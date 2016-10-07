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
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\ServiceBus\EventBus;

/**
 * Class EventPublisherFactory
 *
 * @package Prooph\EventStoreBusBridge\Container
 */
class EventPublisherFactory
{
    /**
     * @param ContainerInterface $container
     * @return EventPublisher
     */
    final public function __invoke(ContainerInterface $container)
    {
        $eventBus = $container->get($this->getEventBusServiceName());

        return new EventPublisher($eventBus);
    }

    /**
     * Return service name of the event bus
     *
     * Override this method if event bus is available with another service name in the container.
     *
     * @return string
     */
    protected function getEventBusServiceName()
    {
        return EventBus::class;
    }
}
