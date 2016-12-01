<?php
/**
 * This file is part of the prooph/event-store-bus-bridge.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreBusBridge\Container;

use Interop\Container\ContainerInterface;
use Prooph\EventStoreBusBridge\CausationMetadataEnricher;
use Prooph\EventStoreBusBridge\Container\CausationMetadataEnricherFactory;
use Prooph\EventStoreBusBridge\Exception\InvalidArgumentException;
use Prooph\ServiceBus\CommandBus;
use Prophecy\Argument;

class CausationMetadataEnricherFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_creates_a_causation_metadata_enricher(): void
    {
        $commandBus = $this->prophesize(CommandBus::class);

        $commandBus->utilize(Argument::type(CausationMetadataEnricher::class))->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(CommandBus::class)->willReturn($commandBus->reveal());

        $factory = new CausationMetadataEnricherFactory();

        $causationMetadataEnricher = $factory($container->reveal());

        $this->assertInstanceOf(CausationMetadataEnricher::class, $causationMetadataEnricher);
    }

    /**
     * @test
     */
    public function it_creates_a_causation_metadata_enricher_via_callstatic(): void
    {
        $commandBus = $this->prophesize(CommandBus::class);

        $commandBus->utilize(Argument::type(CausationMetadataEnricher::class))->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('foo')->willReturn($commandBus->reveal());

        $type = 'foo';
        $causationMetadataEnricher = CausationMetadataEnricherFactory::$type($container->reveal());

        $this->assertInstanceOf(CausationMetadataEnricher::class, $causationMetadataEnricher);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_passed_to_callstatic(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $type = 'foo';
        CausationMetadataEnricherFactory::$type('invalid container');
    }
}
