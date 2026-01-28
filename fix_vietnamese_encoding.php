<?php

/**
 * Script to fix Vietnamese character encoding issues in database
 * 
 * PROBLEM IDENTIFIED:
 * - Data was stored with wrong encoding (likely ISO-8859-1 or Windows-1252)
 * - Vietnamese characters were replaced with '?' (0x3F)
 * - This is IRREVERSIBLE - original data is lost
 * 
 * SOLUTION:
 * Since the original Vietnamese characters are lost (replaced with '?'),
 * we need to either:
 * 1. Restore from backup with correct encoding
 * 2. Re-enter the data manually
 * 3. Use a mapping table if available
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VIETNAMESE ENCODING FIX ANALYSIS ===\n\n";

// Common Vietnamese word patterns that might help identify corrupted data
$vietnamesePatterns = [
    'Gi?m' => 'Giảm',
    'Th?m' => 'Thâm',
    'V?ng' => 'Vùng',
    'K?n' => 'Kín',
    'Ng?c' => 'Ngực',
    'N?ch' => 'Nách',
    'M?ng' => 'Mông',
    'D??ng' => 'Dưỡng',
    'Tr?' => 'Trị',
    'M?n' => 'Mụn',
    'Se' => 'Se',
    'Kh?t' => 'Khít',
    'L? Ch?n L?ng' => 'Lỗ Chân Lông',
];

// Check how many records have encoding issues
echo "1. Scanning for encoding issues...\n";
$tables = [
    'posts' => ['name', 'content', 'description', 'title'],
    'categories' => ['name', 'description'],
    'brands' => ['name', 'description'],
];

$totalIssues = 0;
$fixableRecords = [];

foreach ($tables as $table => $columns) {
    if (!DB::getSchemaBuilder()->hasTable($table)) {
        continue;
    }
    
    foreach ($columns as $column) {
        if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
            continue;
        }
        
        // Count records with '?' character (likely encoding issue)
        $count = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->where($column, 'LIKE', '%?%')
            ->count();
        
        if ($count > 0) {
            echo "   {$table}.{$column}: {$count} records with potential encoding issues\n";
            $totalIssues += $count;
            
            // Get sample records
            $samples = DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where($column, 'LIKE', '%?%')
                ->select('id', $column)
                ->limit(3)
                ->get();
            
            foreach ($samples as $sample) {
                $fixableRecords[] = [
                    'table' => $table,
                    'column' => $column,
                    'id' => $sample->id,
                    'current' => $sample->$column,
                ];
            }
        }
    }
}

echo "\n2. Total issues found: {$totalIssues}\n";

if ($totalIssues > 0) {
    echo "\n3. Sample problematic records:\n";
    foreach (array_slice($fixableRecords, 0, 5) as $record) {
        echo "   {$record['table']}.{$record['column']} (ID: {$record['id']}):\n";
        echo "      Current: " . substr($record['current'], 0, 80) . "...\n";
        
        // Try to fix using pattern matching
        $fixed = $record['current'];
        foreach ($vietnamesePatterns as $wrong => $correct) {
            $fixed = str_replace($wrong, $correct, $fixed);
        }
        
        if ($fixed !== $record['current']) {
            echo "      Fixed:   " . substr($fixed, 0, 80) . "...\n";
        } else {
            echo "      Cannot auto-fix (no pattern match)\n";
        }
        echo "\n";
    }
    
    echo "\n4. RECOMMENDATION:\n";
    echo "   The data has been corrupted and original Vietnamese characters are lost.\n";
    echo "   Options:\n";
    echo "   a) Restore from backup with correct encoding\n";
    echo "   b) Re-enter data manually\n";
    echo "   c) Use pattern matching for common words (limited success)\n";
    echo "   d) Export/Import with correct encoding from source\n";
    echo "\n";
    
    echo "5. To attempt pattern-based fix (run with --fix flag):\n";
    echo "   php fix_vietnamese_encoding.php --fix\n";
    echo "   WARNING: This will modify database data. Backup first!\n";
}

// If --fix flag is provided, attempt to fix
if (isset($argv[1]) && $argv[1] === '--fix') {
    echo "\n=== ATTEMPTING FIX ===\n";
    echo "WARNING: This will modify your database!\n";
    echo "Press Enter to continue or Ctrl+C to cancel...\n";
    // fgets(STDIN); // Uncomment for interactive mode
    
    $fixedCount = 0;
    foreach ($tables as $table => $columns) {
        foreach ($columns as $column) {
            $records = DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where($column, 'LIKE', '%?%')
                ->get();
            
            foreach ($records as $record) {
                $original = $record->$column;
                $fixed = $original;
                
                // Apply pattern fixes
                foreach ($vietnamesePatterns as $wrong => $correct) {
                    $fixed = str_replace($wrong, $correct, $fixed);
                }
                
                if ($fixed !== $original) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update([$column => $fixed]);
                    $fixedCount++;
                }
            }
        }
    }
    
    echo "Fixed {$fixedCount} records using pattern matching.\n";
    echo "Note: This is a partial fix. Manual review and correction may be needed.\n";
}

echo "\n=== END ANALYSIS ===\n";

