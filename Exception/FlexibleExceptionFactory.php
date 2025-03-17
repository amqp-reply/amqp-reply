<?php
declare(strict_types=1);

namespace AmqpReply\AmqpReply\Exception;

use Exception;
use ReflectionClass;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Throwable;

class FlexibleExceptionFactory
{
    public static function createException(FlattenException $flatten): Exception
    {
        $class = $flatten->getClass();

        if (!class_exists($class) || !is_subclass_of($class, Throwable::class)) {
            $exception = new Exception(
                $flatten->getMessage(),
                $flatten->getCode()
            );
            self::setExceptionProperties($exception, $flatten);
            return $exception;
        }

        $reflection = new ReflectionClass($class);
        $exception = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $propertyName = $property->getName();

            if (property_exists($flatten, $propertyName)) {
                $value = self::getFlattenProperty($flatten, $propertyName);
                $property->setValue($exception, $value);
            }
        }

        self::setExceptionProperties($exception, $flatten);

        return $exception;
    }

    private static function setExceptionProperties(Exception $exception, FlattenException $flatten): void
    {
        $reflection = new ReflectionClass($exception);

        $properties = ['message', 'code', 'file', 'line'];
        foreach ($properties as $property) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($exception, self::getFlattenProperty($flatten, $property));
            }
        }
    }

    private static function getFlattenProperty(FlattenException $flatten, string $property)
    {
        $reflection = new ReflectionClass($flatten);
        if ($reflection->hasProperty($property)) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            return $prop->getValue($flatten);
        }

        return null;
    }
}
