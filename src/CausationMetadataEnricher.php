<?php

/**
 * This file is part of prooph/event-store-bus-bridge.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreBusBridge;

use ArrayIterator;
use Assert\Assertion;
use Prooph\Common\Event\ActionEvent;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Metadata\MetadataEnricher;
use Prooph\EventStore\Plugin\Plugin as EventStorePlugin;
use Prooph\EventStore\Stream;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\Plugin as MessageBusPlugin;

final class CausationMetadataEnricher implements MetadataEnricher, EventStorePlugin, MessageBusPlugin
{
    /**
     * @var string
     */
    private $causationIdKey;

    /**
     * @var string
     */
    private $causationNameKey;

    /**
     * @var Message
     */
    private $currentCommand;

    /**
     * @var array
     */
    private $eventStoreListeners = [];

    /**
     * @var array
     */
    private $messageBusListeners = [];

    /**
     * @param string $causationIdKey
     * @param string $causationNameKey
     */
    public function __construct(string $causationIdKey = '_causation_id', string $causationNameKey = '_causation_name')
    {
        Assertion::notEmpty($causationIdKey);
        Assertion::notEmpty($causationNameKey);

        $this->causationIdKey = $causationIdKey;
        $this->causationNameKey = $causationNameKey;
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->eventStoreListeners[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event): void {
                if (! $this->currentCommand instanceof Message) {
                    return;
                }

                $recordedEvents = $event->getParam('streamEvents');

                $enrichedRecordedEvents = [];

                foreach ($recordedEvents as $recordedEvent) {
                    $enrichedRecordedEvents[] = $this->enrich($recordedEvent);
                }

                $event->setParam('streamEvents', new ArrayIterator($enrichedRecordedEvents));
            },
            1000
        );

        $this->eventStoreListeners[] = $eventStore->attach(
            ActionEventEmitterEventStore::EVENT_CREATE,
            function (ActionEvent $event): void {
                if (! $this->currentCommand instanceof Message) {
                    return;
                }

                $stream = $event->getParam('stream');
                $recordedEvents = $stream->streamEvents();

                $enrichedRecordedEvents = [];

                foreach ($recordedEvents as $recordedEvent) {
                    $enrichedRecordedEvents[] = $this->enrich($recordedEvent);
                }

                $stream = new Stream(
                    $stream->streamName(),
                    new ArrayIterator($enrichedRecordedEvents),
                    $stream->metadata()
                );

                $event->setParam('stream', $stream);
            },
            1000
        );
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        foreach ($this->eventStoreListeners as $listenerHandler) {
            $eventStore->detach($listenerHandler);
        }

        $this->eventStoreListeners = [];
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $this->messageBusListeners[] = $messageBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event): void {
                $this->currentCommand = $event->getParam(CommandBus::EVENT_PARAM_MESSAGE);
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 1000
        );

        $this->messageBusListeners[] = $messageBus->attach(
            CommandBus::EVENT_FINALIZE,
            function (ActionEvent $event): void {
                $this->currentCommand = null;
            },
            1000
        );
    }

    public function detachFromMessageBus(MessageBus $messageBus): void
    {
        foreach ($this->messageBusListeners as $listenerHandler) {
            $messageBus->detach($listenerHandler);
        }

        $this->messageBusListeners = [];
    }

    public function enrich(Message $message): Message
    {
        $message = $message->withAddedMetadata($this->causationIdKey, $this->currentCommand->uuid()->toString());
        $message = $message->withAddedMetadata($this->causationNameKey, $this->currentCommand->messageName());

        return $message;
    }
}
