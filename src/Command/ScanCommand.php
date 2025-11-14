<?php

declare(strict_types=1);

namespace Reaperman\Command;

use Reaperman\Analysis\DeadCodeScanner;
use Reaperman\Analysis\AnalysisResult;
use Reaperman\Report\TableReporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ScanCommand extends Command
{
    /** @var string|null */
    protected static $defaultDescription = 'Scan a project for dead code';

    public function __construct()
    {
        parent::__construct('reaperman:scan');
    }

    protected function configure(): void
    {
        $desc = self::$defaultDescription ?? 'Scan a project for dead code';
        $this->setDescription($desc);
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Root path to scan', getcwd())
            ->addOption('ignore', null, InputOption::VALUE_OPTIONAL, 'Comma-separated paths to ignore', 'vendor,node_modules,storage,cache')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format (table,json)', 'table')
            ->addOption('exit-nonzero-on-findings', null, InputOption::VALUE_NONE, 'Exit with non-zero if findings exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Easter egg: set REAPERMAN_EGG=1 to greet the Reaper
        if (getenv('REAPERMAN_EGG')) {
            $this->renderEasterEgg($output);
        }

        $pathOpt = $input->getOption('path');
        $cwd = getcwd();
        $path = is_string($pathOpt)
            ? $pathOpt
            : ($cwd !== false ? $cwd : '.');

        $ignoreOpt = $input->getOption('ignore');
        $ignoreCsv = is_string($ignoreOpt) ? $ignoreOpt : 'vendor,node_modules,storage,cache';
        $ignore = array_values(array_filter(array_map('trim', explode(',', $ignoreCsv)), static fn ($v) => $v !== ''));

        $formatOpt = $input->getOption('format');
        $format = is_string($formatOpt) ? $formatOpt : 'table';

        $exitOpt = $input->getOption('exit-nonzero-on-findings');
        $exitNonZero = is_bool($exitOpt) ? $exitOpt : false;

        $scanner = new DeadCodeScanner();
        $result = $scanner->analyze($path, $ignore);

        if ($format === 'json') {
            $output->writeln((string) json_encode([
                'count' => $result->count(),
                'filesScanned' => $result->files,
                'findings' => $result->findings,
            ], JSON_PRETTY_PRINT));
        } else {
            $reporter = new TableReporter($output);
            $reporter->render($result, $path, $ignore);
        }

        if ($exitNonZero && $result->count() > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function renderEasterEgg(OutputInterface $output): void
    {
        $art = [
            '      .-.',
            '     (o o)   Reap what you code...',
            '     | O \\',
            '      \\   \\   _',
            '       `~~~`  (reaperman)',
        ];
        foreach ($art as $line) {
            $output->writeln('<comment>' . $line . '</comment>');
        }
        $output->writeln('');
    }
}
