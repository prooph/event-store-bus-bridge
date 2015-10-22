<?php
/*
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/30/15 - 2:16 PM
 */
namespace ProophTest\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\EventStore;
use Prooph\EventStoreBusBridge\Container\TransactionManagerFactory;
use Prooph\EventStoreBusBridge\TransactionManager;

/**
 * Class TransactionManagerFactoryTest
 *
 * @package ProophTest\EventStoreBusBridge\Container
 */
final class TransactionManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_creates_a_transaction_manager()
    {
        $actionEventEmitter = $this->prophesize(ActionEventEmitter::class);
        $eventStore = $this->prophesize(EventStore::class);

        $eventStore->getActionEventEmitter()->willReturn($actionEventEmitter->reveal());

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(EventStore::class)->willReturn($eventStore->reveal());

        $factory = new TransactionManagerFactory();

        $transactionManager = $factory($container->reveal());

        $this->assertInstanceOf(TransactionManager::class, $transactionManager);
    }
}
