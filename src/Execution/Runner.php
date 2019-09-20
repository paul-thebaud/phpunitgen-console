<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Execution;

use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Execution\ProcessHandler as ProcessHandlerContract;
use PhpUnitGen\Console\Contracts\Execution\Runner as RunnerContract;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver;
use PhpUnitGen\Console\Contracts\Files\TargetResolver;
use PhpUnitGen\Core\Container\CoreContainerFactory;
use PhpUnitGen\Core\Contracts\Config\Config;
use PhpUnitGen\Core\CoreApplication;
use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use PhpUnitGen\Core\Parsers\Sources\StringSource;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Class Runner.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
class Runner implements RunnerContract
{
    /**
     * @var ConfigResolver
     */
    protected $configResolver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var SourcesResolver
     */
    protected $sourcesResolver;

    /**
     * @var TargetResolver
     */
    protected $targetResolver;

    /**
     * @var ProcessHandlerContract
     */
    protected $processHandler;

    /**
     * Runner constructor.
     *
     * @param ConfigResolver        $configResolver
     * @param Filesystem            $filesystem
     * @param SourcesResolver       $sourcesResolver
     * @param TargetResolver        $targetResolver
     * @param ProcessHandlerContract $processHandler
     */
    public function __construct(
        ConfigResolver $configResolver,
        Filesystem $filesystem,
        SourcesResolver $sourcesResolver,
        TargetResolver $targetResolver,
        ProcessHandlerContract $processHandler
    ) {
        $this->configResolver = $configResolver;
        $this->filesystem = $filesystem;
        $this->sourcesResolver = $sourcesResolver;
        $this->targetResolver = $targetResolver;
        $this->processHandler = $processHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->processHandler->initialize($output);

        try {
            $config = $this->resolveConfig($input);
            $sources = $this->resolveSources($input, $config);
            $target = $this->resolveTarget($input, $sources);

            $this->processHandler->handleStart($sources);

            $application = $this->buildCoreApplication($config);

            $sources->each(function (string $source) use ($application, $config, $target) {
                $this->runGeneration($application, $config, $source, $target);
            });
        } catch (Throwable $exception) {
            $this->processHandler->handleCriticalError($exception);

            return 1;
        }

        $this->processHandler->handleEnd();

        return 1;
    }

    /**
     * Resolve the config from input.
     *
     * @param InputInterface $input
     *
     * @return ConsoleConfig
     */
    protected function resolveConfig(InputInterface $input): ConsoleConfig
    {
        $inputConfig = $input->getOption('config');

        return $this->configResolver->resolve($inputConfig);
    }

    /**
     * Build the core application using config.
     *
     * @param Config $config
     *
     * @return CoreApplication
     */
    protected function buildCoreApplication(Config $config): CoreApplication
    {
        $container = CoreContainerFactory::make($config);

        return new CoreApplication($container);
    }

    /**
     * Resolve sources from input and config.
     *
     * @param InputInterface $input
     * @param ConsoleConfig  $config
     *
     * @return Collection
     */
    protected function resolveSources(InputInterface $input, ConsoleConfig $config): Collection
    {
        $inputSource = $input->getArgument('source');

        $sources = $this->sourcesResolver->resolve($config, $inputSource);

        if ($sources->isEmpty()) {
            throw new InvalidArgumentException(
                'no source to generate tests for'
            );
        }

        return $sources;
    }

    /**
     * Resolve target from input and validate it can be used for sources.
     *
     * @param InputInterface $input
     * @param Collection     $sources
     *
     * @return string
     */
    protected function resolveTarget(InputInterface $input, Collection $sources): string
    {
        $targetPath = $input->getArgument('target');

        if ($sources->count() > 1 && $this->filesystem->isFile($targetPath)) {
            throw new InvalidArgumentException(
                'target must be a directory if there is multiple sources'
            );
        }

        return $targetPath;
    }

    /**
     * Run generation for source.
     *
     * @param CoreApplication $application
     * @param ConsoleConfig   $config
     * @param string          $sourcePath
     * @param string          $targetPath
     */
    protected function runGeneration(
        CoreApplication $application,
        ConsoleConfig $config,
        string $sourcePath,
        string $targetPath
    ): void {
        try {
            $source = new StringSource($this->filesystem->read($sourcePath));

            $rendered = $application->run($source);

            $realTargetPath = $this->targetResolver->resolve($sourcePath, $targetPath);
            if ($config->overwriteFiles() !== true
                && $this->filesystem->has($realTargetPath)
            ) {
                $this->processHandler->handleWarning(
                    $sourcePath,
                    "cannot generate tests to {$realTargetPath}, file exists and overwriting is disabled"
                );

                return;
            }

            $this->filesystem->write($realTargetPath, $rendered->toString());
        } catch (Throwable $exception) {
            $this->processHandler->handleError($sourcePath, $exception);

            return;
        }

        $this->processHandler->handleSuccess($sourcePath, $realTargetPath);
    }
}
