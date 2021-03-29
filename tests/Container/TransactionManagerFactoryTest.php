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

namespace ProophTest\EventStoreBusBridge\Container;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\TransactionalEventStore;
use Prooph\EventStoreBusBridge\Container\TransactionManagerFactory;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\EventStoreBusBridge\TransactionManager;
use Psr\Container\ContainerInterface;

class TransactionManagerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_transaction_manager(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(EventStore::class)->willReturn($eventStore->reveal());

        $factory = new TransactionManagerFactory();

        $transactionManager = $factory($container->reveal());

        $this->assertInstanceOf(TransactionManager::class, $transactionManager);
    }

    /**
     * @test
     */
    public function it_creates_a_transaction_manager_via_callstatic(): void
    {
        $eventStore = $this->prophesize(TransactionalEventStore::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('foo')->willReturn($eventStore->reveal());

        $type = 'foo';
        $transactionManager = TransactionManagerFactory::$type($container->reveal());

        $this->assertInstanceOf(TransactionManager::class, $transactionManager);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_passed_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type = 'foo';
        TransactionManagerFactory::$type('invalid container');
    }
}
