package supplier

import (
	"context"
	"errors"
	"fmt"
	"log"

	domain "github.com/alfanzaky/nexuni/engine/internal/domain/supplier"
)

// ErrUnsupportedProvider is returned when the router cannot find a repository for the given provider ID.
var ErrUnsupportedProvider = errors.New("unsupported provider ID")

// Router determines which supplier API to call based on the provider ID.
type Router struct {
	repositories map[int]domain.Repository
}

func NewRouter() *Router {
	return &Router{
		repositories: make(map[int]domain.Repository),
	}
}

// Register adds a supplier repository to the router for a specific provider ID.
func (r *Router) Register(providerID int, repo domain.Repository) {
	r.repositories[providerID] = repo
}

// Route executes the purchase via the appropriate supplier.
func (r *Router) Route(ctx context.Context, providerID int, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	repo, exists := r.repositories[providerID]
	if !exists {
		log.Printf("[Router] Unsupported provider ID: %d", providerID)
		return nil, fmt.Errorf("%w: %d", ErrUnsupportedProvider, providerID)
	}

	log.Printf("[Router] Routing transaction %s for provider %d (code: %s)", transactionID, providerID, productCode)
	return repo.Purchase(ctx, transactionID, destination, productCode)
}

// CheckStatus verifies the final status of a PENDING transaction via the appropriate supplier.
func (r *Router) CheckStatus(ctx context.Context, providerID int, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	repo, exists := r.repositories[providerID]
	if !exists {
		log.Printf("[Router] Unsupported provider ID for status check: %d", providerID)
		return nil, fmt.Errorf("%w: %d", ErrUnsupportedProvider, providerID)
	}

	log.Printf("[Router] Checking status for transaction %s for provider %d", transactionID, providerID)
	return repo.CheckStatus(ctx, transactionID, destination, productCode)
}
