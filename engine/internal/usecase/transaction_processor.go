package usecase

import (
	"context"
	"encoding/json"
	"fmt"
	"log"
	"time"

	domainSupplier "github.com/alfanzaky/nexuni/engine/internal/domain/supplier"
	"github.com/alfanzaky/nexuni/engine/internal/domain/transaction"
	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/httpclient"
	"github.com/alfanzaky/nexuni/engine/internal/usecase/supplier"
)

type TransactionProcessor struct {
	router         *supplier.Router
	callbackClient httpclient.Client
	laravelURL     string
	internalToken  string
}

func NewTransactionProcessor(router *supplier.Router, callbackClient httpclient.Client, laravelURL string, internalToken string) *TransactionProcessor {
	return &TransactionProcessor{
		router:         router,
		callbackClient: callbackClient,
		laravelURL:     laravelURL,
		internalToken:  internalToken,
	}
}

// CallbackPayload represents the payload sent back to Laravel
type CallbackPayload struct {
	TransactionID string `json:"transaction_id"`
	Status        string `json:"status"`
	Message       string `json:"message"`
	Sn            string `json:"sn"`
}

func (tp *TransactionProcessor) Process(payloadBytes []byte) error {
	var payload transaction.Payload
	if err := json.Unmarshal(payloadBytes, &payload); err != nil {
		log.Printf("Failed to unmarshal payload: %v", err)
		return err // In production, might send to DLQ
	}

	log.Printf("Processing transaction ID: %s", payload.TransactionID)

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	// 1. Route to Supplier based on Action
	var res *domainSupplier.SupplierResponse
	var err error

	if payload.Action == "check_status" {
		log.Printf("Executing CheckStatus for %s", payload.TransactionID)
		res, err = tp.router.CheckStatus(ctx, payload.ProviderID, payload.TransactionID)
	} else {
		log.Printf("Executing Purchase for %s", payload.TransactionID)
		res, err = tp.router.Route(ctx, payload.ProviderID, payload.TransactionID, payload.Destination, fmt.Sprintf("%d", payload.ProductID))
	}
	
	status := "FAILED"
	message := "Unknown error"
	sn := ""

	if err != nil {
		log.Printf("Transaction %s failed at supplier: %v", payload.TransactionID, err)
		message = err.Error()
	} else {
		log.Printf("Transaction %s processed. Status: %s", payload.TransactionID, res.Status)
		status = string(res.Status)
		message = res.Message
		sn = res.Sn
	}

	if status == "PENDING" {
		log.Printf("Transaction %s is still PENDING. Acknowledging message without callback. Laravel will poll again.", payload.TransactionID)
		return nil
	}

	// 2. Callback to Laravel
	cbPayload := CallbackPayload{
		TransactionID: payload.TransactionID,
		Status:        status,
		Message:       message,
		Sn:            sn,
	}

	cbBytes, _ := json.Marshal(cbPayload)
	cbURL := fmt.Sprintf("%s/api/internal/callback", tp.laravelURL)
	headers := map[string]string{
		"Content-Type": "application/json",
		"Accept":       "application/json",
		// Use environment-configured token to authenticate internal requests
		"X-Internal-Token": tp.internalToken,
	}

	log.Printf("Sending callback to %s", cbURL)
	_, statusCode, cbErr := tp.callbackClient.DoRequest(ctx, "POST", cbURL, headers, cbBytes)
	if cbErr != nil {
		log.Printf("Failed to send callback to Laravel: %v", cbErr)
		return cbErr // Return error so message gets Nack'd and retried
	}

	if statusCode >= 400 {
		log.Printf("Laravel returned error status %d for callback", statusCode)
		return fmt.Errorf("laravel callback returned %d", statusCode)
	}

	log.Printf("Callback successful for %s", payload.TransactionID)
	return nil
}
