<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Support;

use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use YamlNs\WppFramework\Core\PluginContext;

final class Logger implements LoggerInterface
{
    /**
     * @var array<string, int>
     */
    private const LEVELS = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    private Closure $handler;

    /**
     * @param callable(string): void|null $handler
     */
    public function __construct(
        private readonly PluginContext $context,
        private readonly bool $enabled = true,
        private readonly string $minLevel = LogLevel::DEBUG,
        ?callable $handler = null
    ) {
        $this->handler = Closure::fromCallable($handler ?? 'error_log');
    }

    /**
     * @param array<string, mixed> $context
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $level = (string) $level;

        if (!$this->enabled || !$this->shouldLog($level)) {
            return;
        }

        ($this->handler)(sprintf(
            '[%s] %s: %s%s',
            $this->context->slug(),
            strtoupper($level),
            $this->interpolate((string) $message, $context),
            $this->contextSuffix($context)
        ));
    }

    private function shouldLog(string $level): bool
    {
        $current = self::LEVELS[$level] ?? self::LEVELS['info'];
        $minimum = self::LEVELS[$this->minLevel] ?? self::LEVELS['debug'];

        return $current >= $minimum;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function contextSuffix(array $context): string
    {
        if ($context === []) {
            return '';
        }

        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $context['exception'] = [
                'class' => $context['exception']::class,
                'message' => $context['exception']->getMessage(),
                'file' => $context['exception']->getFile(),
                'line' => $context['exception']->getLine(),
            ];
        }

        $encoded = json_encode($context);

        return $encoded === false ? '' : ' ' . $encoded;
    }
}
