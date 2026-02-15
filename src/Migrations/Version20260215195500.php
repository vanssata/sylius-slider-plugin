<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align index names/layout with current Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->renameIndexIfNeeded('vanssa_sylius_slide', 'uniq_876619a677153098', 'UNIQ_4660932A77153098');
        $this->renameIndexIfNeeded('vanssa_sylius_slide', 'idx_876619a648d6cc1e', 'IDX_4660932A2CCC9638');
        $this->renameIndexIfNeeded('vanssa_sylius_slider', 'uniq_844454b177153098', 'UNIQ_B7F456D877153098');
        $this->renameIndexIfNeeded('vanssa_sylius_slide_translation', 'idx_95a50d9998e46b87', 'IDX_24482DA0DD5AFB87');
        $this->renameIndexIfNeeded('vanssa_sylius_slider_translation', 'idx_3f40008748d6cc1e', 'IDX_15CD22532CCC9638');

        if (!$this->indexExists('messenger_messages', 'IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750')) {
            if ($this->indexExists('messenger_messages', 'IDX_75EA56E0FB7336F0')) {
                $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
            }

            if ($this->indexExists('messenger_messages', 'IDX_75EA56E0E3BD61CE')) {
                $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
            }

            if ($this->indexExists('messenger_messages', 'IDX_75EA56E016BA31DB')) {
                $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
            }

            $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        }
    }

    public function down(Schema $schema): void
    {
    }

    private function renameIndexIfNeeded(string $table, string $from, string $to): void
    {
        if ($this->indexExists($table, $from) && !$this->indexExists($table, $to)) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME INDEX %s TO %s', $table, $from, $to));
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index",
            ['table' => $table, 'index' => $index],
        ) > 0;
    }
}
