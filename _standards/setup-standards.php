<?php
/**
 * Development Standards Setup Script
 * Copies standards files to new project
 * 
 * Usage:
 *   php _standards/setup-standards.php [target_path]
 * 
 * If target_path not provided, uses current directory
 */

// Get target path
$targetPath = isset($argv[1]) ? $argv[1] : getcwd();
$targetPath = rtrim($targetPath, '/\\');

// Get script directory (where this file is located)
$scriptDir = __DIR__;
$standardsDir = $scriptDir;

// Files to copy
$filesToCopy = [
    '.cursorrules-template' => '.cursorrules',
    'NAMING_STANDARDS.md' => 'NAMING_STANDARDS.md',
    'COMPONENT_CREATION_PROCEDURE.md' => 'COMPONENT_CREATION_PROCEDURE.md'
];

// Directories to create
$directoriesToCreate = [
    $targetPath . '/_standards',
    $targetPath . '/admin/components' // If it doesn't exist
];

echo "🚀 Setting up development standards...\n\n";

// Create directories
foreach ($directoriesToCreate as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: {$dir}\n";
        } else {
            echo "❌ Failed to create directory: {$dir}\n";
        }
    }
}

// Copy files
$copied = 0;
$skipped = 0;

foreach ($filesToCopy as $sourceFile => $targetFile) {
    $sourcePath = $standardsDir . '/' . $sourceFile;
    $targetPathFull = $targetPath . '/' . $targetFile;
    
    // Special handling for .cursorrules
    if ($sourceFile === '.cursorrules-template') {
        $targetPathFull = $targetPath . '/.cursorrules';
    }
    
    // Check if source exists
    if (!file_exists($sourcePath)) {
        echo "⚠️  Source file not found: {$sourcePath}\n";
        continue;
    }
    
    // Check if target already exists
    if (file_exists($targetPathFull)) {
        echo "⏭️  Skipped (already exists): {$targetFile}\n";
        $skipped++;
        continue;
    }
    
    // Copy file
    if (copy($sourcePath, $targetPathFull)) {
        echo "✅ Copied: {$targetFile}\n";
        $copied++;
    } else {
        echo "❌ Failed to copy: {$sourceFile} → {$targetFile}\n";
    }
}

// Copy standards folder
$standardsTarget = $targetPath . '/_standards';
if (!is_dir($standardsTarget)) {
    if (mkdir($standardsTarget, 0755, true)) {
        echo "✅ Created: _standards/\n";
    }
}

// Copy all standards files to _standards folder
$standardsFiles = [
    'NAMING_STANDARDS.md',
    'COMPONENT_CREATION_PROCEDURE.md',
    '.cursorrules-template'
];

foreach ($standardsFiles as $file) {
    $sourcePath = $standardsDir . '/' . $file;
    $targetPathFull = $standardsTarget . '/' . $file;
    
    if (file_exists($sourcePath)) {
        if (!file_exists($targetPathFull)) {
            if (copy($sourcePath, $targetPathFull)) {
                echo "✅ Copied to _standards/: {$file}\n";
            }
        }
    }
}

// Copy to admin/components if directory exists
$adminComponentsPath = $targetPath . '/admin/components';
if (is_dir($adminComponentsPath)) {
    foreach (['NAMING_STANDARDS.md', 'COMPONENT_CREATION_PROCEDURE.md'] as $file) {
        $sourcePath = $standardsDir . '/' . $file;
        $targetPathFull = $adminComponentsPath . '/' . $file;
        
        if (file_exists($sourcePath) && !file_exists($targetPathFull)) {
            if (copy($sourcePath, $targetPathFull)) {
                echo "✅ Copied to admin/components/: {$file}\n";
            }
        }
    }
}

echo "\n";
echo "📊 Summary:\n";
echo "   Copied: {$copied} files\n";
echo "   Skipped: {$skipped} files\n";
echo "\n";
echo "✅ Setup complete!\n";
echo "\n";
echo "📝 Next steps:\n";
echo "   1. Review .cursorrules in project root\n";
echo "   2. Review _standards/ folder\n";
echo "   3. Run verification: php _standards/verify-installation.php\n";
echo "   4. Test Cursor with verification prompts in README.md\n";
echo "\n";

