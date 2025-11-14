<?php

declare(strict_types=1);

namespace Reaperman\Report;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Reaperman\Analysis\AnalysisResult;

final class TableReporter
{
    public function __construct(private OutputInterface $output)
    {
    }

    /**
     * @param array<int,string> $ignore
     */
    public function render(AnalysisResult $result, string $root, array $ignore): void
    {
        if ($result->findings === []) {
            $this->output->writeln(sprintf('<info>No dead code found. Scanned %d PHP files.</info>', count($result->files)));

            if ($this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE && $result->files !== []) {
                $this->output->writeln('Scanned files:');
                foreach ($result->files as $file) {
                    $this->output->writeln(' - ' . $file);
                }
            }
            return;
        }

        $table = new Table($this->output);
        $table->setHeaders(['File', 'Symbol', 'Type', 'Line']);
        foreach ($result->findings as $f) {
            $table->addRow([$f['file'], $f['symbol'], $f['type'], (string) $f['line']]);
        }
        $table->render();
        $this->output->writeln(sprintf('<comment>Total findings: %d</comment>', $result->count()));
    }
}
