<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Transport;

use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyResponder
{
    public function __construct(
        private Connection           $connection,
        private array                $options,
        private ?SerializerInterface $serializer = null,
        private ?AmqpReplyQueueManager $queueManager = null
    )
    {
        $this->queueManager ??= new AmqpReplyQueueManager($this->connection);
    }

    public function respond(Envelope $envelope): void
    {
       $requestStamp = $this->getRequestStamp($envelope);

       $envelope = $envelope->withoutAll(ReplyQueueNameStamp::class);
       $encodedMessage = $this->serializer->encode($envelope);

       $this->queueManager->publishOnQueue($encodedMessage, $requestStamp->getName());
    }

    private function getRequestStamp(Envelope $envelope)
    {
        if($stamp = $envelope->last(ReplyQueueNameStamp::class)) {
            return $stamp;
        }

        throw new \LogicException('No ReplyQueueNameStamp found');
    }
}
