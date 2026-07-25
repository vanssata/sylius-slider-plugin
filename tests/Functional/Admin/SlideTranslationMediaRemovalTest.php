<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\SlideTranslation;
use Vanssa\SyliusSliderPlugin\Form\Type\Translation\SlideTranslationType;

/**
 * SlideTranslationType's POST_SUBMIT listener mirrors SlideType's media
 * removal wiring for the six per-locale image/video slots: a fresh upload
 * always wins over the remove flag, an external video URL wins over both,
 * and otherwise a checked remove flag nulls the slot.
 */
final class SlideTranslationMediaRemovalTest extends FunctionalTestCase
{
    public function testCoverImageIsUnchangedWhenNeitherAnUploadNorTheRemoveFlagIsGiven(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCover('/media/de-cover.jpg');

        $this->submit($translation, []);

        self::assertSame('/media/de-cover.jpg', $translation->getSlideCover(), 'A plain save without touching the media slot must not clear or require re-uploading it.');
    }

    public function testRemoveFlagClearsTheCoverImageWithoutAnUpload(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCover('/media/de-cover.jpg');

        $this->submit($translation, ['slideCoverRemove' => '1']);

        self::assertNull($translation->getSlideCover());
    }

    public function testFreshUploadWinsOverTheRemoveFlagForTheCoverImage(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCover('/media/de-cover.jpg');
        $uploadPath = $this->createTempUploadFile();

        $this->submit($translation, [
            'slideCoverRemove' => '1',
            'slideCoverFile' => new UploadedFile($uploadPath, 'cover.jpg', 'image/jpeg', null, true),
        ]);

        $newCover = $translation->getSlideCover();
        self::assertNotNull($newCover, 'A fresh upload must win over the remove flag.');
        self::assertNotSame('/media/de-cover.jpg', $newCover);
        self::assertStringStartsWith('/media/slider/translation-cover/', $newCover);

        $this->deleteStoredMedia($newCover);
    }

    public function testFreshVideoUploadWinsOverTheRemoveFlagWhenNoExternalUrlIsGiven(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCoverVideo('/media/existing-video.mp4');
        $uploadPath = $this->createTempUploadFile();

        $this->submit($translation, [
            'slideCoverVideoRemove' => '1',
            'slideCoverVideoFile' => new UploadedFile($uploadPath, 'video.mp4', 'video/mp4', null, true),
        ]);

        $newVideo = $translation->getSlideCoverVideo();
        self::assertNotNull($newVideo, 'A fresh upload must win over the remove flag.');
        self::assertNotSame('/media/existing-video.mp4', $newVideo);
        self::assertStringStartsWith('/media/slider/translation-cover/', $newVideo);

        $this->deleteStoredMedia($newVideo);
    }

    public function testExternalVideoUrlWinsOverAnUploadAndTheRemoveFlag(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCoverVideo('/media/existing-video.mp4');
        $uploadPath = $this->createTempUploadFile();

        $this->submit($translation, [
            'slideCoverVideoRemove' => '1',
            'slideCoverVideoUrl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'slideCoverVideoFile' => new UploadedFile($uploadPath, 'video.mp4', 'video/mp4', null, true),
        ]);

        self::assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $translation->getSlideCoverVideo());
    }

    public function testRemoveFlagsClearAllSixMediaSlotsInOneSubmission(): void
    {
        $translation = $this->createTranslation();
        $translation->setSlideCover('/media/cover.jpg');
        $translation->setSlideCoverMobile('/media/cover-mobile.jpg');
        $translation->setSlideCoverTablet('/media/cover-tablet.jpg');
        $translation->setSlideCoverVideo('/media/video.mp4');
        $translation->setSlideCoverVideoMobile('/media/video-mobile.mp4');
        $translation->setSlideCoverVideoTablet('/media/video-tablet.mp4');

        $this->submit($translation, [
            'slideCoverRemove' => '1',
            'slideCoverMobileRemove' => '1',
            'slideCoverTabletRemove' => '1',
            'slideCoverVideoRemove' => '1',
            'slideCoverVideoMobileRemove' => '1',
            'slideCoverVideoTabletRemove' => '1',
        ]);

        self::assertNull($translation->getSlideCover());
        self::assertNull($translation->getSlideCoverMobile());
        self::assertNull($translation->getSlideCoverTablet());
        self::assertNull($translation->getSlideCoverVideo());
        self::assertNull($translation->getSlideCoverVideoMobile());
        self::assertNull($translation->getSlideCoverVideoTablet());
    }

    private function createTranslation(): SlideTranslation
    {
        $slide = new Slide();
        $slide->setCode('translation-media-removal-slide');

        $translation = new SlideTranslation();
        $translation->setLocaleCode('de_DE');
        $slide->addTranslation($translation);

        return $translation;
    }

    /**
     * @param array<string, mixed> $submittedData
     */
    private function submit(SlideTranslation $translation, array $submittedData): void
    {
        $formFactory = self::getContainer()->get('form.factory');
        \assert($formFactory instanceof FormFactoryInterface);

        $formFactory->create(SlideTranslationType::class, $translation)->submit($submittedData);
    }

    /**
     * A real, physical file: SlideTranslationType's POST_SUBMIT handler calls
     * UploadedMediaStorage::store(), which moves the uploaded file on disk.
     */
    private function createTempUploadFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'slide-translation-media-test-');
        if (false === $path) {
            throw new \RuntimeException('Could not create a temporary file for the upload test.');
        }

        file_put_contents($path, 'test-upload-content');

        return $path;
    }

    /** Cleans up the file UploadedMediaStorage wrote into public/media during the test. */
    private function deleteStoredMedia(string $relativePath): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Expected the "kernel.project_dir" parameter to be a string.');
        }

        @unlink($projectDir . '/public' . $relativePath);
    }
}
