<?php
/**
 * This file is part of the prooph/service-bus.
 * (c) 2014-%year% prooph software GmbH <contact@prooph.de>
 * (c) 2015-%year% Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
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
