<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Transport;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AmqpReplyTransportFactory extends AmqpTransportFactory
{
    public function createTransport(#[\SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $replyOptions =[...$options];
        unset($options['transport_name'], $options['reply']);

        if (array_key_exists('reply', $replyOptions)) {
            return new AmqpReplyTransport(Connection::fromDsn($dsn, $options), $replyOptions, $serializer);
        }

        return parent::createTransport($dsn, $options, $serializer);
    }
}
