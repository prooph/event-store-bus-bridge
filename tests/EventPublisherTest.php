<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-%year% prooph software GmbH <contact@prooph.de>
 * (c) 2015-%year% Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\EventBus;

class EventPublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InMemoryEventStore
     */
    private $eventStore;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore(new ProophActionEventEmitter());
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

        $eventPublisher->setUp($this->eventStore);

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

        $eventPublisher->setUp($this->eventStore);

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

        $eventPublisher->setUp($this->eventStore);

        $commitPostEvent = $this->prophesize(ActionEvent::class);

        $commitPostEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn([$event1, $event2]);

        $this->eventStore->beginTransaction();
        $this->eventStore->create(new Stream(new StreamName('test'), new \ArrayIterator([$event1, $event2])));
        $this->eventStore->appendTo(new StreamName('test'), new \ArrayIterator([$event3, $event4]));
        $this->eventStore->rollback();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_event_store_not_implementing_action_event_emitter_aware_is_used(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);

        $eventPublisher = new EventPublisher($this->prophesize(EventBus::class)->reveal());
        $eventPublisher->setUp($eventStore->reveal());
    }
}
