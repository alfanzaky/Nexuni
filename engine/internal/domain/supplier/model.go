package supplier

import "context"

// SupplierResponse represents a normalized response from any supplier.
type SupplierResponse struct {
	Status  TransactionStatus
	Message string
	Sn      string // Serial Number / Reference Number from supplier
}

type TransactionStatus string

const (
	StatusSuccess TransactionStatus = "SUCCESS"
	StatusFailed  TransactionStatus = "FAILED"
	StatusPending TransactionStatus = "PENDING"
)

type Repository interface {
	// Purchase triggers a transaction on the supplier's API.
	Purchase(ctx context.Context, transactionID, destination, productCode string) (*SupplierResponse, error)

	// CheckStatus verifies the final status of a PENDING transaction.
	CheckStatus(ctx context.Context, transactionID string) (*SupplierResponse, error)
}
