<?php declare(strict_types=1);

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
    protected static $defaultDescription = 'Scan a project for dead code';

    public function __construct()
    {
        parent::__construct('reaperman:scan');
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
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

        $path = (string) $input->getOption('path');
        $ignore = array_filter(array_map('trim', explode(',', (string) $input->getOption('ignore'))));
        $format = (string) $input->getOption('format');
        $exitNonZero = (bool) $input->getOption('exit-nonzero-on-findings');

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
