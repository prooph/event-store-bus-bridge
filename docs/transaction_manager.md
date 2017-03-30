# Transaction Handling

The transaction manager starts a new event store transaction on every command dispatch and commits it afterwards.

## Set Up

To enable transaction handling based on command dispatch you need to set up the `Prooph\EventStoreBusBridge\TransactionManager`.
The transaction manager acts as command bus plugin:

```php
/** @var $eventStore Prooph\EventStore\EventStore */
$transactionManager = new TransactionManager($eventStore);

/** @var $commandBus Prooph\ServiceBus\CommandBus */
$transactionManager->attachToMessageBus($commandBus);
```

That's it!

### Container-Driven Set Up

If you are using the `container-aware factories` shipped with prooph/service-bus you may also
want to auto register the `TransactionManager`. As long as the command bus is available as service `Prooph\ServiceBus\CommandBus` in the container you can use
the `Prooph\EventStoreBusBridge\Container\TransactionManagerFactory` for that. Just map the factory to a service name like `prooph.transaction_manager` and
add the service name to the plugin list of the event store configuration. Also have a look at the event store docs for more details about the plugin system.
