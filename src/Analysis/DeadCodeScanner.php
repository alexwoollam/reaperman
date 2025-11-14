<?php

declare(strict_types=1);

namespace Reaperman\Analysis;

use PhpParser\Error as PhpParserError;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Reaperman\Analysis\Ast\CollectVisitor;

final class DeadCodeScanner
{
    /**
     * @param string $root Root directory to scan
     * @param array<int,string> $ignore Directory basenames to ignore (e.g., vendor,node_modules)
     */
    public function analyze(string $root, array $ignore = []): AnalysisResult
    {
        $files = $this->gatherPhpFiles($root, $ignore);

        $factory = new ParserFactory();
        $mode = \defined(ParserFactory::class . '::PREFER_PHP8') ? ParserFactory::PREFER_PHP8 : ParserFactory::PREFER_PHP7;
        $parser = $factory->create($mode);
        $nameResolver = new NameResolver();

        $allClasses = [];
        $allFunctions = [];
        $methodRefs = [];
        $funcRefs = [];
        $instantiations = [];

        foreach ($files as $file) {
            try {
                $code = @file_get_contents($file);
                if ($code === false) {
                    continue;
                }
                $ast = $parser->parse($code);
                if ($ast === null) {
                    continue;
                }

                $traverser = new NodeTraverser();
                $traverser->addVisitor($nameResolver);
                $collector = new CollectVisitor($file);
                $traverser->addVisitor($collector);
                $traverser->traverse($ast);

                foreach ($collector->classes as $fqn => $meta) {
                    if (!isset($allClasses[$fqn])) {
                        $allClasses[$fqn] = $meta;
                    } else {
                        $allClasses[$fqn]['methods'] = $allClasses[$fqn]['methods'] + $meta['methods'];
                    }
                }
                foreach ($collector->functions as $fqn => $meta) {
                    $allFunctions[$fqn] = $meta;
                }
                foreach ($collector->methodReferences as $class => $methods) {
                    foreach ($methods as $m => $_) {
                        $methodRefs[$class][$m] = true;
                    }
                }
                foreach ($collector->functionReferences as $f => $_) {
                    $funcRefs[$f] = true;
                }
                foreach ($collector->instantiations as $c => $_) {
                    $instantiations[$c] = true;
                }
            } catch (PhpParserError $_e) {
                // Skip files that fail to parse; optionally log in verbose mode (not implemented here)
                continue;
            }
        }

        $findings = [];

        $magic = ['__construct','__destruct','__get','__set','__isset','__unset','__sleep','__wakeup','__toString','__invoke','__set_state','__clone','__debugInfo'];

        foreach ($allClasses as $classFqn => $meta) {
            foreach ($meta['methods'] as $methodName => $m) {
                if ($m['visibility'] === 'private' && !in_array(strtolower($methodName), $magic, true)) {
                    $isRef = isset($methodRefs[$classFqn][(string)$methodName]);
                    if (!$isRef) {
                        $findings[] = [
                            'file' => $meta['file'],
                            'symbol' => $classFqn . '::' . $methodName . '()',
                            'type' => 'unused_private_method',
                            'line' => (int) $m['line'],
                        ];
                    }
                }
            }
        }

        foreach ($allFunctions as $fqn => $meta) {
            if (!isset($funcRefs[$fqn])) {
                $findings[] = [
                    'file' => $meta['file'],
                    'symbol' => $fqn . '()',
                    'type' => 'unused_function',
                    'line' => (int) $meta['line'],
                ];
            }
        }

        return new AnalysisResult($files, $findings);
    }

    /**
     * @return array<int,string>
     */
    private function gatherPhpFiles(string $root, array $ignore): array
    {
        $result = [];
        $ignoreSet = array_flip($ignore);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $current) use ($ignoreSet): bool {
                    if ($current->isDir()) {
                        return !isset($ignoreSet[$current->getFilename()]);
                    }
                    return true;
                }
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && strtolower($file->getExtension()) === 'php') {
                $result[] = $file->getPathname();
            }
        }

        sort($result);
        return $result;
    }
}
