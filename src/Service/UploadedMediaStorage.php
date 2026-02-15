<?php

declare(strict_types=1);

namespace Vanssa\SyliusSliderPlugin\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedMediaStorage
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function store(UploadedFile $file, string $subDir = 'slider'): string
    {
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);

        $relativeDirectory = sprintf('/media/%s', trim($subDir, '/'));
        $targetDirectory = $this->projectDir . '/public' . $relativeDirectory;

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $file->move($targetDirectory, $filename);

        return $relativeDirectory . '/' . $filename;
    }
}
