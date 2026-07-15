package main

import (
	"log"
	"os"
	"os/signal"
	"syscall"

	"github.com/alfanzaky/nexuni/engine/internal/infrastructure/rabbitmq"
	"github.com/alfanzaky/nexuni/engine/internal/usecase"
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

	processor := usecase.NewTransactionProcessor()
	consumer, err := rabbitmq.NewConsumer(amqpURI, processor)
	if err != nil {
		log.Fatalf("Failed to connect to RabbitMQ: %v", err)
	}
	defer consumer.Close()

	// Use an error channel so that consumer failures propagate to the main goroutine,
	// allowing deferred cleanup (consumer.Close) to execute gracefully.
	consumerErr := make(chan error, 1)
	go func() {
		if err := consumer.Start(queueName); err != nil {
			consumerErr <- err
		}
	}()

	// Wait for either an interrupt signal or a consumer error
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)

	select {
	case <-quit:
		log.Println("Shutting down Go Transaction Engine...")
	case err := <-consumerErr:
		log.Printf("Consumer encountered a fatal error: %v. Shutting down...", err)
	}
}
