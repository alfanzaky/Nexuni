package httpclient

import (
	"bytes"
	"context"
	"fmt"
	"io"
	"net/http"
	"time"
)

// Client defines the interface for making HTTP requests.
// Implementations (like CircuitBreaker) may return ErrServerStatus for HTTP 5xx responses.
// Callers who need to parse 5xx response bodies should check for errors.Is(err, ErrServerStatus).
type Client interface {
	DoRequest(ctx context.Context, method, url string, headers map[string]string, body []byte) ([]byte, int, error)
}

type defaultClient struct {
	httpClient *http.Client
}

// NewDefaultClient creates a standard HTTP client with a configurable default timeout.
func NewDefaultClient(timeout time.Duration) Client {
	return &defaultClient{
		httpClient: &http.Client{
			Timeout: timeout,
		},
	}
}

func (c *defaultClient) DoRequest(ctx context.Context, method, url string, headers map[string]string, body []byte) ([]byte, int, error) {
	req, err := http.NewRequestWithContext(ctx, method, url, bytes.NewBuffer(body))
	if err != nil {
		return nil, 0, fmt.Errorf("failed to create request: %w", err)
	}

	for k, v := range headers {
		req.Header.Set(k, v)
	}

	resp, err := c.httpClient.Do(req)
	if err != nil {
		return nil, 0, fmt.Errorf("http request failed: %w", err)
	}
	defer resp.Body.Close()

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, resp.StatusCode, fmt.Errorf("failed to read response body: %w", err)
	}

	return respBody, resp.StatusCode, nil
}
