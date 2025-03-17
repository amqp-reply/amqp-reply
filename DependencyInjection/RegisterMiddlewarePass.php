<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\DependencyInjection;

use AmqpReply\AmqpReply\Transport\AmqpReplyTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterMiddlewarePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('messenger.transport.amqp.factory')) {
            return;
        }

        $definition = $container->getDefinition('messenger.transport.amqp.factory');
        $definition->setClass(AmqpReplyTransportFactory::class);
    }
}
