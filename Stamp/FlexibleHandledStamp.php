<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Stamp;

use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class FlexibleHandledStamp implements StampInterface
{
    public function __construct(
        private \stdClass $object,
        private string    $class,
        private string    $handlerName,
    )
    {
    }

    public static function fromHandledStamp(HandledStamp $handledStamp): self
    {
        $object = $handledStamp->getResult();
        $class = $object::class;
        $handledName = $handledStamp->getHandlerName();

        $reflection = new \ReflectionClass($object);
        $stdObject = new \stdClass();

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $stdObject->{$property->getName()} = $property->getValue($object);
        }

        return new FlexibleHandledStamp($stdObject, $class, $handledName);
    }

    public function toHandledStamp(): HandledStamp
    {
        if (!class_exists($this->class)) {
            return new HandledStamp($this->object, '');
        }

        $reflection = new \ReflectionClass($this->class);
        $object = $reflection->newInstanceWithoutConstructor();
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $property->setValue($object, $this->object->{$property->getName()});
        }

        return new HandledStamp($object, $this->handlerName);
    }
}
