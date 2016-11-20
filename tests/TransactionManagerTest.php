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
use Prooph\Common\Event\DefaultActionEvent;
use Prooph\Common\Event\DefaultListenerHandler;
use Prooph\Common\Event\ListenerHandler;
use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterAwareEventStore;
use Prooph\EventStore\CanControlTransactionActionEventEmitterAwareEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;

class TransactionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_handles_transactions(): void
    {
        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();
        $eventStoreMock->isInTransaction()->willReturn(true)->shouldBeCalled();
        $eventStoreMock->commit()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('a message')->to(function () {

        });
        $commandBus->utilize($router);

        $transactionManager->attach($commandBus->getActionEventEmitter());

        $commandBus->dispatch('a message');
    }

    /**
     * @test
     */
    public function it_rolls_back_transactions(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();
        $eventStoreMock->isInTransaction()->willReturn(true)->shouldBeCalled();
        $eventStoreMock->rollback()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('a message')->to(function () {
            throw new \RuntimeException('foo');
        });
        $commandBus->utilize($router);

        $transactionManager->attach($commandBus->getActionEventEmitter());

        $commandBus->dispatch('a message');
    }

    /**
     * @test


    public function it_adds_causation_id_and_causation_name_on_event_store_create_stream(): void
    {
        //Step 1: Track the command which will cause events
        $command = $this->prophesize(Message::class);

        $causationId = Uuid::uuid4();

        $command->uuid()->willReturn($causationId);
        $command->messageName()->willReturn('causation-message-name');

        $initializeActionEvent = $this->prophesize(ActionEvent::class);

        $initializeActionEvent->getParam(CommandBus::EVENT_PARAM_MESSAGE)->willReturn($command->reveal());

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        //Now the command is set as currentCommand internally and later used when new stream is going to be created
        $transactionManager->onInvokeHandler($initializeActionEvent->reveal());

        //Step 2: Prepare a new stream which is going to be created.
        //        The TransactionManager should respect immutability
        //        so we test that too.
        $recordedEvent = $this->prophesize(Message::class);
        $recordedEventCopy1 = $this->prophesize(Message::class);
        $recordedEventCopy2 = $this->prophesize(Message::class);

        $recordedEventCopy1->withAddedMetadata('causation_name', 'causation-message-name')->willReturn($recordedEventCopy2->reveal());
        $recordedEvent->withAddedMetadata('causation_id', $causationId->toString())->willReturn($recordedEventCopy1->reveal());

        $stream = new Stream(new StreamName('event_stream'), new \ArrayIterator([$recordedEvent->reveal()]));

        $createStreamActionEvent = $this->prophesize(ActionEvent::class);

        $createStreamActionEvent->getParam('stream')->willReturn($stream);

        $enrichedStream = null;
        $createStreamActionEvent->setParam('stream', Argument::type(Stream::class))
            ->will(function ($args) use (&$enrichedStream) {
                $enrichedStream = $args[1];
            });

        $transactionManager->onEventStoreCreateStream($createStreamActionEvent->reveal());

        $this->assertNotNull($enrichedStream);
        $this->assertEquals('event_stream', $enrichedStream->streamName()->toString());
        $this->assertCount(1, $enrichedStream->streamEvents());
        $this->assertSame($recordedEventCopy2->reveal(), $enrichedStream->streamEvents()[0]);
    }

    /**
     * @test
     *
    public function it_adds_causation_id_and_causation_name_on_event_store_append_to_stream(): void
    {
        //Step 1: Track the command which will cause events
        $command = $this->prophesize(Message::class);

        $causationId = Uuid::uuid4();

        $command->uuid()->willReturn($causationId);
        $command->messageName()->willReturn('causation-message-name');

        $initializeActionEvent = $this->prophesize(ActionEvent::class);

        $initializeActionEvent->getParam(CommandBus::EVENT_PARAM_MESSAGE)->willReturn($command->reveal());

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        //Now the command is set as currentCommand internally and later used when new stream is going to be created
        $transactionManager->onInvokeHandler($initializeActionEvent->reveal());

        //Step 2: Prepare a new stream which is going to be created.
        //        The TransactionManager should respect immutability
        //        so we test that too.
        $recordedEvent = $this->prophesize(Message::class);
        $recordedEventCopy1 = $this->prophesize(Message::class);
        $recordedEventCopy2 = $this->prophesize(Message::class);

        $recordedEventCopy1->withAddedMetadata('causation_name', 'causation-message-name')->willReturn($recordedEventCopy2->reveal());
        $recordedEvent->withAddedMetadata('causation_id', $causationId->toString())->willReturn($recordedEventCopy1->reveal());

        $appendToStreamActionEvent = $this->prophesize(ActionEvent::class);

        $appendToStreamActionEvent->getParam('streamEvents')->willReturn(new \ArrayIterator([$recordedEvent->reveal()]));

        $enrichedEvents = null;
        $appendToStreamActionEvent->setParam('streamEvents', Argument::any())
            ->will(function ($args) use (&$enrichedEvents) {
                $enrichedEvents = $args[1];
            });

        $transactionManager->onEventStoreAppendToStream($appendToStreamActionEvent->reveal());

        $this->assertNotNull($enrichedEvents);
        $this->assertInstanceOf(\ArrayIterator::class, $enrichedEvents);
        $this->assertEquals(1, count($enrichedEvents));
        $this->assertSame($recordedEventCopy2->reveal(), $enrichedEvents[0]);
    }

    /**
     * @test

    public function it_returns_early_on_event_store_create_stream_if_event_has_no_stream(): void
    {
        $createStreamActionEvent = $this->prophesize(ActionEvent::class);

        $createStreamActionEvent->getParam('stream')->willReturn(false);

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $this->assertNull($transactionManager->onEventStoreCreateStream($createStreamActionEvent->reveal()));
    }
*/

    private function getEventStoreObjectProphecy(): ObjectProphecy
    {
        $eventStoreMock = $this->prophesize(CanControlTransactionActionEventEmitterAwareEventStore::class);

        $eventStoreMock->getActionEventEmitter()->willReturn(new ProophActionEventEmitter());

        return $eventStoreMock;
    }
}
