// bom_cleaner.php - tedd a Laravel gyökérkönyvtárba
<?php

$files = [
    __DIR__ . '/routes/api.php',
    __DIR__ . '/app/Http/Controllers/Api/AuthController.php',
    __DIR__ . '/app/Http/Controllers/Api/AdminController.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "Cleaning BOM from: " . basename($file) . "\n";
        
        $content = file_get_contents($file);
        
        // Remove BOM
        $bom = pack('H*','EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        
        // Also remove any other BOM variants
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = preg_replace('/^\xFE\xFF/', '', $content);
        $content = preg_replace('/^\xFF\xFE/', '', $content);
        $content = preg_replace('/^\x00\x00\xFE\xFF/', '', $content);
        $content = preg_replace('/^\xFF\xFE\x00\x00/', '', $content);
        
        // Save file with UTF-8 without BOM
        file_put_contents($file, $content);
        
        echo "✓ Cleaned: " . basename($file) . "\n";
    } else {
        echo "✗ File not found: " . basename($file) . "\n";
    }
}

echo "\nBOM cleanup complete!\n";