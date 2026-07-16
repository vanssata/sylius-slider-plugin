<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716045546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-breakpoint slide cover videos on slides and their translations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vanssa_sylius_slide ADD slide_cover_video_mobile VARCHAR(1024) DEFAULT NULL, ADD slide_cover_video_tablet VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE vanssa_sylius_slide_translation ADD slide_cover_video VARCHAR(1024) DEFAULT NULL, ADD slide_cover_video_mobile VARCHAR(1024) DEFAULT NULL, ADD slide_cover_video_tablet VARCHAR(1024) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vanssa_sylius_slide DROP slide_cover_video_mobile, DROP slide_cover_video_tablet');
        $this->addSql('ALTER TABLE vanssa_sylius_slide_translation DROP slide_cover_video, DROP slide_cover_video_mobile, DROP slide_cover_video_tablet');
    }
}
