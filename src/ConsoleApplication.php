<?php

declare(strict_types=1);

namespace PhpUnitGen\Console;

use PhpUnitGen\Console\Commands\RunCommand;
use PhpUnitGen\Console\Container\ConsoleContainerFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

/**
 * Class ConsoleApplication.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleApplication extends Application
{
    /**
     * The current phpunitgen-console version.
     */
    public const VERSION = '6.1.0';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * ConsoleApplication constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct(
            'PhpUnitGen',
            self::VERSION
        );

        $this->add($container->get(RunCommand::class));

        $this->setDefaultCommand('phpunitgen', true);
    }

    /**
     * Create a console application.
     *
     * @return ConsoleApplication
     */
    public static function make(): self
    {
        return new static(ConsoleContainerFactory::make());
    }
}
