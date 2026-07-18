<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Cloner;

use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;

/**
 * Deep-copies a slide (settings, channels, media references, translations
 * incl. their override flags) into an independent new entity. Media paths are
 * shared with the source — files are not duplicated on disk.
 */
final class SlideCloner
{
    public function clone(Slide $source): Slide
    {
        $clone = new Slide();

        $clone->setCode(self::cloneCode($source->getCode()));
        $clone->setName($source->getName());
        $clone->setButtonLabel($source->getButtonLabel());
        $clone->setUrl($source->getUrl());
        $clone->setProductCode($source->getProductCode());
        $clone->setSlideCover($source->getSlideCover());
        $clone->setSlideCoverMobile($source->getSlideCoverMobile());
        $clone->setSlideCoverTablet($source->getSlideCoverTablet());
        $clone->setSlideCoverVideo($source->getSlideCoverVideo());
        $clone->setSlideCoverVideoMobile($source->getSlideCoverVideoMobile());
        $clone->setSlideCoverVideoTablet($source->getSlideCoverVideoTablet());
        $clone->setPresentationMedia($source->getPresentationMedia());
        $clone->setPosition($source->getPosition());
        $clone->setEnabled($source->isEnabled());
        $clone->setChannelCodes($source->getChannelCodes());
        $clone->setSlideSettings($source->getSlideSettings());
        $clone->setContentSettings($source->getContentSettings());

        foreach ($source->getTranslations() as $translation) {
            if (!$translation instanceof SlideTranslation) {
                continue;
            }

            $clone->addTranslation($this->cloneTranslation($translation));
        }

        return $clone;
    }

    private function cloneTranslation(SlideTranslation $source): SlideTranslation
    {
        $clone = new SlideTranslation();

        $clone->setLocaleCode($source->getLocaleCode());
        $clone->setName($source->getName());
        $clone->setButtonLabel($source->getButtonLabel());
        $clone->setUrl($source->getUrl());
        $clone->setSlideCover($source->getSlideCover());
        $clone->setSlideCoverMobile($source->getSlideCoverMobile());
        $clone->setSlideCoverTablet($source->getSlideCoverTablet());
        $clone->setSlideCoverVideo($source->getSlideCoverVideo());
        $clone->setSlideCoverVideoMobile($source->getSlideCoverVideoMobile());
        $clone->setSlideCoverVideoTablet($source->getSlideCoverVideoTablet());
        $clone->setContentSettings($source->getContentSettings());
        // Normalization keeps linking, overrides and responsive intact — the
        // source is already normalized, so this is a faithful copy.
        $clone->setSlideSettings($source->getSlideSettings());

        return $clone;
    }

    private static function cloneCode(string $sourceCode): string
    {
        $suffix = '-copy-' . bin2hex(random_bytes(4));

        return substr($sourceCode, 0, 64 - strlen($suffix)) . $suffix;
    }
}
