<?php declare(strict_types=1);

namespace Reaperman\Tests\Analysis;

use PHPUnit\Framework\TestCase;
use Reaperman\Analysis\DeadCodeScanner;
use Reaperman\Analysis\AnalysisResult;

final class DeadCodeScannerTest extends TestCase
{
    public function testAnalyzeReturnsResult(): void
    {
        $scanner = new DeadCodeScanner();
        $result = $scanner->analyze(__DIR__, []);
        $this->assertInstanceOf(AnalysisResult::class, $result);
        $this->assertIsArray($result->files);
        $this->assertIsArray($result->findings);
    }

    public function testFindsUnusedMembersInFixture(): void
    {
        $scanner = new DeadCodeScanner();
        $fixture = __DIR__ . '/../Fixtures/Project1';
        $result = $scanner->analyze($fixture, []);

        $types = array_unique(array_map(static fn($f) => $f['type'], $result->findings));
        $this->assertContains('unused_private_method', $types);
        $this->assertContains('unused_function', $types);
    }
}
