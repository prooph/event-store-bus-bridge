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

namespace Prooph\EventStoreBusBridge\Container;

use Prooph\EventStore\EventStore;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\EventStoreBusBridge\TransactionManager;
use Psr\Container\ContainerInterface;

final class TransactionManagerFactory
{
    /**
     * @var string
     */
    private $eventStoreServiceName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     TransactionManager::class => [TransactionManagerFactory::class, 'event_store_service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): TransactionManager
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $eventStoreServiceName = EventStore::class)
    {
        $this->eventStoreServiceName = $eventStoreServiceName;
    }

    public function __invoke(ContainerInterface $container): TransactionManager
    {
        return new TransactionManager($container->get($this->eventStoreServiceName));
    }
}
