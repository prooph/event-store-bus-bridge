# Event Publishing

The `Prooph\EventStoreBusBridge\EventPublisher` is an event store plugin which listens on the event store `create`,
`appendTo` and `commit` action event.
It iterates over the `recordedEvents` and publishes them on the `Prooph\ServiceBus\EventBus`.

## Set Up

The EventPublisher requires an instance of `Prooph\ServiceBus\EventBus` at construction time.
Furthermore, the publisher implements `Prooph\EventStore\Plugin\Plugin`. So you need to pass the event store to the
`EventPublisher::attachToEventStore` method. That's it. From this moment on all domain events are published on the event bus when
an event store transaction is committed.

```php
$eventPublisher->attachToEventStore($eventStore);
```

### Container-Driven Set Up

If you are using the `container-aware factory` shipped with prooph/event-store you may also
want to auto register the `EventPublisher`. First you need to make the event publisher available as a service in the
container. You can use the `Prooph\EventStoreBusBridge\Container\EventPublisherFactory` for that.

Map the factory to a service name like `prooph.event_publisher` and add this service name to the list of event store plugins
in your application configuration. Also have a look at the event store docs for more details about the plugin system.
