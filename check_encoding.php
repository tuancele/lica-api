<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ENCODING DIAGNOSTICS ===\n\n";

// 1. Check PHP default charset
echo "1. PHP Settings:\n";
echo "   default_charset: " . ini_get('default_charset') . "\n";
echo "   mbstring.internal_encoding: " . (ini_get('mbstring.internal_encoding') ?: 'not set') . "\n";
echo "   mbstring.http_output: " . (ini_get('mbstring.http_output') ?: 'not set') . "\n\n";

// 2. Check Database Connection
echo "2. Database Connection:\n";
try {
    $pdo = DB::connection()->getPdo();
    $charset = $pdo->query("SELECT @@character_set_connection as charset, @@collation_connection as collation")->fetch(PDO::FETCH_ASSOC);
    echo "   Connection charset: " . ($charset['charset'] ?? 'unknown') . "\n";
    echo "   Connection collation: " . ($charset['collation'] ?? 'unknown') . "\n";
    
    $dbCharset = $pdo->query("SELECT @@character_set_database as charset, @@collation_database as collation")->fetch(PDO::FETCH_ASSOC);
    echo "   Database charset: " . ($dbCharset['charset'] ?? 'unknown') . "\n";
    echo "   Database collation: " . ($dbCharset['collation'] ?? 'unknown') . "\n\n";
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n\n";
}

// 3. Check Table Charsets
echo "3. Table Charsets:\n";
$tables = ['posts', 'categories', 'brands', 'variants'];
foreach ($tables as $table) {
    try {
        $result = DB::select("SHOW TABLE STATUS WHERE Name = ?", [$table]);
        if (!empty($result)) {
            echo "   {$table}: " . ($result[0]->Collation ?? 'unknown') . "\n";
        }
    } catch (\Exception $e) {
        echo "   {$table}: ERROR - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// 4. Check Sample Data
echo "4. Sample Data Check:\n";
try {
    $product = DB::table('posts')
        ->where('type', 'product')
        ->where('status', '1')
        ->select('id', 'name', 'content')
        ->first();
    
    if ($product) {
        echo "   Product ID: {$product->id}\n";
        echo "   Product Name (raw): " . bin2hex($product->name) . "\n";
        echo "   Product Name (display): {$product->name}\n";
        
        // Check if it's double-encoded
        $decoded = mb_convert_encoding($product->name, 'UTF-8', 'UTF-8');
        if ($decoded !== $product->name) {
            echo "   WARNING: Possible double-encoding detected!\n";
        }
        
        // Check encoding
        $encoding = mb_detect_encoding($product->name, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        echo "   Detected encoding: " . ($encoding ?: 'unknown') . "\n";
    } else {
        echo "   No products found\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Check Column Charsets
echo "5. Column Charsets (posts table):\n";
try {
    $columns = DB::select("SHOW FULL COLUMNS FROM posts WHERE Field IN ('name', 'content', 'description')");
    foreach ($columns as $col) {
        echo "   {$col->Field}: {$col->Type} - " . ($col->Collation ?? 'no collation') . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Test UTF-8 Insert/Select
echo "6. UTF-8 Test:\n";
try {
    $testString = "Tiếng Việt có dấu: á, à, ả, ã, ạ, ă, ắ, ằ, ẳ, ẵ, ặ, â, ấ, ầ, ẩ, ẫ, ậ";
    echo "   Test string: {$testString}\n";
    echo "   String length: " . mb_strlen($testString, 'UTF-8') . " chars\n";
    echo "   Byte length: " . strlen($testString) . " bytes\n";
    echo "   Is valid UTF-8: " . (mb_check_encoding($testString, 'UTF-8') ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Check Response Headers (simulated)
echo "7. Response Configuration:\n";
echo "   App charset: " . config('app.charset', 'not set') . "\n";
echo "\n";

echo "=== END DIAGNOSTICS ===\n";

