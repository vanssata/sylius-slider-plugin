<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216032000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused translated title and description columns from slide translations; responsive text is stored in slide_settings JSON.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide_translation'])) {
            return;
        }

        $table = $schemaManager->introspectTable('vanssa_sylius_slide_translation');

        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide_translation DROP title');
        }

        if ($table->hasColumn('description')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide_translation DROP description');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['vanssa_sylius_slide_translation'])) {
            return;
        }

        $table = $schemaManager->introspectTable('vanssa_sylius_slide_translation');

        if (!$table->hasColumn('title')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide_translation ADD title VARCHAR(255) DEFAULT NULL');
        }

        if (!$table->hasColumn('description')) {
            $this->addSql('ALTER TABLE vanssa_sylius_slide_translation ADD description LONGTEXT DEFAULT NULL');
        }
    }
}
