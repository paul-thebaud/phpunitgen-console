<?php

namespace PhpUnitGen\Console\Reporters;

/**
 * Class JsonReporter.
 *
 * @author  Paul Thébaud <paul.thebaud29@gmail.com>
 * @author  Killian Hascoët <killianh@live.fr>
 * @license MIT
 */
final class JsonReporter extends AbstractReporter
{
    /**
     * {@inheritdoc}
     */
    public function terminate(): int
    {
        $event = $this->terminateReport();

        $this->writeln(json_encode([
            'summary' => [
                'sources'   => $this->sources->count(),
                'successes' => $this->successes->count(),
                'warnings'  => $this->warnings->count(),
                'errors'    => $this->errors->count(),
                'time'      => $event->getDuration(),
                'memory'    => $event->getMemory(),
            ],
            'results' => $this->sources->mapWithKeys(fn (string $source) => [
                $source => $this->makeSourceResult($source),
            ])->all(),
        ], JSON_PRETTY_PRINT));

        return $this->determineExitCode();
    }

    /**
     * Make a result object for a given source.
     *
     * @param string $source
     *
     * @return array
     */
    private function makeSourceResult(string $source): array
    {
        if ($this->errors->get($source)) {
            return [
                'status'  => 'error',
                'message' => $this->errors->get($source)->getMessage(),
            ];
        }

        if ($this->warnings->get($source)) {
            return [
                'status'  => 'warning',
                'message' => $this->warnings->get($source),
            ];
        }

        return [
            'status'  => 'success',
            'path'    => $this->successes->get($source),
            'message' => 'Successful generation',
        ];
    }
}
