<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407162334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dashboard_read_entries (id CHAR(36) NOT NULL, site_url VARCHAR(191) NOT NULL, site_name VARCHAR(255) NOT NULL, total_page_views BIGINT NOT NULL, unique_visitors INT NOT NULL, bounce_rate NUMERIC(5, 2) NOT NULL, avg_load_time_ms INT NOT NULL, status VARCHAR(20) NOT NULL, last_recorded_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_7726AC6BEB748E (site_url), INDEX idx_dre_status (status), INDEX idx_dre_total_page_views (total_page_views), INDEX idx_dre_unique_visitors (unique_visitors), INDEX idx_dre_last_recorded_at (last_recorded_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE site_metrics (id CHAR(36) NOT NULL, site_url VARCHAR(191) NOT NULL, site_name VARCHAR(255) NOT NULL, page_views INT NOT NULL, unique_visitors INT NOT NULL, bounce_rate NUMERIC(5, 2) NOT NULL, load_time_ms INT NOT NULL, recorded_at DATETIME NOT NULL, INDEX idx_sm_site_url (site_url), INDEX idx_sm_recorded_at (recorded_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE dashboard_read_entries');
        $this->addSql('DROP TABLE site_metrics');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
