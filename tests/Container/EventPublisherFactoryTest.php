<?php
/*
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 8/30/15 - 6:20 PM
 */
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
    public function it_creates_an_event_publisher_using_the_default_service_name_for_getting_an_event_bus()
    {
        $eventBus = $this->prophesize(EventBus::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(EventBus::class)->willReturn($eventBus->reveal());

        $factory = new EventPublisherFactory();

        $eventPublisher = $factory($container->reveal());

        $this->assertInstanceOf(EventPublisher::class, $eventPublisher);
    }
}
