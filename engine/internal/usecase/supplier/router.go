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

	log.Printf("[Router] Routing transaction %s for provider %d (dest: %s, code: %s)", transactionID, providerID, destination, productCode)

	// Simulate network delay
	time.Sleep(500 * time.Millisecond)

	// Simulated Mock Response
	// In a real scenario, this would be `return r.digiflazzRepo.Purchase(ctx, ...)`
	return &domain.SupplierResponse{
		Status:  domain.StatusSuccess,
		Message: "Simulated Success from Mock Supplier",
		Sn:      fmt.Sprintf("SN-%s", transactionID),
	}, nil
}
