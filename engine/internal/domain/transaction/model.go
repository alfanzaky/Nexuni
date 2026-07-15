package transaction

import "time"

// Payload represents the JSON payload sent by Laravel.
type Payload struct {
	Action         string    `json:"action"`
	TransactionID  string    `json:"transaction_id"`
	ProductID      int       `json:"product_id"`
	ProviderID     int       `json:"provider_id"`
	Destination    string    `json:"destination"`
	Amount         string    `json:"amount"`
	IdempotencyKey string    `json:"idempotency_key"`
	Timestamp      time.Time `json:"timestamp"`
}
