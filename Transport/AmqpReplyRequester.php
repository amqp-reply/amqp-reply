<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Transport;

use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyRequester
{

    public function __construct(
        private Connection             $connection,
        private array                  $options,
        private ?SerializerInterface   $serializer = null,
        private ?AmqpReplyQueueManager $queueManager = null,
    )
    {
        $this->queueManager ??= new AmqpReplyQueueManager($this->connection);
    }


    public function request(Envelope $envelope): mixed
    {
        $requestStamp = $this->getRequestStamp($envelope);
        $queueName = $this->queueManager->createTemporaryQueue($requestStamp->getName());
        $body = $this->queueManager->waitForResponse($queueName, $this->options['reply']['timeout'] ?? 5);
        return $this->serializer->decode(['body' => $body]);
    }

    public function getRequestStamp(Envelope $envelope): ReplyQueueNameStamp
    {
        if ($stamp = $envelope->last(ReplyQueueNameStamp::class)) {
            return $stamp;
        }

        throw new \LogicException('No ReplyQueueNameStamp found');
    }

}
