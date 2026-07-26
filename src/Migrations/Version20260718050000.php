<?php

declare(strict_types=1);

namespace VanssaSyliusSliderPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin-managed style presets (vanssa_sylius_style_preset) with source slides for slider presets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vanssa_sylius_style_preset (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, type VARCHAR(16) NOT NULL, label VARCHAR(255) NOT NULL, settings JSON NOT NULL, mockup_image VARCHAR(1024) DEFAULT NULL, enabled TINYINT(1) NOT NULL, position INT NOT NULL, UNIQUE INDEX UNIQ_VANSSA_STYLE_PRESET_CODE (code), INDEX IDX_VANSSA_STYLE_PRESET_TYPE_ENABLED (type, enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vanssa_sylius_style_preset_slide (preset_id INT NOT NULL, slide_id INT NOT NULL, INDEX IDX_VANSSA_STYLE_PRESET_SLIDE_PRESET (preset_id), INDEX IDX_VANSSA_STYLE_PRESET_SLIDE_SLIDE (slide_id), PRIMARY KEY(preset_id, slide_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vanssa_sylius_style_preset_slide ADD CONSTRAINT FK_VANSSA_STYLE_PRESET_SLIDE_PRESET FOREIGN KEY (preset_id) REFERENCES vanssa_sylius_style_preset (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vanssa_sylius_style_preset_slide ADD CONSTRAINT FK_VANSSA_STYLE_PRESET_SLIDE_SLIDE FOREIGN KEY (slide_id) REFERENCES vanssa_sylius_slide (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vanssa_sylius_style_preset_slide');
        $this->addSql('DROP TABLE vanssa_sylius_style_preset');
    }
}
