package main

import (
	"log"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/httpclient"
	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/rabbitmq"
	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/supplier/digiflazz"
	"github.com/alfanzaky/nexuni/engine/internal/usecase"
	"github.com/alfanzaky/nexuni/engine/internal/usecase/supplier"
	"github.com/sony/gobreaker"
)

func main() {
	log.Println("Starting Nexuni Go Transaction Engine...")

	amqpURI := os.Getenv("RABBITMQ_URI")
	if amqpURI == "" {
		log.Fatal("RABBITMQ_URI environment variable is required but not set. Refusing to start with default credentials.")
	}

	queueName := os.Getenv("RABBITMQ_TRANSACTION_QUEUE")
	if queueName == "" {
		queueName = "transaction_queue"
	}

	laravelInternalURL := os.Getenv("LARAVEL_INTERNAL_URL")
	if laravelInternalURL == "" {
		laravelInternalURL = "http://localhost:8000"
	}

	internalAPIToken := os.Getenv("INTERNAL_API_TOKEN")
	if internalAPIToken == "" {
		log.Fatal("INTERNAL_API_TOKEN environment variable is required but not set.")
	}

	supplierRouter := supplier.NewRouter()
	
	// Create HTTP client with Circuit Breaker for Digiflazz (ProviderID = 1)
	digiflazzBaseClient := httpclient.NewDefaultClient(15 * time.Second)
	digiflazzCBSettings := gobreaker.Settings{
		Name:        "Digiflazz-API-CB",
		MaxRequests: 5,               // Half-open allowed requests
		Interval:    0,               // Never clear counts while CLOSED
		Timeout:     60 * time.Second, // Wait 60s in OPEN state before trying Half-Open
		ReadyToTrip: func(counts gobreaker.Counts) bool {
			// Trip if 5 consecutive failures occur
			return counts.ConsecutiveFailures >= 5
		},
	}
	digiflazzCBClient := httpclient.NewCircuitBreakerClient(digiflazzBaseClient, "digiflazz-api", digiflazzCBSettings)
	
	// Digiflazz credentials from environment
	digiflazzURL := os.Getenv("DIGIFLAZZ_API_URL")
	if digiflazzURL == "" {
		digiflazzURL = "https://api.digiflazz.com" // Default to production URL
	}
	digiflazzUsername := os.Getenv("DIGIFLAZZ_USERNAME")
	if digiflazzUsername == "" {
		log.Println("WARNING: DIGIFLAZZ_USERNAME is not set!")
	}
	digiflazzAPIKey := os.Getenv("DIGIFLAZZ_API_KEY")
	if digiflazzAPIKey == "" {
		log.Println("WARNING: DIGIFLAZZ_API_KEY is not set!")
	}
	digiflazzTesting := os.Getenv("DIGIFLAZZ_TESTING") == "true"

	digiflazzRepo := digiflazz.NewRepository(digiflazzCBClient, digiflazzURL, digiflazzUsername, digiflazzAPIKey, digiflazzTesting)
	supplierRouter.Register(1, digiflazzRepo)
	
	// Create HTTP client with Circuit Breaker for Laravel callback
	baseClient := httpclient.NewDefaultClient(10 * time.Second)
	cbSettings := gobreaker.Settings{
		Name:        "Laravel-Callback-CB",
		MaxRequests: 3,
		Interval:    30 * time.Second,
		Timeout:     10 * time.Second,
	}
	callbackClient := httpclient.NewCircuitBreakerClient(baseClient, "laravel-callback", cbSettings)

	processor := usecase.NewTransactionProcessor(supplierRouter, callbackClient, laravelInternalURL, internalAPIToken)

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)

	// Reconnection loop with exponential backoff.
	// The engine will automatically recover from broker restarts or transient
	// network disruptions without requiring a process restart.
	const maxBackoff = 60 * time.Second
	backoff := 2 * time.Second

	for {
		log.Printf("Connecting to RabbitMQ (next retry in %s on failure)...", backoff)

		consumer, err := rabbitmq.NewConsumer(amqpURI, processor)
		if err != nil {
			log.Printf("Failed to connect to RabbitMQ: %v. Retrying in %s...", err, backoff)
			select {
			case <-quit:
				log.Println("Shutdown signal received during reconnect. Exiting.")
				return
			case <-time.After(backoff):
				backoff = min(backoff*2, maxBackoff)
				continue
			}
		}

		// Reset backoff after a successful connection
		backoff = 2 * time.Second
		log.Println("Connected to RabbitMQ.")

		consumerErr := make(chan error, 1)
		go func() {
			consumerErr <- consumer.Start(queueName)
		}()

		select {
		case <-quit:
			log.Println("Shutdown signal received. Closing consumer and exiting.")
			consumer.Close()
			return
		case err := <-consumerErr:
			log.Printf("Consumer disconnected: %v. Reconnecting...", err)
			consumer.Close()
			// Loop back to reconnect
		}
	}
}

func min(a, b time.Duration) time.Duration {
	if a < b {
		return a
	}
	return b
}
