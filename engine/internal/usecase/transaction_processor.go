package usecase

import (
	"encoding/json"
	"log"

	"github.com/alfanzaky/nexuni/engine/internal/domain/transaction"
)

type TransactionProcessor struct {
	// dependencies like gRPC client or HTTP clients will be injected here later
}

func NewTransactionProcessor() *TransactionProcessor {
	return &TransactionProcessor{}
}

func (tp *TransactionProcessor) Process(payloadBytes []byte) error {
	var payload transaction.Payload
	if err := json.Unmarshal(payloadBytes, &payload); err != nil {
		log.Printf("Failed to unmarshal payload: %v", err)
		return err // In production, might send to DLQ instead of returning error if payload is completely invalid
	}

	log.Printf("Processing Transaction: %s for Destination: %s", payload.TransactionID, payload.Destination)

	// TODO: Phase 8 - Implement Supplier Routing and HTTP Request Execution here.
	// For now, we simulate success.
	log.Printf("Transaction %s successfully processed via Mock Supplier.", payload.TransactionID)

	// TODO: Send result back to Laravel via gRPC

	return nil
}
