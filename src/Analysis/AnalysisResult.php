<?php declare(strict_types=1);

namespace Reaperman\Analysis;

/**
 * Lightweight value object holding scan results.
 * @psalm-type Finding=array{file:string,symbol:string,type:string,line:int}
 */
final class AnalysisResult
{
    /**
     * @param array<int,string> $files
     * @param array<int, array{file:string,symbol:string,type:string,line:int}> $findings
     */
    public function __construct(
        public array $files,
        public array $findings
    ) {
    }

    public function count(): int
    {
        return count($this->findings);
    }
}

