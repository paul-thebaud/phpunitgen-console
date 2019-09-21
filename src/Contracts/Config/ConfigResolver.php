<?php

declare(strict_types=1);

namespace PhpUnitGen\Console\Contracts\Config;

use PhpUnitGen\Core\Exceptions\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Interface ConfigResolver.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
interface ConfigResolver
{
    /**
     * Try to resolve the given config from the input, or resolve defaults.
     *
     * @param InputInterface|null $input
     *
     * @return ConsoleConfig
     *
     * @throws InvalidArgumentException
     */
    public function resolve(?InputInterface $input = null): ConsoleConfig;
}
