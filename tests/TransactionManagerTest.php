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
use Prooph\Common\Event\ListenerHandler;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;

/**
 * Class TransactionManagerTest
 *
 * @package ProophTest\EventStoreBusBridge
 */
final class TransactionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_attaches_itself_to_event_store_events(): void
    {
        $eventStoreMock = $this->prophesize(EventStore::class);

        $emitter = $this->prophesize(ActionEventEmitter::class);

        $createStreamListener = null;
        $appendToStreamListener = null;

        $emitter->attachListener('create.pre', Argument::any(), -1000)->will(
            function ($args) use (&$createStreamListener) {
                $createStreamListener = $args[1];
            }
        );
        $emitter->attachListener('appendTo.pre', Argument::any(), -1000)->will(
            function ($args) use (&$appendToStreamListener) {
                $appendToStreamListener = $args[1];
            }
        );

        $eventStoreMock->getActionEventEmitter()->willReturn($emitter->reveal());

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $this->assertEquals([$transactionManager, 'onEventStoreCreateStream'], $createStreamListener);
        $this->assertEquals([$transactionManager, 'onEventStoreAppendToStream'], $appendToStreamListener);
    }

    /**
     * @test
     */
    public function it_attaches_itself_to_command_bus_initialize_and_finalize_events(): void
    {
        $transactionManager = new TransactionManager();

        $commandBusEmitter = $this->prophesize(ActionEventEmitter::class);

        $commandBusEmitter->attachListener(CommandBus::EVENT_INVOKE_HANDLER, [$transactionManager, 'onInvokeHandler'], 1000)
            ->willReturn($this->prophesize(ListenerHandler::class)->reveal());
        $commandBusEmitter->attachListener(CommandBus::EVENT_FINALIZE, [$transactionManager, 'onFinalize'], 1000)
            ->willReturn($this->prophesize(ListenerHandler::class)->reveal());

        $transactionManager->attach($commandBusEmitter->reveal());
    }

    /**
     * @test
     */
    public function it_begins_a_transaction_on_command_dispatch_initialize(): void
    {
        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $actionEvent = $this->prophesize(ActionEvent::class);

        $actionEvent->getParam(CommandBus::EVENT_PARAM_MESSAGE)->willReturn("a message");

        $transactionManager->onInvokeHandler($actionEvent->reveal());
    }

    /**
     * @test
     */
    public function it_commits_a_transaction_on_command_dispatch_finalize_if_no_exception_was_thrown(): void
    {
        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->isInTransaction()->willReturn(true);

        $eventStoreMock->commit()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $actionEvent = $this->prophesize(ActionEvent::class);

        $actionEvent->getParam(CommandBus::EVENT_PARAM_EXCEPTION)->willReturn(null);

        $transactionManager->onFinalize($actionEvent->reveal());
    }

    /**
     * @test
     */
    public function it_rollback_a_transaction_on_command_dispatch_finalize_if_exception_was_thrown(): void
    {
        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->isInTransaction()->willReturn(true);

        $eventStoreMock->rollback()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $actionEvent = $this->prophesize(ActionEvent::class);

        $exception = $this->prophesize(\Exception::class);

        $actionEvent->getParam(CommandBus::EVENT_PARAM_EXCEPTION)->willReturn($exception->reveal());

        $transactionManager->onFinalize($actionEvent->reveal());
    }

    /**
     * @test
     */
    public function it_does_not_perform_rollback_after_transaction_commit(): void
    {
        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->isInTransaction()->willReturn(false);

        $eventStoreMock->rollback()->shouldNotBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $actionEvent = $this->prophesize(ActionEvent::class);

        $exception = $this->prophesize(\Exception::class);

        $actionEvent->getParam(CommandBus::EVENT_PARAM_EXCEPTION)->willReturn($exception->reveal());

        $transactionManager->onFinalize($actionEvent->reveal());
    }

    /**
     * @test
     */
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
        $this->assertEquals(1, count($enrichedStream->streamEvents()));
        $this->assertSame($recordedEventCopy2->reveal(), $enrichedStream->streamEvents()[0]);
    }

    /**
     * @test
     */
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
     */
    public function it_returns_early_on_event_store_create_stream_if_event_has_no_stream(): void
    {
        $createStreamActionEvent = $this->prophesize(ActionEvent::class);

        $createStreamActionEvent->getParam('stream')->willReturn(false);

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $this->assertNull($transactionManager->onEventStoreCreateStream($createStreamActionEvent->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_early_if_command_was_null_when_handling_events(): void
    {
        //Step 1: Create null command
        $command = null;

        $initializeActionEvent = $this->prophesize(ActionEvent::class);

        $initializeActionEvent->getParam(CommandBus::EVENT_PARAM_MESSAGE)->willReturn($command);

        $eventStoreMock = $this->getEventStoreObjectProphecy();

        $eventStoreMock->beginTransaction()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStoreMock->reveal());

        $transactionManager->onInvokeHandler($initializeActionEvent->reveal());

        $recordedEvent = $this->prophesize(Message::class);

        $recordedEvent->withAddedMetadata('causation_id', Argument::any())->shouldNotBeCalled();

        $stream = new Stream(new StreamName('event_stream'), new \ArrayIterator([$recordedEvent->reveal()]));

        $createStreamActionEvent = new DefaultActionEvent('test');
        $createStreamActionEvent->setParam('stream', $stream);

        $transactionManager->onEventStoreCreateStream($createStreamActionEvent);

        $this->assertEquals($stream, $createStreamActionEvent->getParam('stream'));
    }

    private function getEventStoreObjectProphecy(): ObjectProphecy
    {
        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);

        $eventStoreMock = $this->prophesize(EventStore::class);

        $eventStoreMock->getActionEventEmitter()->willReturn($actionEventEmitter->reveal());

        return $eventStoreMock;
    }
}
