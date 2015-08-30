# Event Publishing

The [EventPublisher](src/EventPublisher.php) is an event store plugin which listens on the event store `commit.post` action event.
It iterates over the `recordedEvents` and publishes each on the `Prooph\ServiceBus\EventBus`.

## Set Up

The EventPublisher requires an instance of `Prooph\ServiceBus\EventBus` at construction time.
Furthermore, the publisher implements `Prooph\EventStore\Plugin\Plugin`. So you need to pass the event store to the
`EventPublisher::setUp` method. That's it. From this moment on all domain events are published on the event bus when
an event store transaction is committed.

### Container-Driven Set Up

If you are using the `container-aware factory` shipped with prooph/event-store you may also
want to auto register the `EventPublisher`. First you need to make the event publisher available as a service in the
container. You can use the [EventPublisherFactory](src/Container/EventPublisherFactory.php) for that.

*Note: The event bus should be available as service `Prooph\ServiceBus\EventBus` in the container. But if your event bus is
registered with another service name you can extend the factory and override the protected method `getEventBusServiceName`.*

Map the factory to a service name like `prooph.event_publisher` and add this service name to the list of event store plugins
in your application configuration. Please refer to the [event store docs](https://github.com/prooph/event-store/blob/master/docs/event_store.md#container-driven-creation)
for more details about plugin configuration.