<?php
/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterAwareEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStoreBusBridge\CausationMetadataEnricher;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\SomethingDone;

class CausationMetadataEnricherTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_exception_when_non_action_event_emitter_aware_event_store_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->setUp($eventStore->reveal());
    }

    /**
     * @test
     */
    public function it_enriches_command_on_create_stream(): void
    {
        $eventStore = $this->getEventStore();

        $causationMetadataEnricher = new CausationMetadataEnricher();

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['name' => $command->payload('name')])
                    ])
                )
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

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

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['name' => $command->payload('name')])
                ])
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

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

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['name' => $command->payload('name')])
                    ])
                )
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            2000
        );

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            500
        );

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

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route(DoSomething::class)->to(function (DoSomething $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['name' => $command->payload('name')])
                ])
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

        $command = new DoSomething(['name' => 'Alex'], 1);

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            2000
        );

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            500
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

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('do something')->to(function (string $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->create(
                new Stream(
                    new StreamName('something'),
                    new \ArrayIterator([
                        new SomethingDone(['foo' => 'bar'])
                    ])
                )
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_CREATE,
            function (ActionEvent $event) use (&$result): void {
                $stream = $event->getParam('stream');
                $result = $stream->streamEvents()->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

        $command = 'do something';

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            2000
        );

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            500
        );

        $commandBus->dispatch($command);
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

        $causationMetadataEnricher->setUp($eventStore);

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('do something')->to(function (string $command) use ($eventStore): void {
            /* @var EventStore $eventStore */
            $eventStore->appendTo(
                new StreamName('something'),
                new \ArrayIterator([
                    new SomethingDone(['foo' => 'bar'])
                ])
            );
        });

        $result = null;

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use (&$result): void {
                $streamEvents = $event->getParam('streamEvents');
                $streamEvents->rewind();
                $result = $streamEvents->current();
            },
            -1000
        );

        $commandBus->utilize($router);
        $commandBus->utilize($causationMetadataEnricher);

        $command = 'do something';

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, null);
            },
            2000
        );

        $commandBus->getActionEventEmitter()->attachListener(
            CommandBus::EVENT_INVOKE_HANDLER,
            function (ActionEvent $event) use ($command): void {
                $event->setParam(CommandBus::EVENT_PARAM_MESSAGE, $command);
            },
            500
        );

        $commandBus->dispatch($command);
    }

    private function getEventStore(): InMemoryEventStore
    {
        return new InMemoryEventStore(new ProophActionEventEmitter());
    }
}
