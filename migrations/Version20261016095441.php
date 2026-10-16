<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261016095441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AD8A54A9E7927C74 ON admin_user (email)');
        $this->addSql('CREATE TABLE setting (name VARCHAR(64) NOT NULL, value CLOB NOT NULL, PRIMARY KEY (name))');
        $this->addSql('CREATE TABLE transmission_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sender_name VARCHAR(200) NOT NULL, sender_email VARCHAR(255) NOT NULL, sent_at DATETIME NOT NULL)');
        $this->addSql('CREATE INDEX IDX_80F87A1396E4F388 ON transmission_log (sent_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin_user');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE transmission_log');
    }
}
