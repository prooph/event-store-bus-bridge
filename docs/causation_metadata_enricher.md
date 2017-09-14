# Causation Metadata Enricher

If the `dispatched command` is an instance of `Prooph\Common\Messaging\Message` the transaction manager will also add `causation metadata` to each recorded event during the transaction.
Two entries are added to the metadata:
- `causation_id` = `$command->uuid()->toString()`
- `causation_name` = `$command->messageName()`.

## Set Up

The `CausationMetadataEnricher` is a dual plugin, it's a plugin for event-store as well as for the message-bus.

```php
$causationMetadataEnricher = new CausationMetadataEnricher();
$causationMetadataEnricher->attachToEventStore($eventStore);
$causationMetadataEnricher->attachToMessageBus($commandBus);
```

That's it!

### Container-Driven Set Up

If you are using the `container-aware factories` shipped with prooph/event-store and prooph/service-bus you may also
want to auto register the `CausationMetadataEnricher`.

Add the `CausationMetadataEnricher` as plugin to the event-store. Use the `CausationMetadataEnricherFactory`. 
