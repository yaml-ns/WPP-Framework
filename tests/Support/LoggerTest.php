<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Support;

use Psr\Log\LogLevel;
use YamlNs\WppFramework\Support\Logger;

final class LoggerTest extends TestCase
{
    public function test_logger_is_psr3_compatible_and_uses_injected_handler(): void
    {
        $lines = [];
        $logger = new Logger(
            $this->container->get(\YamlNs\WppFramework\Core\PluginContext::class),
            true,
            LogLevel::WARNING,
            static function (string $line) use (&$lines): void {
                $lines[] = $line;
            }
        );

        $logger->info('Ignored');
        $logger->error('Failure: {reason}', ['reason' => 'boom']);

        $this->assertCount(1, $lines);
        $this->assertStringContainsString('ERROR', $lines[0]);
        $this->assertStringContainsString('Failure: boom', $lines[0]);
    }
}
