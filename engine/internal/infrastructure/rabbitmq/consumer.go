package rabbitmq

import (
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
		return nil, err
	}

	return &Consumer{
		conn:      conn,
		channel:   ch,
		processor: processor,
	}, nil
}

func (c *Consumer) Start(queueName string) error {
	// Ensure queue exists
	_, err := c.channel.QueueDeclare(
		queueName,
		true,  // durable
		false, // delete when unused
		false, // exclusive
		false, // no-wait
		nil,   // arguments
	)
	if err != nil {
		return err
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
		log.Printf("Received message: %s", msg.Body)

		err := c.processor.Process(msg.Body)
		if err != nil {
			// In a real system, you might nack or send to DLQ
			log.Printf("Error processing message: %v", err)
			msg.Nack(false, false) // Nack and do not requeue (send to DLQ if configured)
		} else {
			msg.Ack(false)
		}
	}

	return nil
}

func (c *Consumer) Close() {
	if c.channel != nil {
		c.channel.Close()
	}
	if c.conn != nil {
		c.conn.Close()
	}
}
