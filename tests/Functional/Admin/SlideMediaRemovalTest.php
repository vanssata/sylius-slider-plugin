<?php

declare(strict_types=1);

namespace Tests\Vanssa\SyliusSliderPlugin\Functional\Admin;

use Tests\Vanssa\SyliusSliderPlugin\Functional\FunctionalTestCase;
use Vanssa\SyliusSliderPlugin\Entity\Slide;
use Vanssa\SyliusSliderPlugin\Entity\Slider;

/**
 * SlideType's POST_SUBMIT listener wires six unmapped "remove this media"
 * checkboxes (one per image/video slot) against the actual file/URL fields:
 * a fresh upload always wins over the remove flag, an external video URL
 * wins over both, and otherwise a checked remove flag nulls the slot.
 */
final class SlideMediaRemovalTest extends FunctionalTestCase
{
    public function testCoverImageIsUnchangedWhenNeitherAnUploadNorTheRemoveFlagIsGiven(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-noop-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $originalCover = $slide->getSlideCover();
        self::assertNotNull($originalCover);
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();

        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        self::assertSame($originalCover, $reloaded->getSlideCover(), 'A plain save without touching the media slot must not clear or require re-uploading it.');
    }

    public function testRemoveFlagClearsTheCoverImageWithoutAnUpload(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-basic-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[slideCoverRemove]'] = '1';

        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        self::assertNull($reloaded->getSlideCover());
    }

    public function testFreshUploadWinsOverTheRemoveFlagForTheCoverImage(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-image-upload-wins-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $originalCover = $slide->getSlideCover();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[slideCoverRemove]'] = '1';
        $uploadPath = $this->createTempUploadFile();
        $form['slide[slideCoverFile]'] = $uploadPath;

        $this->client->submit($form);
        @unlink($uploadPath);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        $newCover = $reloaded->getSlideCover();
        self::assertNotNull($newCover, 'A fresh upload must win over the remove flag.');
        self::assertNotSame($originalCover, $newCover);
        self::assertStringStartsWith('/media/slider/base-cover/', $newCover);

        $this->deleteStoredMedia($newCover);
    }

    public function testFreshVideoUploadWinsOverTheRemoveFlagWhenNoExternalUrlIsGiven(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-video-upload-wins-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $slide->setSlideCoverVideo('/media/slider/base-cover-video/existing.mp4');
        $this->entityManager()->flush();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[slideCoverVideoRemove]'] = '1';
        $uploadPath = $this->createTempUploadFile();
        $form['slide[slideCoverVideoFile]'] = $uploadPath;

        $this->client->submit($form);
        @unlink($uploadPath);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        $newVideo = $reloaded->getSlideCoverVideo();
        self::assertNotNull($newVideo, 'A fresh upload must win over the remove flag.');
        self::assertNotSame('/media/slider/base-cover-video/existing.mp4', $newVideo);
        self::assertStringStartsWith('/media/slider/base-cover-video/', $newVideo);

        $this->deleteStoredMedia($newVideo);
    }

    public function testExternalVideoUrlWinsOverAnUploadAndTheRemoveFlag(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-video-url-wins-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $slide->setSlideCoverVideo('/media/slider/base-cover-video/existing.mp4');
        $this->entityManager()->flush();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        $form['slide[slideCoverVideoRemove]'] = '1';
        $form['slide[slideCoverVideoUrl]'] = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $uploadPath = $this->createTempUploadFile();
        $form['slide[slideCoverVideoFile]'] = $uploadPath;

        $this->client->submit($form);
        @unlink($uploadPath);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        self::assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $reloaded->getSlideCoverVideo());
    }

    public function testRemoveFlagsClearAllSixMediaSlotsInOneSubmission(): void
    {
        $this->ensureChannel();
        $slider = $this->createSlider('functional-media-removal-all-slots-slider', [], 1);
        $slide = $this->firstSlide($slider);
        $slideId = $slide->getId();
        $slide->setSlideCoverMobile('/media/functional/mobile.jpg');
        $slide->setSlideCoverTablet('/media/functional/tablet.jpg');
        $slide->setSlideCoverVideo('/media/functional/video.mp4');
        $slide->setSlideCoverVideoMobile('/media/functional/video-mobile.mp4');
        $slide->setSlideCoverVideoTablet('/media/functional/video-tablet.mp4');
        $this->entityManager()->flush();
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', sprintf('/admin/slides/%d/edit-panel', $slideId));
        $form = $crawler->selectButton('Update')->form();
        foreach ([
            'slideCoverRemove',
            'slideCoverMobileRemove',
            'slideCoverTabletRemove',
            'slideCoverVideoRemove',
            'slideCoverVideoMobileRemove',
            'slideCoverVideoTabletRemove',
        ] as $field) {
            $form[sprintf('slide[%s]', $field)] = '1';
        }

        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(Slide::class, $slideId);
        self::assertInstanceOf(Slide::class, $reloaded);
        self::assertNull($reloaded->getSlideCover());
        self::assertNull($reloaded->getSlideCoverMobile());
        self::assertNull($reloaded->getSlideCoverTablet());
        self::assertNull($reloaded->getSlideCoverVideo());
        self::assertNull($reloaded->getSlideCoverVideoMobile());
        self::assertNull($reloaded->getSlideCoverVideoTablet());
    }

    private function firstSlide(Slider $slider): Slide
    {
        $slide = $slider->getSlides()->first();
        self::assertInstanceOf(Slide::class, $slide);

        return $slide;
    }

    /**
     * A real, physical file: SlideType's POST_SUBMIT handler calls
     * UploadedMediaStorage::store(), which moves the uploaded file on disk.
     */
    private function createTempUploadFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'slide-media-test-');
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
