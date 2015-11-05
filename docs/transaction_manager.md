# Transaction Handling

## Set Up
To enable transaction handling based on command dispatch you need to set up the [TransactionManager](src/TransactionManager.php).
The transaction manager acts as command bus AND event store plugin so you need to attach it to both:

```php
/** @var $eventStore Prooph\EventStore\EventStore */
$transactionManager->setUp($eventStore);

/** @var $commandBus Prooph\ServiceBus\CommandBus */
$commandBus->utilize($transactionManager);
```

That's it!

### Container-Driven Set Up
If you are using the `container-aware factories` shipped with prooph/service-bus you may also
want to auto register the `TransactionManager`. As long as the command bus is available as service `Prooph\ServiceBus\CommandBus` in the container you can use
the [TransactionManagerFactory](src/Container/TransactionManagerFactory.php) for that. Just map the factory to a service name like `prooph.transaction_manager` and
add the service name to the plugin list of the event store configuration. Please refer to [prooph/event-store docs](https://github.com/prooph/event-store/blob/master/docs/interop_factories.md#event-store-factory)
for more details.

## Features

1. The transaction manager starts a new event store transaction on every command dispatch.
    *Note: Nested transactions were removed with prooph/event-store v6.0. So you can dispatch follow up commands only after event store commit!*
2. If the `dispatched command` is an instance of `Prooph\Common\Messaging\Message` the transaction manager will also add `causation metadata` to each recorded event during the transaction.
   Two entries are added to the metadata:
   - `causation_id` = `$command->uuid()->toString()`
   - `causation_name` = `$command->messageName()`.
   *Note: Depending on the event store adapter used you may need to alter your event stream schema*