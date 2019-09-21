<?php

declare(strict_types=1);

namespace Tests\PhpUnitGen\Console;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Class TestCase.
 */
class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;
}
