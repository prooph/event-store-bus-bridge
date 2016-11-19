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

namespace ProophTest\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use Prooph\EventStoreBusBridge\Container\EventPublisherFactory;
use Prooph\EventStoreBusBridge\EventPublisher;
use Prooph\ServiceBus\EventBus;

/**
 * Class EventPublisherFactoryTest
 *
 * @package ProophTest\EventStoreBusBridge\Container
 */
final class EventPublisherFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_creates_an_event_publisher_using_the_default_service_name_for_getting_an_event_bus(): void
    {
        $eventBus = $this->prophesize(EventBus::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(EventBus::class)->willReturn($eventBus->reveal());

        $factory = new EventPublisherFactory();

        $eventPublisher = $factory($container->reveal());

        $this->assertInstanceOf(EventPublisher::class, $eventPublisher);
    }
}
