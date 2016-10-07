<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-%year% prooph software GmbH <contact@prooph.de>
 * (c) 2015-%year% Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStoreBusBridge;

use Iterator;
use ArrayIterator;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\Stream\Stream;
use Prooph\ServiceBus\CommandBus;

/**
 * TransactionManager
 *
 * The transaction manager starts a new transaction when a command is dispatched on the command bus.
 * If the command dispatch finishes without an error the transaction manager commits the transaction otherwise it does a rollback.
 * Furthermore it attaches a listener to the event store create.pre and appendTo.pre action events with a low priority to
 * set causation_id as metadata for all domain events which are going to be persisted.
 *
 * @package Prooph\EventStoreBusBridge
 */
final class TransactionManager implements Plugin, ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var Command
     */
    private $currentCommand;

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function setUp(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->eventStore->getActionEventEmitter()->attachListener('create.pre', [$this, 'onEventStoreCreateStream'], -1000);
        $this->eventStore->getActionEventEmitter()->attachListener('appendTo.pre', [$this, 'onEventStoreAppendToStream'], -1000);
    }

    /**
     * Attaches itself to the command dispatch of the application command bus
     *
     * @param ActionEventEmitter $emitter
     *
     * @return void
     */
    public function attach(ActionEventEmitter $emitter)
    {
        //Attach with a high priority, so that it invokes before the handler is invoked
        $this->trackHandler($emitter->attachListener(CommandBus::EVENT_INVOKE_HANDLER, [$this, 'onInvokeHandler'], 1000));
        //Attach with a high priority to rollback transaction early in case of an error
        $this->trackHandler($emitter->attachListener(CommandBus::EVENT_FINALIZE, [$this, 'onFinalize'], 1000));
    }

    /**
     * This method takes domain events as argument which are going to be added to the event stream and
     * adds the causation_id (command UUID) and causation_name (name of the command which has caused the events)
     * as metadata to each event.
     *
     * @param Iterator $recordedEvents
     * @return Iterator
     */
    private function handleRecordedEvents(Iterator $recordedEvents)
    {
        if (is_null($this->currentCommand) || ! $this->currentCommand instanceof Message) {
            return $recordedEvents;
        }

        $causationId = $this->currentCommand->uuid()->toString();
        $causationName = $this->currentCommand->messageName();

        $enrichedRecordedEvents = [];

        foreach ($recordedEvents as $recordedEvent) {
            $recordedEvent = $recordedEvent->withAddedMetadata('causation_id', $causationId);
            $recordedEvent = $recordedEvent->withAddedMetadata('causation_name', $causationName);

            $enrichedRecordedEvents[] = $recordedEvent;
        }

        return new ArrayIterator($enrichedRecordedEvents);
    }

    /**
     * Begin event store transaction before command gets handled
     *
     * @param ActionEvent $actionEvent
     */
    public function onInvokeHandler(ActionEvent $actionEvent)
    {
        $this->currentCommand = $actionEvent->getParam(CommandBus::EVENT_PARAM_MESSAGE);

        $this->eventStore->beginTransaction();
    }

    /**
     * Check if exception an exception was thrown. If so rollback event store transaction
     * otherwise commit it.
     *
     * @param ActionEvent $actionEvent
     */
    public function onFinalize(ActionEvent $actionEvent)
    {
        if ($this->eventStore->isInTransaction()) {
            if ($actionEvent->getParam(CommandBus::EVENT_PARAM_EXCEPTION)) {
                $this->eventStore->rollback();
            } else {
                $this->eventStore->commit();
            }
        }

        $this->currentCommand = null;
    }

    /**
     * Add event metadata on event store createStream
     *
     * @param ActionEvent $createEvent
     */
    public function onEventStoreCreateStream(ActionEvent $createEvent)
    {
        $stream = $createEvent->getParam('stream');

        if (! $stream instanceof Stream) {
            return;
        }

        $streamEvents = $stream->streamEvents();
        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $createEvent->setParam('stream', new Stream($stream->streamName(), $streamEvents));
    }

    /**
     * Add event metadata on event store appendToStream
     *
     * @param ActionEvent $appendToStreamEvent
     */
    public function onEventStoreAppendToStream(ActionEvent $appendToStreamEvent)
    {
        $streamEvents = $appendToStreamEvent->getParam('streamEvents');
        $streamEvents = $this->handleRecordedEvents($streamEvents);

        $appendToStreamEvent->setParam('streamEvents', $streamEvents);
    }
}
