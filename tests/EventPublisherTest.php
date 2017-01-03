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

use PHPUnit\Framework\TestCase;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
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
    public function it_publishes_all_created_and_appended_events(): void
    {
        $event1 = $this->prophesize(Message::class)->reveal();
        $event2 = $this->prophesize(Message::class)->reveal();
        $event3 = $this->prophesize(Message::class)->reveal();
        $event4 = $this->prophesize(Message::class)->reveal();

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
        $event1 = $this->prophesize(Message::class)->reveal();
        $event2 = $this->prophesize(Message::class)->reveal();
        $event3 = $this->prophesize(Message::class)->reveal();
        $event4 = $this->prophesize(Message::class)->reveal();

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
        $event1 = $this->prophesize(Message::class)->reveal();
        $event2 = $this->prophesize(Message::class)->reveal();
        $event3 = $this->prophesize(Message::class)->reveal();
        $event4 = $this->prophesize(Message::class)->reveal();

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1)->shouldNotBeCalled();
        $eventBus->dispatch($event2)->shouldNotBeCalled();
        $eventBus->dispatch($event3)->shouldNotBeCalled();
        $eventBus->dispatch($event4)->shouldNotBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $commitPostListener = null;

        $eventPublisher->attachToEventStore($this->eventStore);

        $commitPostEvent = $this->prophesize(ActionEvent::class);

        $commitPostEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn([$event1, $event2]);

        $this->eventStore->beginTransaction();
        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        $this->eventStore->rollback();
    }
}
