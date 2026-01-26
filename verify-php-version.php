<?php

/**
 * PHP Version Verification Script
 * Run this to verify PHP version before Laravel 11 upgrade.
 */
echo "=== PHP Version Verification ===\n\n";

$phpVersion = phpversion();
echo "Current PHP Version: {$phpVersion}\n";

// Check if PHP 8.3+
$major = (int) explode('.', $phpVersion)[0];
$minor = (int) explode('.', $phpVersion)[1];

if ($major >= 8 && $minor >= 3) {
    echo "✅ PHP version is compatible with Laravel 11 (requires PHP ^8.2)\n";
    exit(0);
} else {
    echo "❌ PHP version is NOT compatible. Laravel 11 requires PHP ^8.2\n";
    echo "   Current: PHP {$phpVersion}\n";
    echo "   Required: PHP 8.2+\n";
    exit(1);
}
