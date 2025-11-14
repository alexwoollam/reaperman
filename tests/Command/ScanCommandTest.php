<?php declare(strict_types=1);

namespace Reaperman\Tests\Command;

use PHPUnit\Framework\TestCase;
use Reaperman\Command\ScanCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class ScanCommandTest extends TestCase
{
    private function makeTester(): CommandTester
    {
        $app = new Application('reaperman-test', 'test');
        $app->add(new ScanCommand());
        $command = $app->find('reaperman:scan');
        return new CommandTester($command);
    }

    public function testTableOutputAndExitCode(): void
    {
        $tester = $this->makeTester();
        $path = __DIR__ . '/../Fixtures/Project1';
        $status = $tester->execute([
            '--path' => $path,
            '--ignore' => 'vendor',
            '--format' => 'table',
            '--exit-nonzero-on-findings' => true,
        ]);

        $out = $tester->getDisplay();
        $this->assertNotSame(0, $status, 'Exit code should be non-zero when findings exist');
        $this->assertStringContainsString('Total findings', $out);
        $this->assertStringContainsString('unused_private_method', $out);
        $this->assertStringContainsString('unused_function', $out);
    }

    public function testJsonOutputStructure(): void
    {
        $tester = $this->makeTester();
        $path = __DIR__ . '/../Fixtures/Project1';
        $tester->execute([
            '--path' => $path,
            '--format' => 'json',
        ]);

        $json = $tester->getDisplay();
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('filesScanned', $data);
        $this->assertArrayHasKey('findings', $data);
        $this->assertGreaterThanOrEqual(2, $data['count']);
        $this->assertNotEmpty($data['filesScanned']);
    }

    public function testVerboseListsFiles(): void
    {
        $tester = $this->makeTester();
        $path = __DIR__ . '/../Fixtures/Project1';
        $tester->execute([
            '--path' => $path,
            '--ignore' => 'vendor',
            '--format' => 'table',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $out = $tester->getDisplay();
        $this->assertStringContainsString('Scanned files:', $out);
        $this->assertStringContainsString('functions.php', $out);
        $this->assertStringContainsString('ClassA.php', $out);
    }
}

