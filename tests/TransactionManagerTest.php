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
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
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
        $eventStore = $this->getEventStoreObjectProphecy();

        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->isInTransaction()->willReturn(true)->shouldBeCalled();
        $eventStore->commit()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStore->reveal());

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

        $eventStore = $this->getEventStoreObjectProphecy();

        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->isInTransaction()->willReturn(true)->shouldBeCalled();
        $eventStore->rollback()->shouldBeCalled();

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStore->reveal());

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
     */
    public function it_throws_exception_when_non_can_control_transaction_action_event_emitter_aware_event_store_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $eventStore = $this->prophesize(EventStore::class);

        $transactionManager = new TransactionManager();

        $transactionManager->setUp($eventStore->reveal());
    }

    private function getEventStoreObjectProphecy(): ObjectProphecy
    {
        $eventStore = $this->prophesize(CanControlTransactionActionEventEmitterAwareEventStore::class);

        $eventStore->getActionEventEmitter()->willReturn(new ProophActionEventEmitter());

        return $eventStore;
    }
}
