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
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\Common\Event\ActionEventListenerAggregate;
use Prooph\Common\Event\DetachAggregateHandlers;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\TransactionalEventStore;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
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
final class TransactionManager implements ActionEventListenerAggregate
{
    use DetachAggregateHandlers;

    /**
     * @var TransactionalEventStore
     */
    private $eventStore;

    public function __construct(EventStore $eventStore)
    {
        if (! $eventStore instanceof TransactionalEventStore) {
            throw new InvalidArgumentException(
                sprintf(
                    'EventStore must implement %s',
                    TransactionalEventStore::class
                )
            );
        }

        $this->eventStore = $eventStore;
    }

    public function attach(ActionEventEmitter $eventEmitter): void
    {
        $this->trackHandler(
            $eventEmitter->attachListener(
                CommandBus::EVENT_DISPATCH,
                function (ActionEvent $event): void {
                    $this->eventStore->beginTransaction();
                },
                CommandBus::PRIORITY_INVOKE_HANDLER + 1000
            )
        );

        $this->trackHandler(
            $eventEmitter->attachListener(
                CommandBus::EVENT_FINALIZE,
                function (ActionEvent $event): void {
                    if ($this->eventStore->inTransaction()) {
                        if ($event->getParam(CommandBus::EVENT_PARAM_EXCEPTION)) {
                            $this->eventStore->rollback();
                        } else {
                            $this->eventStore->commit();
                        }
                    }
                },
                1000
            )
        );
    }
}
