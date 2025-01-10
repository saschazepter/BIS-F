<?php declare(strict_types=1);

function getGitRootDir() {
    $output   = [];
    $exitCode = 0;
    exec('git rev-parse --show-toplevel 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        echo "Error: Could not determine Git root directory. Are you in a Git repository?\n";
        exit(1);
    }
    return trim($output[0]);
}

function getTranslationKeys($langPath): array {
    $keys = [];
    foreach (glob($langPath . '/*.json') as $file) {
        $jsonContent = json_decode(file_get_contents($file), true);
        if (is_array($jsonContent)) {
            $keys = array_merge($keys, array_keys($jsonContent));
        }
    }
    $keys = array_unique($keys);
    echo "Found " . count($keys) . " translation keys\n";
    return $keys;
}

function findUnusedKeys(string $langPath, string $projectPath): array {
    $keys = getTranslationKeys($langPath);

    $dirsToCheck = [
        '/app',
        '/resources',
        '/tests',
        '/database',
        '/config',
        '/routes',
        '/public',
        '/resources/views',
        '/resources/js',
    ];

    $filesToCheck = [];
    foreach ($dirsToCheck as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectPath . $directory));
        foreach ($iterator as $file) {
            // Check only PHP, Vue, and JS files
            if ($file->isFile() && preg_match('/\.(php|vue|js)$/', $file->getFilename())) {
                $filesToCheck[] = $file;
            }
        }
    }

    echo "Checking " . count($filesToCheck) . " files\n";

    $usedKeys = [];
    foreach ($filesToCheck as $file) {
        $content = file_get_contents($file->getPathname());
        foreach ($keys as $key) {
            if (str_contains($content, $key)) {
                $usedKeys[] = $key;
            }
        }
    }

    // Remove used keys from the list of unused keys
    $unusedKeys = array_diff($keys, $usedKeys);
    return array_values($unusedKeys);
}

// Main execution
$gitRoot     = getGitRootDir();
$langPath    = $gitRoot . '/lang';
$projectPath = $gitRoot;

$unusedKeys = findUnusedKeys($langPath, $projectPath);

echo PHP_EOL;
if (empty($unusedKeys)) {
    echo "No unused keys found.\n";
    exit(0);
}

echo "Unused keys found:\n";
foreach ($unusedKeys as $key) {
    echo "- $key\n";
}

if (count($unusedKeys) > 0) {
    exit(1);
}
exit(0);
