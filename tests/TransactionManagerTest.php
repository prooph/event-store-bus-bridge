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

namespace ProophTest\EventStoreBusBridge;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\TransactionalEventStore;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\Exception\MessageDispatchException;
use Prooph\ServiceBus\Plugin\Router\CommandRouter;
use Prophecy\PhpUnit\ProphecyTrait;

class TransactionManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_handles_transactions(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);

        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->inTransaction()->willReturn(true)->shouldBeCalled();
        $eventStore->commit()->shouldBeCalled();

        $transactionManager = new TransactionManager($eventStore->reveal());

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('a message')->to(function () {
        });

        $router->attachToMessageBus($commandBus);

        $transactionManager->attachToMessageBus($commandBus);

        $commandBus->dispatch('a message');
    }

    /**
     * @test
     */
    public function it_rolls_back_transactions(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);

        $eventStore->beginTransaction()->shouldBeCalled();
        $eventStore->inTransaction()->willReturn(true)->shouldBeCalled();
        $eventStore->rollback()->shouldBeCalled();

        $transactionManager = new TransactionManager($eventStore->reveal());

        $commandBus = new CommandBus();
        $router = new CommandRouter();
        $router->route('a message')->to(function () {
            throw new \RuntimeException('foo');
        });

        $router->attachToMessageBus($commandBus);

        $transactionManager->attachToMessageBus($commandBus);

        try {
            $commandBus->dispatch('a message');
        } catch (MessageDispatchException $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertEquals('foo', $e->getPrevious()->getMessage());

            return;
        }

        $this->fail('No exception thrown');
    }
}
