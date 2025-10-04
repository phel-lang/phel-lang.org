<?php

/**
 * Update Phel version in config.toml based on installed Phel package version
 */

$versionFinderFile = __DIR__ . '/../vendor/phel-lang/phel-lang/src/php/Console/Application/VersionFinder.php';
$configFile = __DIR__ . '/../config.toml';

if (!file_exists($versionFinderFile)) {
    echo "Error: VersionFinder.php not found. Make sure phel-lang/phel-lang is installed.\n";
    exit(1);
}

if (!file_exists($configFile)) {
    echo "Error: config.toml not found\n";
    exit(1);
}

// Read VersionFinder.php to extract LATEST_VERSION constant
$versionFinderContent = file_get_contents($versionFinderFile);

if (!$versionFinderContent) {
    echo "Error: Failed to read VersionFinder.php\n";
    exit(1);
}

// Extract version from LATEST_VERSION constant
$phelVersion = null;
if (preg_match(
    '/public\s+const\s+string\s+LATEST_VERSION\s*=\s*[\'"]v?(\d+\.\d+\.\d+)[\'"]/',
    $versionFinderContent,
    $matches
)) {
    $phelVersion = $matches[1];
}

if (!$phelVersion) {
    echo "Warning: Could not determine Phel version from VersionFinder.php\n";
    exit(0);
}

// Read config.toml
$configContent = file_get_contents($configFile);

// Update version
$updatedContent = preg_replace(
    '/^phel_version\s*=\s*"[^"]*"/m',
    'phel_version = "' . $phelVersion . '"',
    $configContent
);

if ($updatedContent === $configContent) {
    echo "Phel version in config.toml is already up to date: $phelVersion\n";
    exit(0);
}

// Write updated config
file_put_contents($configFile, $updatedContent);

echo "Updated Phel version in config.toml to: $phelVersion\n";
