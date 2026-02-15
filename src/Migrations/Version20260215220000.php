<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change slide<->slider relation from many-to-one to many-to-many with data migration.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['vanssa_sylius_slide', 'vanssa_sylius_slider'])) {
            return;
        }

        if (!$schemaManager->tablesExist(['vanssa_sylius_slide_slider'])) {
            $this->addSql('CREATE TABLE vanssa_sylius_slide_slider (slide_id INT NOT NULL, slider_id INT NOT NULL, INDEX IDX_DCBF312198E46B87 (slide_id), INDEX IDX_DCBF31212CCC9638 (slider_id), PRIMARY KEY(slide_id, slider_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE vanssa_sylius_slide_slider ADD CONSTRAINT FK_DCBF312198E46B87 FOREIGN KEY (slide_id) REFERENCES vanssa_sylius_slide (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE vanssa_sylius_slide_slider ADD CONSTRAINT FK_DCBF31212CCC9638 FOREIGN KEY (slider_id) REFERENCES vanssa_sylius_slider (id) ON DELETE CASCADE');
        }

        if ($schemaManager->introspectTable('vanssa_sylius_slide')->hasColumn('slider_id')) {
            $this->addSql('INSERT IGNORE INTO vanssa_sylius_slide_slider (slide_id, slider_id) SELECT id, slider_id FROM vanssa_sylius_slide WHERE slider_id IS NOT NULL');

            if ($this->foreignKeyExists('vanssa_sylius_slide', 'FK_876619A648D6CC1E')) {
                $this->addSql('ALTER TABLE vanssa_sylius_slide DROP FOREIGN KEY FK_876619A648D6CC1E');
            }

            if ($this->indexExists('vanssa_sylius_slide', 'IDX_876619A648D6CC1E')) {
                $this->addSql('DROP INDEX IDX_876619A648D6CC1E ON vanssa_sylius_slide');
            }

            if ($this->indexExists('vanssa_sylius_slide', 'idx_876619a648d6cc1e')) {
                $this->addSql('DROP INDEX idx_876619a648d6cc1e ON vanssa_sylius_slide');
            }

            $this->addSql('ALTER TABLE vanssa_sylius_slide DROP COLUMN slider_id');
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index",
            ['table' => $table, 'index' => $index],
        ) > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            ['table' => $table, 'constraint' => $constraint],
        ) > 0;
    }
}
