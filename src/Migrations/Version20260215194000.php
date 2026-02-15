<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure slider/slide translation tables exist and use locale_code column.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['vanssa_sylius_slider']) && !$schemaManager->tablesExist(['vanssa_sylius_slider_translation'])) {
            $this->addSql('CREATE TABLE vanssa_sylius_slider_translation (id INT AUTO_INCREMENT NOT NULL, slider_id INT NOT NULL, locale_code VARCHAR(16) NOT NULL, name VARCHAR(255) DEFAULT NULL, settings JSON NOT NULL, INDEX IDX_3F40008748D6CC1E (slider_id), UNIQUE INDEX uniq_slider_locale (slider_id, locale_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE vanssa_sylius_slider_translation ADD CONSTRAINT FK_3F40008748D6CC1E FOREIGN KEY (slider_id) REFERENCES vanssa_sylius_slider (id) ON DELETE CASCADE');
        }

        if ($schemaManager->tablesExist(['vanssa_sylius_slide']) && !$schemaManager->tablesExist(['vanssa_sylius_slide_translation'])) {
            $this->addSql('CREATE TABLE vanssa_sylius_slide_translation (id INT AUTO_INCREMENT NOT NULL, slide_id INT NOT NULL, locale_code VARCHAR(16) NOT NULL, name VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, button_label VARCHAR(255) DEFAULT NULL, url VARCHAR(1024) DEFAULT NULL, slide_cover VARCHAR(1024) DEFAULT NULL, slide_cover_mobile VARCHAR(1024) DEFAULT NULL, slide_cover_tablet VARCHAR(1024) DEFAULT NULL, content_settings JSON NOT NULL, slide_settings JSON NOT NULL, INDEX IDX_95A50D9998E46B87 (slide_id), UNIQUE INDEX uniq_slide_locale (slide_id, locale_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE vanssa_sylius_slide_translation ADD CONSTRAINT FK_95A50D9998E46B87 FOREIGN KEY (slide_id) REFERENCES vanssa_sylius_slide (id) ON DELETE CASCADE');
        }

        if ($schemaManager->tablesExist(['vanssa_sylius_slider_translation']) && !$schemaManager->introspectTable('vanssa_sylius_slider_translation')->hasColumn('locale_code')) {
            if ($schemaManager->introspectTable('vanssa_sylius_slider_translation')->hasColumn('locale')) {
                $this->addSql('ALTER TABLE vanssa_sylius_slider_translation CHANGE locale locale_code VARCHAR(16) NOT NULL');
            } else {
                $this->addSql('ALTER TABLE vanssa_sylius_slider_translation ADD locale_code VARCHAR(16) NOT NULL');
            }
        }

        if ($schemaManager->tablesExist(['vanssa_sylius_slide_translation']) && !$schemaManager->introspectTable('vanssa_sylius_slide_translation')->hasColumn('locale_code')) {
            if ($schemaManager->introspectTable('vanssa_sylius_slide_translation')->hasColumn('locale')) {
                $this->addSql('ALTER TABLE vanssa_sylius_slide_translation CHANGE locale locale_code VARCHAR(16) NOT NULL');
            } else {
                $this->addSql('ALTER TABLE vanssa_sylius_slide_translation ADD locale_code VARCHAR(16) NOT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
