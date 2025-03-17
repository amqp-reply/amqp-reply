# AMQP Reply Bundle

[![codecov](https://codecov.io/gh/conejerock/ampq-bundle/branch/main/graph/badge.svg?token=NZ15C7YQ1X)](https://codecov.io/gh/conejerock/ampq-bundle)

AMQP Reply Bundle is an extension of Symfony's `amqp-messenger` that abstracts RabbitMQ's RPC (Remote Procedure Call) functionality. It provides a simple and flexible way to send messages and wait for a response in Symfony microservices architecture.

## Why Use AMQP Reply Bundle?

- **Simple RPC Handling:** No need to manually implement RabbitMQ RPC.
- **Seamless Symfony Integration:** Works directly with Symfony Messenger.
- **Improved Microservices Communication:** Enables synchronous message processing while maintaining decoupled services.
- **Reliable Response Management:** Built-in support for handling responses efficiently.

## Installation

```sh
composer require amqp-reply/amqp-reply
```

## Configuration

Ensure your Symfony Messenger transport configuration supports AMQP:

```yaml
framework:
  messenger:
    transports:
      my_transport_sync:
        dsn: 'amqp://guest:guest@rabbitmq:5672'
        options:
          exchange:
            name: query_exchange
            type: topic
          queues:
            query_queue: ~
          reply: # Option to enable amqp-messenger
            timeout: 5
            prefix: 'my_reply_'
    routing:
      'App\Query\MyQuery': my_transport_sync

```

## Usage

### Sending a Request and Waiting for a Reply

```php
declare(strict_types=1);

namespace App;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Query\MyQuery;
use App\Query\MyQueryResponse;

final class RpcClient
{
    public function __construct(private MessageBusInterface $bus) {}

    public function __invoke(): string
    {
        $handledStamp = $this->bus->dispatch(new MyQuery($id))->last(HandledStamp::class)
        /** @var MyQueryResponse $response */
        $response = $handledStamp->getResult();
        return $response->message;
    }
}
```

### Handling the Request in a Consumer

```php
declare(strict_types=1);

namespace App\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class MyQuery
{
    public function __construct(public readonly string $id) {}
}


final class MyQueryResponse
{
    public function __construct(public readonly string $message) {}
}


#[AsMessageHandler]
class MyQueryHandler
{

    public function __invoke(MyQuery $query): MyQueryResponse
    {
        $message = 'Hello, ' . $query->id;
        return new MyQueryResponse($message);
    }
}
```

### Consuming requests

```sh
php bin/console messenger:consume my_transport_sync
```

## License

This library is licensed under the [MIT License](LICENSE).

## Author

Developed by [Juanjo Conejero](https://juanjoconejero.com).

## Contribute

Contributions are welcome! Feel free to open issues and pull requests to improve this library.

