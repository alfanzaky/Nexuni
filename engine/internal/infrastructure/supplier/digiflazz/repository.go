package digiflazz

import (
	"context"
	"crypto/md5"
	"encoding/json"
	"errors"
	"fmt"
	"strings"

	domain "github.com/alfanzaky/nexuni/engine/internal/domain/supplier"
	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/httpclient"
)

type digiflazzRepository struct {
	client    httpclient.Client
	baseURL   string
	username  string
	apiKey    string
	isTesting bool
}

func NewRepository(client httpclient.Client, baseURL, username, apiKey string, isTesting bool) domain.Repository {
	return &digiflazzRepository{
		client:    client,
		baseURL:   baseURL,
		username:  username,
		apiKey:    apiKey,
		isTesting: isTesting,
	}
}

// RequestPayload represents the JSON payload sent to Digiflazz for Prepay transactions.
type RequestPayload struct {
	Username     string `json:"username"`
	BuyerSKUCode string `json:"buyer_sku_code"`
	CustomerNo   string `json:"customer_no"`
	RefID        string `json:"ref_id"`
	Sign         string `json:"sign"`
	Testing      bool   `json:"testing,omitempty"`
}

// ResponsePayload represents the standard JSON response from Digiflazz.
type ResponsePayload struct {
	Data struct {
		RefID        string `json:"ref_id"`
		CustomerNo   string `json:"customer_no"`
		BuyerSKUCode string `json:"buyer_sku_code"`
		Message      string `json:"message"`
		Status       string `json:"status"`
		RC           string `json:"rc"`
		SN           string `json:"sn"`
	} `json:"data"`
}

func (r *digiflazzRepository) generateSignature(refID string) string {
	raw := fmt.Sprintf("%s%s%s", r.username, r.apiKey, refID)
	hash := md5.Sum([]byte(raw))
	return fmt.Sprintf("%x", hash)
}

func (r *digiflazzRepository) mapStatus(digiflazzStatus string) domain.TransactionStatus {
	// Digiflazz statuses: "Sukses", "Gagal", "Pending"
	switch strings.ToLower(digiflazzStatus) {
	case "sukses":
		return domain.StatusSuccess
	case "gagal":
		return domain.StatusFailed
	case "pending":
		return domain.StatusPending
	default:
		// Fallback for unknown statuses to pending so it can be re-checked later
		return domain.StatusPending
	}
}

// Purchase triggers a transaction on the supplier's API.
func (r *digiflazzRepository) Purchase(ctx context.Context, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	url := fmt.Sprintf("%s/v1/transaction", r.baseURL)
	headers := map[string]string{
		"Content-Type": "application/json",
	}

	payload := RequestPayload{
		Username:     r.username,
		BuyerSKUCode: productCode,
		CustomerNo:   destination,
		RefID:        transactionID,
		Sign:         r.generateSignature(transactionID),
		Testing:      r.isTesting,
	}

	reqBytes, _ := json.Marshal(payload)

	resBytes, statusCode, err := r.client.DoRequest(ctx, "POST", url, headers, reqBytes)
	if err != nil && !errors.Is(err, httpclient.ErrServerStatus) {
		return nil, fmt.Errorf("digiflazz network error: %w", err)
	}

	var res ResponsePayload
	if parseErr := json.Unmarshal(resBytes, &res); parseErr != nil {
		// If status is >= 400 and it's not JSON, it could be a WAF block or bad credentials.
		if statusCode >= 500 {
			return nil, fmt.Errorf("digiflazz server error %d: %s", statusCode, string(resBytes))
		}
		// If unmarshal fails but the response is 4xx, it's a permanent client error.
		return nil, &domain.PermanentError{Err: fmt.Errorf("failed to parse digiflazz response (HTTP %d): %w", statusCode, parseErr)}
	}

	// Some 4xx errors from Digiflazz still return valid JSON with 'Gagal' status.
	return &domain.SupplierResponse{
		Status:  r.mapStatus(res.Data.Status),
		Message: res.Data.Message,
		Sn:      res.Data.SN,
	}, nil
}

// CheckStatus verifies the final status of a PENDING transaction.
func (r *digiflazzRepository) CheckStatus(ctx context.Context, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	// WARNING: Digiflazz uses the exact same endpoint and payload for checking status.
	// If the ref_id already exists in their system, they return the current status.
	// If the ref_id does NOT exist, they will process it as a NEW purchase.
	// 
	// In Nexuni's architecture, this is safe because `check_status` is only 
	// dispatched by Laravel's PollPendingTransactions command for transactions 
	// that are already marked PENDING (meaning the original Purchase succeeded).
	// Network failures during initial Purchase return TransientError, causing the 
	// message to be requeued with action="purchase", never "check_status".
	return r.Purchase(ctx, transactionID, destination, productCode)
}
