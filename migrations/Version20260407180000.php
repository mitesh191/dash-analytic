<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds composite index (status, total_page_views) to dashboard_read_entries.
 *
 * Rationale: the most common dashboard query filters rows by status and then
 * orders by total_page_views. MySQL can satisfy both the WHERE and the ORDER BY
 * from a single index scan using this composite index, eliminating the filesort
 * that occurs when using only the single-column idx_dre_status index.
 */
final class Version20260407180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite index (status, total_page_views) on dashboard_read_entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE INDEX idx_dre_status_page_views ON dashboard_read_entries (status, total_page_views)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_dre_status_page_views ON dashboard_read_entries');
    }
}
