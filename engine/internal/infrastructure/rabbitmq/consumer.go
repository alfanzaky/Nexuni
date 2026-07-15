package rabbitmq

import (
	"fmt"
	"log"

	"github.com/alfanzaky/nexuni/engine/internal/usecase"
	amqp "github.com/rabbitmq/amqp091-go"
)

type Consumer struct {
	conn      *amqp.Connection
	channel   *amqp.Channel
	processor *usecase.TransactionProcessor
}

func NewConsumer(amqpURI string, processor *usecase.TransactionProcessor) (*Consumer, error) {
	conn, err := amqp.Dial(amqpURI)
	if err != nil {
		return nil, err
	}

	ch, err := conn.Channel()
	if err != nil {
		conn.Close() // Close the connection to prevent resource leak
		return nil, err
	}

	return &Consumer{
		conn:      conn,
		channel:   ch,
		processor: processor,
	}, nil
}

func (c *Consumer) Start(queueName string) error {
	dlxName := queueName + ".dlx"
	dlqName := queueName + ".dead"

	// Declare Dead Letter Exchange (DLX)
	err := c.channel.ExchangeDeclare(
		dlxName,  // name
		"direct", // type
		true,     // durable
		false,    // auto-deleted
		false,    // internal
		false,    // no-wait
		nil,      // arguments
	)
	if err != nil {
		return fmt.Errorf("failed to declare DLX %q: %w", dlxName, err)
	}

	// Declare Dead Letter Queue (DLQ) and bind it to the DLX
	_, err = c.channel.QueueDeclare(dlqName, true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("failed to declare DLQ %q: %w", dlqName, err)
	}
	if err = c.channel.QueueBind(dlqName, queueName, dlxName, false, nil); err != nil {
		return fmt.Errorf("failed to bind DLQ to DLX: %w", err)
	}

	// Declare the main queue, pointing failed messages to the DLX
	_, err = c.channel.QueueDeclare(
		queueName,
		true,  // durable
		false, // delete when unused
		false, // exclusive
		false, // no-wait
		amqp.Table{"x-dead-letter-exchange": dlxName},
	)
	if err != nil {
		return fmt.Errorf("failed to declare main queue %q: %w", queueName, err)
	}

	// Set QoS prefetch count to prevent unbounded memory usage if processing is slow.
	// 100 is a reasonable default to keep the consumer busy without overloading it.
	if err := c.channel.Qos(100, 0, false); err != nil {
		return fmt.Errorf("failed to set QoS: %w", err)
	}

	msgs, err := c.channel.Consume(
		queueName,
		"",    // consumer tag
		false, // auto-ack (we use manual ack for reliability)
		false, // exclusive
		false, // no-local
		false, // no-wait
		nil,   // args
	)
	if err != nil {
		return err
	}

	log.Printf("Consumer started, waiting for messages on queue: %s", queueName)

	for msg := range msgs {
		// Log only a safe, non-sensitive identifier — never log the full body.
		// The payload contains PII (destination phone number, amount).
		log.Printf("Received message from queue (body length: %d bytes)", len(msg.Body))

		err := c.processor.Process(msg.Body)
		if err != nil {
			// Nack and do not requeue (send to DLQ if configured)
			log.Printf("Error processing message: %v", err)
			msg.Nack(false, false)
		} else {
			msg.Ack(false)
		}
	}

	// The delivery channel was closed — this means the broker connection dropped.
	// Returning a sentinel error ensures the main goroutine is notified and can
	// trigger a graceful shutdown instead of silently blocking forever.
	return fmt.Errorf("message delivery channel closed unexpectedly (broker disconnected)")
}

func (c *Consumer) Close() {
	if c.channel != nil {
		c.channel.Close()
	}
	if c.conn != nil {
		c.conn.Close()
	}
}
