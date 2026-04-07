<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Batch-inserts synthetic site metrics into dashboard_read_entries.
 * Default target: 100 000 rows in 1 000-row batches to avoid OOM.
 *
 * Usage:
 *   php bin/console app:dashboard:seed
 *   php bin/console app:dashboard:seed --count=500000
 */
#[AsCommand(
    name:        'app:dashboard:seed',
    description: 'Seeds dashboard_read_entries with synthetic site-analytics data',
)]
final class SeedDashboardDataCommand extends Command
{
    private const BATCH_SIZE = 1_000;
    private const STATUSES   = ['active', 'active', 'active', 'inactive', 'degraded']; // weighted
    private const TLDS       = ['.com', '.net', '.org', '.io', '.dev', '.app', '.co'];

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'count',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of rows to seed',
            '100000',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');

        if ($count < 1) {
            $io->error('--count must be a positive integer.');
            return Command::FAILURE;
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM dashboard_read_entries',
        );

        if ($existing > 0) {
            if (!$io->confirm("{$existing} rows already exist. Truncate and re-seed?", false)) {
                $io->info('Seeding cancelled.');
                return Command::SUCCESS;
            }
            $this->connection->executeStatement('TRUNCATE TABLE dashboard_read_entries');
            $io->text('Table truncated.');
        }

        $io->title("Seeding {$count} dashboard entries …");

        $progressBar = new ProgressBar($output, $count);
        $progressBar->setFormat('debug');
        $progressBar->start();

        $batch = [];
        $total = 0;

        for ($i = 1; $i <= $count; $i++) {
            $batch[] = $this->buildRow($i);

            if (count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($batch);
                $total += count($batch);
                $progressBar->advance(count($batch));
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->insertBatch($batch);
            $total += count($batch);
            $progressBar->advance(count($batch));
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success("Seeded {$total} rows into dashboard_read_entries.");

        return Command::SUCCESS;
    }

    private function buildRow(int $index): array
    {
        $tld    = self::TLDS[array_rand(self::TLDS)];
        $domain = 'site-' . str_pad((string) $index, 7, '0', STR_PAD_LEFT);

        return [
            'id'               => $this->generateUuid(),
            'site_url'         => "https://{$domain}{$tld}",
            'site_name'        => ucwords(str_replace('-', ' ', $domain)),
            'total_page_views' => random_int(1_000, 60_000_000),
            'unique_visitors'  => random_int(500, 15_000_000),
            'bounce_rate'      => round(mt_rand(500, 9_500) / 100, 2),
            'avg_load_time_ms' => random_int(80, 9_000),
            'status'           => self::STATUSES[array_rand(self::STATUSES)],
            'last_recorded_at' => date(
                'Y-m-d H:i:s',
                strtotime('-' . random_int(0, 730) . ' days'),
            ),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** Single multi-row INSERT — ~10x faster than individual inserts. */
    private function insertBatch(array $rows): void
    {
        $placeholders = implode(
            ',',
            array_fill(0, count($rows), '(?,?,?,?,?,?,?,?,?,?)'),
        );

        $values = [];
        foreach ($rows as $row) {
            array_push(
                $values,
                $row['id'],
                $row['site_url'],
                $row['site_name'],
                $row['total_page_views'],
                $row['unique_visitors'],
                $row['bounce_rate'],
                $row['avg_load_time_ms'],
                $row['status'],
                $row['last_recorded_at'],
                $row['updated_at'],
            );
        }

        $this->connection->executeStatement(
            "INSERT INTO dashboard_read_entries
                 (id, site_url, site_name, total_page_views, unique_visitors,
                  bounce_rate, avg_load_time_ms, status, last_recorded_at, updated_at)
             VALUES {$placeholders}",
            $values,
        );
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
