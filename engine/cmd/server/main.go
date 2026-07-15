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

	// Start consuming in a goroutine
	go func() {
		if err := consumer.Start(queueName); err != nil {
			log.Fatalf("Failed to start consumer: %v", err)
		}
	}()

	// Wait for interrupt signal to gracefully shutdown the server
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down Go Transaction Engine...")
}
