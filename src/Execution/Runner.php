<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Execution;

use PhpUnitGen\Console\Contracts\Config\ConfigResolver;
use PhpUnitGen\Console\Contracts\Config\ConsoleConfig;
use PhpUnitGen\Console\Contracts\Execution\ProcessHandler;
use PhpUnitGen\Console\Contracts\Execution\Runner as RunnerContract;
use PhpUnitGen\Console\Contracts\Files\FileBackup;
use PhpUnitGen\Console\Contracts\Files\Filesystem;
use PhpUnitGen\Console\Contracts\Files\SourcesResolver;
use PhpUnitGen\Console\Contracts\Files\TargetResolver;
use PhpUnitGen\Core\Container\CoreContainerFactory;
use PhpUnitGen\Core\Contracts\Config\Config;
use PhpUnitGen\Core\Contracts\Generators\DelegateTestGenerator;
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
     * @var FileBackup
     */
    protected $fileBackup;

    /**
     * @var ProcessHandler
     */
    protected $processHandler;

    /**
     * Runner constructor.
     *
     * @param ConfigResolver  $configResolver
     * @param Filesystem      $filesystem
     * @param SourcesResolver $sourcesResolver
     * @param TargetResolver  $targetResolver
     * @param FileBackup      $fileBackup
     * @param ProcessHandler  $processHandler
     */
    public function __construct(
        ConfigResolver $configResolver,
        Filesystem $filesystem,
        SourcesResolver $sourcesResolver,
        TargetResolver $targetResolver,
        FileBackup $fileBackup,
        ProcessHandler $processHandler
    ) {
        $this->configResolver = $configResolver;
        $this->filesystem = $filesystem;
        $this->sourcesResolver = $sourcesResolver;
        $this->targetResolver = $targetResolver;
        $this->fileBackup = $fileBackup;
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
            $target = $this->resolveTarget($input);

            $this->processHandler->handleStart($config, $sources);

            $application = $this->buildCoreApplication($config);

            $sources->each(function (string $source) use ($application, $config, $target) {
                $this->runGeneration($application, $config, $source, $target);
            });
        } catch (Throwable $exception) {
            $this->processHandler->handleCriticalError($exception);

            return 1;
        }

        $this->processHandler->handleEnd();

        return $this->determineExitCode();
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
        return $this->configResolver->resolve($input);
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
     * Resolve target from input and validate it is not an existing file.
     *
     * @param InputInterface $input
     *
     * @return string
     */
    protected function resolveTarget(InputInterface $input): string
    {
        $targetPath = $input->getArgument('target');

        if ($this->filesystem->isFile($targetPath)) {
            throw new InvalidArgumentException(
                'target cannot be an existing file'
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
            $reflectionClass = $application->getCodeParser()->parse(
                new StringSource($this->filesystem->read($sourcePath))
            );

            $testGenerator = $application->getTestGenerator();
            if ($testGenerator instanceof DelegateTestGenerator) {
                $testGenerator = $testGenerator->getDelegate($reflectionClass);
            }

            if (! $testGenerator->canGenerateFor($reflectionClass)) {
                $this->processHandler->handleWarning(
                    $sourcePath,
                    'cannot generate tests, file is an interface/anonymous class or does not contain any public method'
                );

                return;
            }

            $testClass = $testGenerator->generate($reflectionClass);
            $rendered = $application->getRenderer()
                ->visitTestClass($testClass)
                ->getRendered();

            $realTargetPath = $this->targetResolver->resolve(
                $testGenerator->getClassFactory(),
                $sourcePath,
                $targetPath
            );

            if ($this->filesystem->has($realTargetPath)) {
                if ($config->overwriteFiles() !== true) {
                    $this->processHandler->handleWarning(
                        $sourcePath,
                        "cannot generate tests to {$realTargetPath}, file exists and overwriting is disabled"
                    );

                    return;
                }

                if ($config->backupFiles() === true) {
                    $this->fileBackup->backup($realTargetPath);
                }
            }

            $this->filesystem->write($realTargetPath, $rendered->toString());
        } catch (Throwable $exception) {
            $this->processHandler->handleError($sourcePath, $exception);

            return;
        }

        $this->processHandler->handleSuccess($sourcePath, $realTargetPath);
    }

    /**
     * Determine the exit code to use depending on the process handler final state.
     *
     * @return int
     */
    protected function determineExitCode(): int
    {
        if ($this->processHandler->hasErrors()) {
            return 100;
        }

        if ($this->processHandler->hasWarnings()) {
            return 101;
        }

        return 0;
    }
}
