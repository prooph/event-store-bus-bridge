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
use Prooph\EventStoreBusBridge\Container\CausationMetadataEnricherFactory;
use Prooph\EventStoreBusBridge\CausationMetadataEnricher;
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

        $CausationMetadataEnricher = $factory($container->reveal());

        $this->assertInstanceOf(CausationMetadataEnricher::class, $CausationMetadataEnricher);
    }
}
