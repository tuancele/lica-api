<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEEP DATA ENCODING ANALYSIS ===\n\n";

// Get a sample product with Vietnamese text
$product = DB::table('posts')
    ->where('type', 'product')
    ->where('status', '1')
    ->select('id', 'name', 'content', 'description')
    ->first();

if (!$product) {
    echo "No product found\n";
    exit;
}

echo "Product ID: {$product->id}\n";
echo "Product Name: {$product->name}\n\n";

// Analyze the encoding issue
echo "=== ENCODING ANALYSIS ===\n";

// Method 1: Check if it's ISO-8859-1 stored as UTF-8
$nameBytes = $product->name;
echo "1. Raw bytes (hex): " . bin2hex(substr($nameBytes, 0, 50)) . "...\n";

// Method 2: Try to detect the original encoding
$encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
foreach ($encodings as $enc) {
    try {
        $converted = mb_convert_encoding($nameBytes, 'UTF-8', $enc);
        if ($converted !== $nameBytes && mb_check_encoding($converted, 'UTF-8')) {
            echo "2. Possible source encoding: {$enc}\n";
            echo "   Converted: {$converted}\n";
        }
    } catch (\Exception $e) {
        // Skip invalid encoding
    }
}

// Method 3: Check for common Vietnamese character patterns
echo "\n3. Character Analysis:\n";
$chars = mb_str_split($product->name, 1, 'UTF-8');
$problemChars = [];
foreach ($chars as $i => $char) {
    if ($char === '?' || $char === '') {
        $problemChars[] = "Position {$i}: '{$char}' (hex: " . bin2hex($char) . ")";
    }
}
if (!empty($problemChars)) {
    echo "   Found " . count($problemChars) . " problematic characters\n";
    echo "   First 5: " . implode(", ", array_slice($problemChars, 0, 5)) . "\n";
} else {
    echo "   No obvious problematic characters found\n";
}

// Method 4: Check if data was double-encoded
echo "\n4. Double-encoding check:\n";
$testDecode = utf8_decode($product->name);
if ($testDecode !== $product->name) {
    echo "   utf8_decode produces different result (possible double-encoding)\n";
    echo "   Decoded: {$testDecode}\n";
} else {
    echo "   No double-encoding detected\n";
}

// Method 5: Check database storage
echo "\n5. Database Storage Check:\n";
$rawQuery = DB::select("SELECT HEX(name) as hex_name FROM posts WHERE id = ?", [$product->id]);
if (!empty($rawQuery)) {
    $hexName = $rawQuery[0]->hex_name;
    echo "   Stored as hex: " . substr($hexName, 0, 100) . "...\n";
    
    // Check for common Vietnamese character hex patterns
    $vietnamesePatterns = [
        'c3a1' => 'á', 'c3a0' => 'à', 'c3a3' => 'ã', 'c4a9' => 'ă',
        'c3a2' => 'â', 'c3a9' => 'é', 'c3a8' => 'è', 'c3aa' => 'ê',
        'c3ad' => 'í', 'c3ac' => 'ì', 'c3b3' => 'ó', 'c3b2' => 'ò',
        'c3b5' => 'õ', 'c6a1' => 'ơ', 'c3ba' => 'ú', 'c6b0' => 'ư',
        'c3bd' => 'ý', 'c491' => 'đ'
    ];
    
    $found = false;
    foreach ($vietnamesePatterns as $hex => $char) {
        if (stripos($hexName, $hex) !== false) {
            echo "   Found Vietnamese character '{$char}' (hex: {$hex}) in storage\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "   WARNING: No Vietnamese character patterns found in hex data!\n";
        echo "   This suggests data was stored incorrectly from the start.\n";
    }
}

// Method 6: Test fix attempt
echo "\n6. Fix Attempt Test:\n";
// If data was stored as ISO-8859-1 but read as UTF-8, we need to convert
$testFix = mb_convert_encoding($product->name, 'UTF-8', 'ISO-8859-1');
if ($testFix !== $product->name && mb_check_encoding($testFix, 'UTF-8')) {
    echo "   ISO-8859-1 to UTF-8 conversion produces:\n";
    echo "   {$testFix}\n";
    echo "   This might be the fix!\n";
} else {
    echo "   ISO-8859-1 conversion doesn't help\n";
}

// Check another approach: if it's Windows-1252
$testFix2 = mb_convert_encoding($product->name, 'UTF-8', 'Windows-1252');
if ($testFix2 !== $product->name && $testFix2 !== $testFix && mb_check_encoding($testFix2, 'UTF-8')) {
    echo "   Windows-1252 to UTF-8 conversion produces:\n";
    echo "   {$testFix2}\n";
}

echo "\n=== END ANALYSIS ===\n";

