<?php

declare(strict_types=1);

namespace PhpUnitGen\Console;

use PackageVersions\Versions;
use PhpUnitGen\Console\Commands\RunCommand;
use PhpUnitGen\Console\Container\ConsoleContainerFactory;
use PhpUnitGen\Core\Helpers\Str;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            "{$this->getConsoleVersion()} (core version: {$this->getCoreVersion()})"
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

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (! $output->isQuiet()) {
            $output->writeln($this->getLongVersion()."\n");
        }

        return parent::doRun($input, $output);
    }

    /**
     * Get the PhpUnitGen Console version.
     *
     * @return string
     */
    protected function getConsoleVersion(): string
    {
        return $this->getPackagistVersion('phpunitgen/console');
    }

    /**
     * Get the PhpUnitGen Core version.
     *
     * @return string
     */
    protected function getCoreVersion(): string
    {
        return $this->getPackagistVersion('phpunitgen/core');
    }

    /**
     * Get the given package version.
     *
     * @param string $package
     *
     * @return string
     */
    protected function getPackagistVersion(string $package): string
    {
        $version = Versions::getVersion($package);

        return Str::beforeLast('@', $version);
    }
}
