<?php

/**
 * This file is part of prooph/event-store-bus-bridge.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreBusBridge\Container;

use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\EventBus;
use Psr\Container\ContainerInterface;

final class EventPublisherFactory
{
    /**
     * @var string
     */
    private $eventBusServiceName;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     EventPublisher::class => [EventPublisherFactory::class, 'event_bus_service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): EventPublisher
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $eventBusServiceName = EventBus::class)
    {
        $this->eventBusServiceName = $eventBusServiceName;
    }

    public function __invoke(ContainerInterface $container): EventPublisher
    {
        $eventBus = $container->get($this->eventBusServiceName);

        return new EventPublisher($eventBus);
    }
}
