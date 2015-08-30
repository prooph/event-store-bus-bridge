<?php
/*
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/30/15 - 6:06 PM
 */
namespace ProophTest\EventStoreBusBridge;

use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\ServiceBus\EventBus;
use Prophecy\Argument;

/**
 * Class EventPublisherTest
 *
 * @package ProophTest\EventStoreBusBridge
 */
final class EventPublisherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_publishes_all_recorded_events()
    {
        $event1 = $this->prophesize(Message::class);
        $event2 = $this->prophesize(Message::class);

        $eventBus = $this->prophesize(EventBus::class);

        $eventBus->dispatch($event1->reveal())->shouldBeCalled();
        $eventBus->dispatch($event2->reveal())->shouldBeCalled();

        $eventPublisher = new EventPublisher($eventBus->reveal());

        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);

        $commitPostListener = null;

        $actionEventEmitter->attachListener('commit.post', Argument::any())->will(
            function ($args) use (&$commitPostListener) {
                $commitPostListener = $args[1];
            }
        );

        $eventStore = $this->prophesize(EventStore::class);

        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal());

        $eventPublisher->setUp($eventStore->reveal());

        $this->assertEquals([$eventPublisher, 'onEventStoreCommitPost'], $commitPostListener);

        $commitPostEvent = $this->prophesize(ActionEvent::class);

        $commitPostEvent->getParam('recordedEvents', [])->willReturn([$event1->reveal(), $event2->reveal()]);

        $eventPublisher->onEventStoreCommitPost($commitPostEvent->reveal());
    }
}
