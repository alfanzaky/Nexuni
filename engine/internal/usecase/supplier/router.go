package supplier

import (
	"context"
	"fmt"
	"log"
	"time"

	domain "github.com/alfanzaky/nexuni/engine/internal/domain/supplier"
)

// Router determines which supplier API to call based on the provider ID.
type Router struct {
	// In the future, this will hold actual implementations of supplier repositories.
	// e.g. digiflazzRepo domain.Repository
}

func NewRouter() *Router {
	return &Router{}
}

// Route executes the purchase via the appropriate supplier.
func (r *Router) Route(ctx context.Context, providerID int, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	// TODO: Phase 8.x - Implement actual supplier routing based on ProviderID.
	// 1 = Mock Digiflazz, 2 = Mock Lapak, etc.

	log.Printf("[Router] Routing transaction %s for provider %d (code: %s)", transactionID, providerID, productCode)

	// Simulate network delay
	time.Sleep(500 * time.Millisecond)

	// Simulated Mock Response
	// For testing the polling mechanism, we simulate a PENDING response on initial purchase
	return &domain.SupplierResponse{
		Status:  domain.StatusPending,
		Message: "Simulated PENDING from Mock Supplier",
		Sn:      fmt.Sprintf("SN-%s", transactionID),
	}, nil
}

// CheckStatus verifies the final status of a PENDING transaction via the appropriate supplier.
func (r *Router) CheckStatus(ctx context.Context, providerID int, transactionID string) (*domain.SupplierResponse, error) {
	log.Printf("[Router] Checking status for transaction %s for provider %d", transactionID, providerID)

	time.Sleep(500 * time.Millisecond)

	// Simulated Mock CheckStatus Response
	// In a real scenario, this would query the supplier. Here we simulate it resolving to SUCCESS.
	return &domain.SupplierResponse{
		Status:  domain.StatusSuccess,
		Message: "Simulated SUCCESS after status check",
		Sn:      fmt.Sprintf("SN-%s-RESOLVED", transactionID),
	}, nil
}
