<?php

/**
 * Script to add declare(strict_types=1) to all PHP files
 * Usage: php scripts/add-strict-types.php.
 */

declare(strict_types=1);

$directories = [
    __DIR__.'/../app',
    __DIR__.'/../routes',
    __DIR__.'/../database',
    __DIR__.'/../tests',
];

$excludePaths = [
    __DIR__.'/../app/Themes',
    __DIR__.'/../vendor',
    __DIR__.'/../storage',
    __DIR__.'/../bootstrap/cache',
];

$filesProcessed = 0;
$filesSkipped = 0;
$filesWithErrors = [];

function shouldExclude(string $filePath, array $excludePaths): bool
{
    foreach ($excludePaths as $excludePath) {
        if (strpos($filePath, $excludePath) === 0) {
            return true;
        }
    }

    return false;
}

function addStrictTypes(string $filePath): bool
{
    $content = file_get_contents($filePath);

    if ($content === false) {
        return false;
    }

    // Skip if already has strict_types
    if (strpos($content, 'declare(strict_types=1)') !== false) {
        return false;
    }

    // Skip if no PHP opening tag
    if (strpos($content, '<?php') === false) {
        return false;
    }

    // Handle different opening tag formats
    $patterns = [
        '/^<\?php\s*\n/' => "<?php\n\ndeclare(strict_types=1);\n",
        '/^<\?php\n/' => "<?php\n\ndeclare(strict_types=1);\n",
        '/^<\?=\s*/' => "<?php\n\ndeclare(strict_types=1);\n",
    ];

    $newContent = $content;
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $newContent)) {
            $newContent = preg_replace($pattern, $replacement, $newContent, 1);
            break;
        }
    }

    // If no replacement happened, add after first line
    if ($newContent === $content) {
        $lines = explode("\n", $content);
        if (count($lines) > 0 && strpos($lines[0], '<?php') !== false) {
            array_splice($lines, 1, 0, '', 'declare(strict_types=1);');
            $newContent = implode("\n", $lines);
        }
    }

    if ($newContent !== $content) {
        return file_put_contents($filePath, $newContent) !== false;
    }

    return false;
}

function processDirectory(string $dir, array $excludePaths, int &$processed, int &$skipped, array &$errors): void
{
    if (! is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();

        if (shouldExclude($filePath, $excludePaths)) {
            $skipped++;
            continue;
        }

        if (addStrictTypes($filePath)) {
            $processed++;
            echo '✓ Added strict_types to: '.str_replace(__DIR__.'/../', '', $filePath)."\n";
        } else {
            $skipped++;
        }
    }
}

echo "Starting to add declare(strict_types=1) to PHP files...\n\n";

foreach ($directories as $directory) {
    $fullPath = __DIR__.'/../'.str_replace(__DIR__.'/../', '', $directory);
    if (is_dir($fullPath)) {
        processDirectory($fullPath, $excludePaths, $filesProcessed, $filesSkipped, $filesWithErrors);
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "Files processed: {$filesProcessed}\n";
echo "Files skipped: {$filesSkipped}\n";
echo "========================================\n";
