<?php

/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge;

use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\ConcurrencyException;
use Prooph\EventStore\Exception\StreamExistsAlready;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\ServiceBus\EventBus;

class EventPublisherTest extends TestCase
{
    /**
     * @var ActionEventEmitterEventStore
     */
    private $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new TransactionalActionEventEmitterEventStore(new InMemoryEventStore(), new ProophActionEventEmitter());
    }

    /**
     * @test
     */
    public function it_publishes_all_created_and_appended_events_if_not_inside_transaction(): void
    {
        [$event1, $event2, $event3, $event4] = $this->setupStubEvents();

        $eventBus = $this->prophesize(EventBus::class);

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $eventPublisher->attachToEventStore($this->eventStore);

        $eventBus->dispatch($event1)->shouldBeCalled();
        $eventBus->dispatch($event2)->shouldBeCalled();
        $eventBus->dispatch($event3)->shouldBeCalled();
        $eventBus->dispatch($event4)->shouldBeCalled();

        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
    }

    /**
     * @test
     */
    public function it_publishes_all_created_and_appended_events(): void
    {
        [$event1, $event2, $event3, $event4] = $this->setupStubEvents();

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldBeCalled();
        $eventBus->dispatch($event2)->shouldBeCalled();
        $eventBus->dispatch($event3)->shouldBeCalled();
        $eventBus->dispatch($event4)->shouldBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $eventPublisher->attachToEventStore($this->eventStore);

        $this->eventStore->beginTransaction();
        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_publishes_correctly_when_event_store_implements_can_control_transaction(): void
    {
        [$event1, $event2, $event3, $event4] = $this->setupStubEvents();

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldBeCalled();
        $eventBus->dispatch($event2)->shouldBeCalled();
        $eventBus->dispatch($event3)->shouldBeCalled();
        $eventBus->dispatch($event4)->shouldBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $eventPublisher->attachToEventStore($this->eventStore);

        $this->eventStore->beginTransaction();
        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        $this->eventStore->commit();
    }

    /**
     * @test
     */
    public function it_does_not_publish_when_event_store_rolls_back(): void
    {
        [$event1, $event2, $event3, $event4] = $this->setupStubEvents();

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldNotBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();
        $eventBus->dispatch($event3)->shouldNotBeCalled();
        $eventBus->dispatch($event4)->shouldNotBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $eventPublisher->attachToEventStore($this->eventStore);

        $this->eventStore->beginTransaction();
        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        $this->eventStore->rollback();
    }

    /**
     * @test
     */
    public function it_does_not_publish_when_non_transactional_event_store_throws_exception(): void
    {
        [$event1, $event2, $event3, $event4] = $this->setupStubEvents();

        $eventStore = $this->prophesize(EventStore::class);
        $eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])))->willThrow(StreamExistsAlready::with(new StreamName('test')))->shouldBeCalled();
        $eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]))->willThrow(new ConcurrencyException())->shouldBeCalled();
        $eventStore->appendTo(new StreamName('unknown'), new \ArrayIterator([$event3, $event4]))->willThrow(StreamNotFound::with(new StreamName('unknown')))->shouldBeCalled();

        $eventStore = new ActionEventEmitterEventStore($eventStore->reveal(), new ProophActionEventEmitter());

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldNotBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();
        $eventBus->dispatch($event3)->shouldNotBeCalled();
        $eventBus->dispatch($event4)->shouldNotBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $eventPublisher->attachToEventStore($eventStore);

        try {
            $eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $eventStore->appendTo(new StreamName('unknown'), new \ArrayIterator([$event3, $event4]));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * @return Message[]
     */
    private function setupStubEvents(): array
    {
        $event1 = $this->prophesize(Message::class)->reveal();
        $event2 = $this->prophesize(Message::class)->reveal();
        $event3 = $this->prophesize(Message::class)->reveal();
        $event4 = $this->prophesize(Message::class)->reveal();

        return [$event1, $event2, $event3, $event4];
    }
}
