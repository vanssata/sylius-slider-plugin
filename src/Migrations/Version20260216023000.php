<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused base title and description columns from slide; translated fields remain in slide translation table.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide'])) {
            return;
        }

        $table = $schemaManager->introspectTable('vanssa_sylius_slide');

        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide DROP title');
        }

        if ($table->hasColumn('description')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide DROP description');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide'])) {
            return;
        }

        $table = $schemaManager->introspectTable('vanssa_sylius_slide');

        if (!$table->hasColumn('title')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide ADD title VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('description')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide ADD description LONGTEXT DEFAULT NULL');
        }
    }
}
