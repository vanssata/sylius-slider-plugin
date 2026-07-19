<?php

/**
 * Flattens the human-readable recipe sources under flex/recipes/<vendor>/<pkg>/<version>/
 * into the archived JSON files Symfony Flex actually downloads
 * (flex/<vendor>.<pkg>.<version>.json, referenced by index.json's recipe_template).
 *
 * Run after changing any recipe source:  php flex/build-recipes.php
 */

declare(strict_types=1);

$flexDir = __DIR__;

foreach (glob($flexDir.'/recipes/*/*/*', GLOB_ONLYDIR) as $versionDir) {
    $version = basename($versionDir);
    $package = basename(\dirname($versionDir, 2)).'/'.basename(\dirname($versionDir));

    $manifest = json_decode((string) file_get_contents($versionDir.'/manifest.json'), true, 512, \JSON_THROW_ON_ERROR);

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($versionDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $rel = substr((string) $file, \strlen($versionDir) + 1);
        if ('manifest.json' === $rel || !$file->isFile()) {
            continue;
        }
        // "contents" as a list of lines; Flex joins them with "\n"
        $files[str_replace('\\', '/', $rel)] = [
            'contents' => explode("\n", (string) file_get_contents((string) $file)),
            'executable' => $file->isExecutable(),
        ];
    }
    ksort($files);

    $entry = ['manifest' => $manifest, 'files' => $files];
    $entry['ref'] = sha1(json_encode($entry, \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));

    $out = sprintf('%s/%s.%s.json', $flexDir, str_replace('/', '.', $package), $version);
    file_put_contents($out, json_encode(
        ['manifests' => [$package => $entry]],
        \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR
    )."\n");

    echo "wrote $out\n";
}
