<?php
/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStoreBusBridge\CausationMetadataEnricher;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\SomethingDone;

class CausationMetadataEnricherTest extends TestCase
{
    /**
     * @test
     */
    public function it_enriches_command_on_create_stream(): void
    {
        $eventStore = $this->getEventStore();

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['name' => $command->payload('name')]),
                    ])
                )
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $stream->streamEvents()->rewind();
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->dispatch($command);

        $this->assertArrayHasKey('_causation_id', $result->metadata());
        $this->assertArrayHasKey('_causation_name', $result->metadata());

        $this->assertEquals($command->uuid()->toString(), $result->metadata()['_causation_id']);
        $this->assertEquals(get_class($command), $result->metadata()['_causation_name']);
    }

    /**
     * @test
     */
    public function it_enriches_command_on_append_to_stream(): void
    {
        $eventStore = $this->getEventStore();

        $eventStore->create(
            new Stream(
                new StreamName('something'),
                new \ArrayIterator()
            )
        );

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['name' => $command->payload('name')]),
                ])
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->dispatch($command);

        $this->assertArrayHasKey('_causation_id', $result->metadata());
        $this->assertArrayHasKey('_causation_name', $result->metadata());

        $this->assertEquals($command->uuid()->toString(), $result->metadata()['_causation_id']);
        $this->assertEquals(get_class($command), $result->metadata()['_causation_name']);
    }

    /**
     * @test
     */
    public function it_returns_early_if_command_is_null_on_create_stream(): void
    {
        $eventStore = $this->getEventStore();

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['name' => $command->payload('name')]),
                    ])
                )
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $stream->streamEvents()->rewind();
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 2000
        );

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 500
        );

        $commandBus->dispatch($command);

        $this->assertArrayNotHasKey('_causation_id', $result->metadata());
        $this->assertArrayNotHasKey('_causation_name', $result->metadata());
    }

    /**
     * @test
     */
    public function it_detaches_from_command_bus_and_event_store(): void
    {
        $eventStore = $this->getEventStore();

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['name' => $command->payload('name')]),
                    ])
                )
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $stream->streamEvents()->rewind();
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $causationMetadataEnricher->detachFromEventStore($eventStore);
        $causationMetadataEnricher->detachFromMessageBus($commandBus);

        $command = new DoSomething(['name' => 'Alex']);

        $commandBus->dispatch($command);

        $this->assertArrayNotHasKey('_causation_id', $result->metadata());
        $this->assertArrayNotHasKey('_causation_name', $result->metadata());
    }

    /**
     * @test
     */
    public function it_returns_early_if_command_is_null_on_append_to_stream(): void
    {
        $eventStore = $this->getEventStore();

        $eventStore->create(
            new Stream(
                new StreamName('something'),
                new \ArrayIterator()
            )
        );

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['name' => $command->payload('name')]),
                ])
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 2000
        );

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 500
        );

        $commandBus->dispatch($command);

        $this->assertArrayNotHasKey('_causation_id', $result->metadata());
        $this->assertArrayNotHasKey('_causation_name', $result->metadata());
    }

    /**
     * @test
     */
    public function it_returns_early_if_command_is_no_instance_of_message_on_create_stream(): void
    {
        $eventStore = $this->getEventStore();

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('do something')->to(function (string $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['foo' => 'bar']),
                    ])
                )
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $stream->streamEvents()->rewind();
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = 'do something';

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 500
        );

        $commandBus->dispatch($command);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertArrayNotHasKey('_causation_id', $result->metadata());
        $this->assertArrayNotHasKey('_causation_', $result->metadata());
    }

    /**
     * @test
     */
    public function it_returns_early_if_command_is_no_instance_of_message_on_append_to_stream(): void
    {
        $eventStore = $this->getEventStore();

        $eventStore->create(
            new Stream(
                new StreamName('something'),
                new \ArrayIterator()
            )
        );

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->attachToEventStore($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('do something')->to(function (string $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['foo' => 'bar']),
                ])
            );
        });

        $result = null;

        $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $router->attachToMessageBus($commandBus);
        $causationMetadataEnricher->attachToMessageBus($commandBus);

        $command = 'do something';

        $commandBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 500
        );

        $commandBus->dispatch($command);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertArrayNotHasKey('_causation_id', $result->metadata());
        $this->assertArrayNotHasKey('_causation_', $result->metadata());
    }

    private function getEventStore(): ActionEventEmitterEventStore
    {
        return new ActionEventEmitterEventStore(new InMemoryEventStore(), new ProophActionEventEmitter());
    }
}
