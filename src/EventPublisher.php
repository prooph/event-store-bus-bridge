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

namespace Prooph\EventStoreBusBridge;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterAwareEventStore;
use Prooph\EventStore\CanControlTransactionActionEventEmitterAwareEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\EventBus;

final class EventPublisher implements Plugin
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var \Iterator[]
     */
    private $cachedEventStreams = [];

    public function __construct(EventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function setUp(EventStore $eventStore): void
    {
        if (! $eventStore instanceof ActionEventEmitterAwareEventStore) {
            throw new InvalidArgumentException(
                sprintf(
                    'EventStore must implement %s',
                    ActionEventEmitterAwareEventStore::class
                )
            );
        }

        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_APPEND_TO,
            function (ActionEvent $event) use ($eventStore): void {
                $recordedEvents = $event->getParam('streamEvents', new \ArrayIterator());

                if (! $eventStore instanceof CanControlTransactionActionEventEmitterAwareEventStore) {
                    foreach ($recordedEvents as $recordedEvent) {
                        $this->eventBus->dispatch($recordedEvent);
                    }
                } else {
                    $this->cachedEventStreams[] = $recordedEvents;
                }
            }
        );
        $eventStore->getActionEventEmitter()->attachListener(
            ActionEventEmitterAwareEventStore::EVENT_CREATE,
            function (ActionEvent $event) use ($eventStore): void {
                $stream = $event->getParam('stream');
                $recordedEvents = $stream->streamEvents();

                if (! $eventStore instanceof CanControlTransactionActionEventEmitterAwareEventStore) {
                    foreach ($recordedEvents as $recordedEvent) {
                        $this->eventBus->dispatch($recordedEvent);
                    }
                } else {
                    $this->cachedEventStreams[] = $recordedEvents;
                }
            }
        );

        if ($eventStore instanceof CanControlTransactionActionEventEmitterAwareEventStore) {
            $eventStore->getActionEventEmitter()->attachListener(
                CanControlTransactionActionEventEmitterAwareEventStore::EVENT_COMMIT,
                function (ActionEvent $event): void {
                    foreach ($this->cachedEventStreams as $stream) {
                        foreach ($stream as $recordedEvent) {
                            $this->eventBus->dispatch($recordedEvent);
                        }
                    }
                    $this->cachedEventStreams = [];
                }
            );
            $eventStore->getActionEventEmitter()->attachListener(
                CanControlTransactionActionEventEmitterAwareEventStore::EVENT_ROLLBACK,
                function (ActionEvent $event): void {
                    $this->cachedEventStreams = [];
                }
            );
        }
    }
}
