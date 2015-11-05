<?php
/*
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/30/15 - 2:14 PM
 */
namespace Prooph\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\EventStore;
use Prooph\EventStoreBusBridge\TransactionManager;
use Prooph\ServiceBus\CommandBus;

/**
 * Class TransactionManagerFactory
 *
 * @package Prooph\EventStoreBusBridge\Container
 */
final class TransactionManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $commandBus = $container->get(CommandBus::class);

        $transactionManager = new TransactionManager();

        $commandBus->utilize($transactionManager);

        return $transactionManager;
    }
}
