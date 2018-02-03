<?php
/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreBusBridge;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\TransactionalEventStore;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\AbstractPlugin;

/**
 * The transaction manager starts a new transaction when a command is dispatched on the command bus.
 * If the command dispatch finishes without an error the transaction manager commits the transaction otherwise it does a rollback.
 * Furthermore it attaches a listener to the event store create.pre and appendTo.pre action events with a low priority to
 * set causation_id as metadata for all domain events which are going to be persisted.
 */
final class TransactionManager extends AbstractPlugin
{
    /**
     * @var TransactionalEventStore
     */
    private $eventStore;

    public function __construct(TransactionalEventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function attachToMessageBus(MessageBus $messageBus): void
    {
        $this->listenerHandlers[] = $messageBus->attach(
            CommandBus::EVENT_DISPATCH,
            function (ActionEvent $event): void {
                $this->eventStore->beginTransaction();
            },
            CommandBus::PRIORITY_INVOKE_HANDLER + 1000
        );

        $this->listenerHandlers[] = $messageBus->attach(
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
        );
    }
}
