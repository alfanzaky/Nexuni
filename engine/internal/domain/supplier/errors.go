package supplier

import "fmt"

// PermanentError indicates a failure that cannot be resolved by retrying,
// such as a 4xx HTTP client error or bad credentials.
type PermanentError struct {
	Err error
}

func (e *PermanentError) Error() string {
	return fmt.Sprintf("supplier permanent error: %v", e.Err)
}

func (e *PermanentError) Unwrap() error {
	return e.Err
}
