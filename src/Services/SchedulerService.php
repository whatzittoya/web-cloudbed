<?php

declare(strict_types=1);

namespace App\Services;

class SchedulerService
{
    private string $logPath;

    public function __construct(
        private readonly string $scriptPath,
        string $rootPath,
        private readonly string $tag = 'quinos:pull_reservations',
    ) {
        $logDir = $rootPath . '/var/log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logPath = $logDir . '/pull_reservations.log';
    }

    public function getStatus(): array
    {
        $line = $this->findCronLine();

        if ($line === null) {
            return ['enabled' => false, 'expression' => null, 'schedule' => null];
        }

        $parts = preg_split('/\s+/', ltrim($line));
        $expression = implode(' ', array_slice($parts ?? [], 0, 5));

        return [
            'enabled' => true,
            'expression' => $expression,
            'schedule' => $this->expressionToSchedule($expression),
        ];
    }

    public function enable(string $schedule): void
    {
        $expression = self::scheduleToExpression($schedule);

        if ($expression === null) {
            throw new \InvalidArgumentException("Invalid schedule: $schedule");
        }

        $crontab = $this->removeCronLine($this->readCrontab());
        $crontab[] = sprintf(
            '%s php %s >> %s 2>&1 # %s',
            $expression,
            $this->scriptPath,
            $this->logPath,
            $this->tag,
        );
        $this->writeCrontab($crontab);
    }

    public function disable(): void
    {
        $crontab = $this->removeCronLine($this->readCrontab());
        $this->writeCrontab($crontab);
    }

    public static function scheduleToExpression(string $schedule): ?string
    {
        $map = [
            'every_15min'  => '*/15 * * * *',
            'every_30min'  => '*/30 * * * *',
            'every_hour'   => '0 * * * *',
            'every_2hours' => '0 */2 * * *',
            'every_6hours' => '0 */6 * * *',
            'every_12hours' => '0 */12 * * *',
        ];

        if (isset($map[$schedule])) {
            return $map[$schedule];
        }

        if (preg_match('/^daily_(\d{1,2}):(\d{2})$/', $schedule, $m)) {
            return sprintf('%d %d * * *', (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    private function expressionToSchedule(string $expression): string
    {
        $map = [
            '*/15 * * * *'  => 'every_15min',
            '*/30 * * * *'  => 'every_30min',
            '0 * * * *'     => 'every_hour',
            '0 */2 * * *'   => 'every_2hours',
            '0 */6 * * *'   => 'every_6hours',
            '0 */12 * * *'  => 'every_12hours',
        ];

        if (isset($map[$expression])) {
            return $map[$expression];
        }

        if (preg_match('/^(\d+) (\d+) \* \* \*$/', $expression, $m)) {
            return sprintf('daily_%02d:%02d', (int) $m[2], (int) $m[1]);
        }

        return $expression;
    }

    private function findCronLine(): ?string
    {
        foreach ($this->readCrontab() as $line) {
            if (str_contains($line, '# ' . $this->tag)) {
                return trim($line);
            }
        }

        return null;
    }

    private function readCrontab(): array
    {
        $output = shell_exec('crontab -l 2>/dev/null');

        if ($output === null || trim($output) === '') {
            return [];
        }

        return explode("\n", rtrim($output));
    }

    private function writeCrontab(array $lines): void
    {
        $lines = array_values(array_filter($lines, fn ($l) => trim($l) !== ''));
        $content = $lines === [] ? '' : implode("\n", $lines) . "\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'crontab_');

        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file for crontab.');
        }

        file_put_contents($tmpFile, $content);

        if ($content === '') {
            shell_exec('crontab -r 2>/dev/null');
        } else {
            shell_exec('crontab ' . escapeshellarg($tmpFile));
        }

        unlink($tmpFile);
    }

    private function removeCronLine(array $crontab): array
    {
        return array_values(
            array_filter($crontab, fn ($l) => !str_contains($l, '# ' . $this->tag))
        );
    }
}
