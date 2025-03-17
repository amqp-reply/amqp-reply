<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Transport;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Exception\TransportException;

class AmqpReplyQueueManager
{
    public function __construct(private Connection $connection)
    {
    }

    public function createTemporaryQueue(string $queueName): string
    {
        $queue = $this->connection->queue($queueName);
        $queue->setFlags(AMQP_AUTODELETE);
        $queue->declare();
        $queue->bind($this->connection->exchange()->getName(), $queueName);

        return $queueName;
    }

    public function waitForResponse(string $queueName, int $timeout = 5): ?string
    {
        $startTime = time();
        if(!($queue = $this->connection->queue($queueName))) {
            return null;
        }

        while ((time() - $startTime) < $timeout) {
            if ($message = $this->connection->get($queueName)) {
                $queue->delete();
                return $message->getBody();
            }

            usleep(100000);
        }

        $queue->delete();
        throw new TransportException('Timeout waiting for response on queue: ' . $queueName);
    }

    public function publishOnQueue(array $encodedMessage, string $queueRequestName): void
    {
        try {
            $this->connection->publish(
                $encodedMessage['body'],
                $encodedMessage['headers'] ?? [],
                0,
                new AmqpStamp($queueRequestName)
            );
        } catch (\AMQPException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }
}
