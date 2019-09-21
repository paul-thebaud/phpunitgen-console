<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Container;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;

/**
 * Class ConsoleContainerFactory.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleContainerFactory
{
    /**
     * Make a container for the given configuration.
     *
     * @return ContainerInterface
     */
    public static function make(): ContainerInterface
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->addServiceProvider(
            new ConsoleServiceProvider()
        );

        return $container;
    }
}
