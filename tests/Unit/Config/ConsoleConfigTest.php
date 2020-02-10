<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console\Unit\Config;

use PhpUnitGen\Console\Config\ConsoleConfig;
use PhpUnitGen\Core\Generators\Tests\DelegateTestGenerator;
use Tests\PhpUnitGen\Console\TestCase;

/**
 * Class ConsoleConfigTest.
 *
 * @covers \PhpUnitGen\Console\Config\ConsoleConfig
 */
class ConsoleConfigTest extends TestCase
{
    public function testWhenDefaultConfiguration(): void
    {
        $this->assertSame([
            'overwriteFiles'      => false,
            'backupFiles'         => true,
            'excludedFiles'       => [],
            'includedFiles'       => [
                '\.php$',
            ],
            'generateOnMake'      => true,
            'automaticGeneration' => true,
            'implementations'     => DelegateTestGenerator::implementations(),
            'baseNamespace'       => 'App',
            'baseTestNamespace'   => 'Tests',
            'testCase'            => 'Tests\\TestCase',
            'excludedMethods'     => [
                '__construct',
                '__destruct',
            ],
            'mergedPhpDoc'        => [
                'author',
                'copyright',
                'license',
                'version',
            ],
            'phpDoc'              => [],
            'options'             => [
                'context' => 'laravel',
            ],
        ], ConsoleConfig::make()->toArray());
    }

    public function testWhenCompleteConfiguration(): void
    {
        $this->assertSame([
            'overwriteFiles'      => true,
            'backupFiles'         => false,
            'excludedFiles'       => [
                'config\.php',
            ],
            'includedFiles'       => [
                'blade\.php$',
            ],
            'generateOnMake'      => false,
            'automaticGeneration' => false,
            'implementations'     => [],
            'baseNamespace'       => 'App\\',
            'baseTestNamespace'   => 'App\\Tests\\',
            'testCase'            => 'App\\Tests\\TestCase',
            'excludedMethods'     => [],
            'mergedPhpDoc'        => [],
            'phpDoc'              => ['@author John Doe'],
            'options'             => ['custom' => 'option'],
        ], ConsoleConfig::make([
            'overwriteFiles'      => true,
            'backupFiles'         => false,
            'excludedFiles'       => [
                'config\.php',
            ],
            'includedFiles'       => [
                'blade\.php$',
            ],
            'generateOnMake'      => false,
            'automaticGeneration' => false,
            'implementations'     => [],
            'baseNamespace'       => 'App\\',
            'baseTestNamespace'   => 'App\\Tests\\',
            'testCase'            => 'App\\Tests\\TestCase',
            'excludedMethods'     => [],
            'mergedPhpDoc'        => [],
            'phpDoc'              => ['@author John Doe'],
            'options'             => ['custom' => 'option'],
        ])->toArray());
    }

    public function testGetters(): void
    {
        $config = ConsoleConfig::make([
            'overwriteFiles'      => true,
            'backupFiles'         => false,
            'excludedFiles'       => [
                'config\.php',
            ],
            'includedFiles'       => [
                'blade\.php$',
            ],
            'generateOnMake'      => false,
            'automaticGeneration' => false,
            'implementations'     => [],
            'baseNamespace'       => 'App\\',
            'baseTestNamespace'   => 'App\\Tests\\',
            'testCase'            => 'App\\Tests\\TestCase',
            'excludedMethods'     => [],
            'mergedPhpDoc'        => [],
            'phpDoc'              => ['@author John Doe'],
            'options'             => ['custom' => 'option'],
        ]);

        $this->assertSame(true, $config->overwriteFiles());
        $this->assertSame(false, $config->backupFiles());
        $this->assertSame([
            'config\.php',
        ], $config->excludedFiles());
        $this->assertSame([
            'blade\.php$',
        ], $config->includedFiles());
        $this->assertSame(false, $config->generateOnMake());
        $this->assertSame(false, $config->automaticGeneration());
        $this->assertSame([], $config->implementations());
        $this->assertSame('App\\', $config->baseNamespace());
        $this->assertSame('App\\Tests\\', $config->baseTestNamespace());
        $this->assertSame('App\\Tests\\TestCase', $config->testCase());
        $this->assertSame([], $config->excludedMethods());
        $this->assertSame([], $config->mergedPhpDoc());
        $this->assertSame(['@author John Doe'], $config->phpDoc());
        $this->assertSame(['custom' => 'option'], $config->options());
        $this->assertSame('option', $config->getOption('custom'));
        $this->assertSame(null, $config->getOption('unknown'));
        $this->assertSame('foo bar', $config->getOption('unknown', 'foo bar'));
    }

    public function testPath(): void
    {
        $config = ConsoleConfig::make();

        $this->assertNull($config->getPath());

        $config->setPath('my/config/path.php');

        $this->assertSame('my/config/path.php', $config->getPath());
    }
}
