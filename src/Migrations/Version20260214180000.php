<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create slider and slide tables inspired by BlurElysiumSlider concepts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE acme_sylius_slider (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, settings JSON NOT NULL, UNIQUE INDEX UNIQ_844454B177153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE acme_sylius_slide (id INT AUTO_INCREMENT NOT NULL, slider_id INT DEFAULT NULL, code VARCHAR(64) NOT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, button_label VARCHAR(255) DEFAULT NULL, url VARCHAR(1024) DEFAULT NULL, product_code VARCHAR(64) DEFAULT NULL, slide_cover VARCHAR(1024) DEFAULT NULL, slide_cover_mobile VARCHAR(1024) DEFAULT NULL, slide_cover_tablet VARCHAR(1024) DEFAULT NULL, slide_cover_video VARCHAR(1024) DEFAULT NULL, presentation_media VARCHAR(1024) DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, slide_settings JSON NOT NULL, content_settings JSON NOT NULL, UNIQUE INDEX UNIQ_876619A677153098 (code), INDEX IDX_876619A648D6CC1E (slider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acme_sylius_slide ADD CONSTRAINT FK_876619A648D6CC1E FOREIGN KEY (slider_id) REFERENCES acme_sylius_slider (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE acme_sylius_slide DROP FOREIGN KEY FK_876619A648D6CC1E');
        $this->addSql('DROP TABLE acme_sylius_slide');
        $this->addSql('DROP TABLE acme_sylius_slider');
    }
}
