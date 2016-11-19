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
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\DefaultListenerHandler;
use Prooph\Common\Event\ListenerHandler;
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
    public function it_publishes_all_recorded_events(): void
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
            $function = function ($args) use (&$commitPostListener, &$function): ListenerHandler {
                $commitPostListener = $args[1];
                return new DefaultListenerHandler($function);
            }
        );

        $eventStore = $this->prophesize(EventStore::class);

        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal());

        $eventPublisher->setUp($eventStore->reveal());

        $this->assertEquals([$eventPublisher, 'onEventStoreCommitPost'], $commitPostListener);

        $commitPostEvent = $this->prophesize(ActionEvent::class);

        $commitPostEvent->getParam('recordedEvents', new \ArrayIterator())->willReturn([$event1->reveal(), $event2->reveal()]);

        $eventPublisher->onEventStoreCommitPost($commitPostEvent->reveal());
    }
}
