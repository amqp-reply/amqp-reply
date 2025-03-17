<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Transport;

use AmqpReply\AmqpReply\Exception\FlexibleExceptionFactory;
use AmqpReply\AmqpReply\Serialization\FlexiblePhpSerializer;
use AmqpReply\AmqpReply\Stamp\ReplyQueueNameStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpReplyTransport extends AmqpTransport
{
    private AmqpReplyRequester $requester;
    private AmqpReplyResponder $responder;
    private SerializerInterface $replySerializer;
    private AmqpReplyQueueManager $queueManager;


    public function __construct(
        private Connection   $connection,
        private array        $options,
        ?SerializerInterface $serializer = null,
    )
    {
        parent::__construct($connection, $serializer);
        $this->replySerializer = new FlexiblePhpSerializer();
        $this->queueManager = new AmqpReplyQueueManager($connection);
    }

    public function send(Envelope $envelope): Envelope
    {
        $envelope = parent::send($this->addRequestStamp($envelope));
        $envelope = $this->getRequester()->request($envelope);
        $this->ensureException($envelope);
        return $envelope;
    }

    public function ack(Envelope $envelope): void
    {
        parent::ack($envelope);
        $this->getResponder()->respond($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        parent::reject($envelope);
        $this->getResponder()->respond($envelope);
    }

    private function getRequester(): AmqpReplyRequester
    {
        return $this->requester ??= new AmqpReplyRequester(
            $this->connection,
            $this->options,
            $this->replySerializer,
            $this->queueManager
        );
    }

    private function getResponder(): AmqpReplyResponder
    {
        return $this->responder ??= new AmqpReplyResponder(
            $this->connection,
            $this->options,
            $this->replySerializer,
            $this->queueManager
        );
    }

    private function addRequestStamp(Envelope $envelope): Envelope
    {
        $queueName = uniqid($this->options['reply']['prefix'] ?? 'reply_');
        return $envelope->with(new ReplyQueueNameStamp($queueName));
    }

    private function ensureException(Envelope $envelope): void
    {
        if (!($error = $envelope->last(ErrorDetailsStamp::class))) {
            return;
        }

        throw FlexibleExceptionFactory::createException($error->getFlattenException());
    }
}
