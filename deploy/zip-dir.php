<?php
/*
 * Zip a folder with forward-slash paths (works on every OS; Windows PowerShell's
 * Compress-Archive writes "\" paths that break when extracted on a Linux server).
 *
 *   php deploy/zip-dir.php <source-folder> <output.zip>
 */
if ($argc !== 3) {
    fwrite(STDERR, "Usage: php zip-dir.php <source-folder> <output.zip>\n");
    exit(1);
}
$src = realpath($argv[1]);
if (! $src || ! is_dir($src)) {
    fwrite(STDERR, "Not a folder: {$argv[1]}\n");
    exit(1);
}
$zip = new ZipArchive();
if ($zip->open($argv[2], ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot write {$argv[2]}\n");
    exit(1);
}
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $file) {
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($src) + 1));
    if ($file->isDir()) {
        $zip->addEmptyDir($rel);          // keeps empty runtime folders (storage/logs, bootstrap/cache…)
    } else {
        $zip->addFile($file->getPathname(), $rel);
    }
}
$zip->close();
