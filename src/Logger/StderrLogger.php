<?php

declare(strict_types=1);

namespace App\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

final class StderrLogger extends AbstractLogger
{
    private static array $levelMap = [
        LogLevel::EMERGENCY => 'EMERGENCY',
        LogLevel::ALERT     => 'ALERT',
        LogLevel::CRITICAL  => 'CRITICAL',
        LogLevel::ERROR     => 'ERROR',
        LogLevel::WARNING   => 'WARNING',
        LogLevel::NOTICE    => 'NOTICE',
        LogLevel::INFO      => 'INFO',
        LogLevel::DEBUG     => 'DEBUG',
    ];

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $label = self::$levelMap[$level] ?? strtoupper((string) $level);
        $msg   = $this->interpolate((string) $message, $context);

        $extra = array_filter($context, static fn($k) => $k !== 'exception', ARRAY_FILTER_USE_KEY);
        $suffix = $extra !== [] ? ' ' . json_encode($extra, JSON_UNESCAPED_UNICODE) : '';

        fwrite(STDERR, sprintf('[%s] %s%s' . PHP_EOL, $label, $msg, $suffix));
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }
        return strtr($message, $replace);
    }
}
