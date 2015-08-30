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
use Prooph\EventStoreBusBridge\TransactionManager;

/**
 * Class TransactionManagerFactory
 *
 * @package Prooph\EventStoreBusBridge\Container
 */
final class TransactionManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $eventStore = $container->get('prooph.event_store');
        return new TransactionManager($eventStore);
    }
}
