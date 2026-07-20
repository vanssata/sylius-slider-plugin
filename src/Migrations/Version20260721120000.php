<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Slide title/description are now authored per language version. Historically
 * they were stored in the base slide's responsive settings; this migration
 * moves that text into every existing translation that does not already define
 * its own, then strips it from the base so the text is sourced only from the
 * translations (and becomes editable in the admin, which no longer edits base
 * text). Slides without any translation row are left untouched so the
 * storefront keeps rendering their text.
 */
final class Version20260721120000 extends AbstractMigration
{
    private const BREAKPOINTS = ['desktop', 'tablet', 'mobile'];
    private const TEXT_FIELDS = ['title', 'description'];

    public function getDescription(): string
    {
        return 'Move base slide responsive title/description into slide translations (text is now translation-sourced).';
    }

    public function up(Schema $schema): void
    {
        $slides = $this->connection->fetchAllAssociative('SELECT id, slide_settings FROM vanssa_sylius_slide');

        foreach ($slides as $slide) {
            $slideId = (int) $slide['id'];
            $base = $this->decode($slide['slide_settings']);
            $baseResponsive = $base['responsive'] ?? null;
            if (!is_array($baseResponsive)) {
                continue;
            }

            $baseText = $this->collectText($baseResponsive);
            if ([] === $baseText) {
                continue;
            }

            $translations = $this->connection->fetchAllAssociative(
                'SELECT id, slide_settings FROM vanssa_sylius_slide_translation WHERE slide_id = :id',
                ['id' => $slideId],
            );
            if ([] === $translations) {
                // No language version to move the text into — keep it on the
                // base so the storefront still renders it.
                continue;
            }

            foreach ($translations as $translation) {
                $tSettings = $this->decode($translation['slide_settings']);
                $tResponsive = is_array($tSettings['responsive'] ?? null) ? $tSettings['responsive'] : [];
                $changed = false;

                foreach ($baseText as $breakpoint => $fields) {
                    foreach ($fields as $field => $value) {
                        $existing = $tResponsive[$breakpoint][$field] ?? null;
                        if (is_string($existing) && '' !== $existing) {
                            continue; // translation already carries its own text
                        }
                        $tResponsive[$breakpoint][$field] = $value;
                        $changed = true;
                    }
                }

                if (!$changed) {
                    continue;
                }

                $tSettings['responsive'] = $tResponsive;
                $this->addSql(
                    'UPDATE vanssa_sylius_slide_translation SET slide_settings = :settings WHERE id = :id',
                    ['settings' => $this->encode($tSettings), 'id' => (int) $translation['id']],
                );
            }

            // Every existing translation now carries the text; drop it from the base.
            foreach ($baseText as $breakpoint => $fields) {
                foreach (array_keys($fields) as $field) {
                    unset($baseResponsive[$breakpoint][$field]);
                }
            }
            $base['responsive'] = $baseResponsive;
            $this->addSql(
                'UPDATE vanssa_sylius_slide SET slide_settings = :settings WHERE id = :id',
                ['settings' => $this->encode($base), 'id' => $slideId],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Best-effort reverse: copy each translation's responsive text back onto
        // the base slide (first non-empty value per breakpoint/field wins), so
        // the base again holds a fallback title/description.
        $slides = $this->connection->fetchAllAssociative('SELECT id, slide_settings FROM vanssa_sylius_slide');

        foreach ($slides as $slide) {
            $slideId = (int) $slide['id'];
            $base = $this->decode($slide['slide_settings']);
            $baseResponsive = is_array($base['responsive'] ?? null) ? $base['responsive'] : [];

            $translations = $this->connection->fetchAllAssociative(
                'SELECT slide_settings FROM vanssa_sylius_slide_translation WHERE slide_id = :id',
                ['id' => $slideId],
            );

            $restored = false;
            foreach ($translations as $translation) {
                $tResponsive = $this->decode($translation['slide_settings'])['responsive'] ?? null;
                if (!is_array($tResponsive)) {
                    continue;
                }
                foreach ($this->collectText($tResponsive) as $breakpoint => $fields) {
                    foreach ($fields as $field => $value) {
                        $existing = $baseResponsive[$breakpoint][$field] ?? null;
                        if (is_string($existing) && '' !== $existing) {
                            continue;
                        }
                        $baseResponsive[$breakpoint][$field] = $value;
                        $restored = true;
                    }
                }
            }

            if (!$restored) {
                continue;
            }

            $base['responsive'] = $baseResponsive;
            $this->addSql(
                'UPDATE vanssa_sylius_slide SET slide_settings = :settings WHERE id = :id',
                ['settings' => $this->encode($base), 'id' => $slideId],
            );
        }
    }

    /**
     * @param array<string, mixed> $responsive
     *
     * @return array<string, array<string, string>>
     */
    private function collectText(array $responsive): array
    {
        $text = [];
        foreach (self::BREAKPOINTS as $breakpoint) {
            foreach (self::TEXT_FIELDS as $field) {
                $value = $responsive[$breakpoint][$field] ?? null;
                if (is_string($value) && '' !== $value) {
                    $text[$breakpoint][$field] = $value;
                }
            }
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $json): array
    {
        if (!is_string($json) || '' === $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
