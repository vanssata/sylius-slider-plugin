<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add channel_codes JSON column to slide for per-slide channel targeting.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide'])) {
            return;
        }

        if (!$schemaManager->introspectTable('vanssa_sylius_slide')->hasColumn('channel_codes')) {
            $this->addSql("ALTER TABLE vanssa_sylius_slide ADD channel_codes JSON NOT NULL COMMENT '(DC2Type:json)'");
            $this->addSql("UPDATE vanssa_sylius_slide SET channel_codes = '[]'");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide'])) {
            return;
        }

        if ($schemaManager->introspectTable('vanssa_sylius_slide')->hasColumn('channel_codes')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide DROP channel_codes');
        }
    }
}
