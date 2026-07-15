package digiflazz

import (
	"context"
	"fmt"

	domain "github.com/alfanzaky/nexuni/engine/internal/domain/supplier"
	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/httpclient"
)

type digiflazzRepository struct {
	client  httpclient.Client
	baseURL string
}

func NewRepository(client httpclient.Client, baseURL string) domain.Repository {
	return &digiflazzRepository{
		client:  client,
		baseURL: baseURL,
	}
}

// Purchase triggers a transaction on the supplier's API.
func (r *digiflazzRepository) Purchase(ctx context.Context, transactionID, destination, productCode string) (*domain.SupplierResponse, error) {
	// For Phase 8.1, we make a dummy request to the configured baseURL to test the Circuit Breaker.
	url := fmt.Sprintf("%s/v1/transaction", r.baseURL)
	headers := map[string]string{
		"Content-Type": "application/json",
	}

	// This dummy request will likely fail (or timeout if the URL is unreachable),
	// which is exactly what we want to test the Circuit Breaker behavior.
	_, _, err := r.client.DoRequest(ctx, "POST", url, headers, []byte(`{"testing":"true"}`))

	if err != nil {
		return nil, fmt.Errorf("digiflazz purchase failed: %w", err)
	}

	// If by some miracle it succeeds, return a mock response
	return &domain.SupplierResponse{
		Status:  domain.StatusPending,
		Message: "Simulated PENDING from Digiflazz",
		Sn:      fmt.Sprintf("SN-%s", transactionID),
	}, nil
}

// CheckStatus verifies the final status of a PENDING transaction.
func (r *digiflazzRepository) CheckStatus(ctx context.Context, transactionID string) (*domain.SupplierResponse, error) {
	url := fmt.Sprintf("%s/v1/transaction/status", r.baseURL)
	headers := map[string]string{
		"Content-Type": "application/json",
	}

	_, _, err := r.client.DoRequest(ctx, "POST", url, headers, []byte(`{"testing":"true"}`))
	
	if err != nil {
		return nil, fmt.Errorf("digiflazz check status failed: %w", err)
	}

	return &domain.SupplierResponse{
		Status:  domain.StatusSuccess,
		Message: "Simulated SUCCESS after status check",
		Sn:      fmt.Sprintf("SN-%s-RESOLVED", transactionID),
	}, nil
}
