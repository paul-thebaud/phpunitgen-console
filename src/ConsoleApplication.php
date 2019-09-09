<?php

declare(strict_types=1);

namespace PhpUnitGen\Console;

use PackageVersions\Versions;
use PhpUnitGen\Console\Commands\RunCommand;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConsoleApplication.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class ConsoleApplication extends SymfonyApplication
{
    /**
     * The console application version.
     */
    public const VERSION = '1.0.0-alpha';

    /**
     * ConsoleApplication constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'PhpUnitGen',
            self::VERSION." (core version: {$this->getCoreVersion()})"
        );

        $this->add(new RunCommand());

        $this->setDefaultCommand('run', true);
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
     * Get the PhpUnitGen Core code version.
     *
     * @return string
     */
    protected function getCoreVersion(): string
    {
        $version = Versions::getVersion('phpunitgen/core');

        return substr($version, 0, strrpos($version, '@'));
    }
}
