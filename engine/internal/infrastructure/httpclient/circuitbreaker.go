package httpclient

import (
	"context"
	"errors"

	"github.com/sony/gobreaker"
)

type cbClient struct {
	client Client
	cb     *gobreaker.CircuitBreaker
}

// NewCircuitBreakerClient wraps an existing HTTP Client with a Circuit Breaker.
func NewCircuitBreakerClient(client Client, name string, settings gobreaker.Settings) Client {
	settings.Name = name
	return &cbClient{
		client: client,
		cb:     gobreaker.NewCircuitBreaker(settings),
	}
}

func (c *cbClient) DoRequest(ctx context.Context, method, url string, headers map[string]string, body []byte) ([]byte, int, error) {
	// Execute the request through the Circuit Breaker
	res, err := c.cb.Execute(func() (interface{}, error) {
		respBody, statusCode, reqErr := c.client.DoRequest(ctx, method, url, headers, body)
		
		// Typically, HTTP 5xx errors should also count as failures for the circuit breaker.
		// For simplicity, we just look at network errors (reqErr != nil) and 5xx status codes.
		if reqErr != nil {
			return nil, reqErr
		}
		if statusCode >= 500 {
			// Wrapping response so we can still return the body and status code, but returning an error 
			// to trip the circuit breaker.
			return &cbResponse{body: respBody, statusCode: statusCode}, errHttpStatus5xx
		}
		
		return &cbResponse{body: respBody, statusCode: statusCode}, nil
	})

	if err != nil {
		// If the error was our custom 5xx wrapper error, extract the response payload
		if err == errHttpStatus5xx && res != nil {
			cbr := res.(*cbResponse)
			return cbr.body, cbr.statusCode, nil
		}
		// Otherwise, it's a real network error or CircuitBreakerOpenError
		return nil, 0, err
	}

	cbr := res.(*cbResponse)
	return cbr.body, cbr.statusCode, nil
}

type cbResponse struct {
	body       []byte
	statusCode int
}

var errHttpStatus5xx = errors.New("http status 5xx")
