<?php
/**
 * Update All Projects with Latest Standards
 * 
 * This script updates all your projects with the latest development standards
 * from the master _standards folder.
 * 
 * Usage:
 *   1. Edit the $projects array below with your project paths
 *   2. Run: php _standards/update-all-projects.php
 * 
 * Options:
 *   --dry-run    Show what would be updated without making changes
 *   --force      Overwrite existing files even if they're newer
 */

$masterStandardsPath = __DIR__;
$dryRun = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);

// ============================================
// CONFIGURATION: Add your project paths here
// ============================================
$projects = [
    '/path/to/project1',
    '/path/to/project2',
    '/path/to/project3',
    // Add all your project paths here
    // Example: '/var/www/project1', 'C:\xampp\htdocs\project2'
];

$filesToUpdate = [
    'NAMING_STANDARDS.md',
    'COMPONENT_CREATION_PROCEDURE.md',
    '.cursorrules-template'
];

// ============================================
// SCRIPT EXECUTION
// ============================================

if (empty($projects) || $projects[0] === '/path/to/project1') {
    echo "⚠️  Please edit the \$projects array in this script with your project paths.\n";
    echo "   Edit: " . __FILE__ . "\n";
    exit(1);
}

echo "🔄 Updating all projects with latest standards...\n";
if ($dryRun) {
    echo "   [DRY RUN MODE - No files will be changed]\n";
}
echo "\n";

$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($projects as $projectPath) {
    // Normalize path
    $projectPath = rtrim($projectPath, '/\\');
    
    if (!is_dir($projectPath)) {
        echo "❌ Project not found: {$projectPath}\n";
        $errorCount++;
        continue;
    }
    
    echo "📁 Project: " . basename($projectPath) . "\n";
    echo "   Path: {$projectPath}\n";
    
    // Create _standards folder if it doesn't exist
    $standardsDir = $projectPath . '/_standards';
    if (!is_dir($standardsDir)) {
        if (!$dryRun) {
            if (mkdir($standardsDir, 0755, true)) {
                echo "   ✅ Created _standards/\n";
            } else {
                echo "   ❌ Failed to create _standards/\n";
                $errorCount++;
                continue;
            }
        } else {
            echo "   [Would create] _standards/\n";
        }
    }
    
    // Copy standards files
    foreach ($filesToUpdate as $file) {
        $source = $masterStandardsPath . '/' . $file;
        $target = $standardsDir . '/' . $file;
        
        if (!file_exists($source)) {
            echo "   ⚠️  Source file not found: {$file}\n";
            continue;
        }
        
        // Check if update is needed
        $needsUpdate = true;
        if (file_exists($target) && !$force) {
            $sourceTime = filemtime($source);
            $targetTime = filemtime($target);
            if ($targetTime >= $sourceTime) {
                $needsUpdate = false;
            }
        }
        
        if ($needsUpdate || $force) {
            if (!$dryRun) {
                if (copy($source, $target)) {
                    echo "   ✅ Updated: {$file}\n";
                    $updatedCount++;
                } else {
                    echo "   ❌ Failed to update: {$file}\n";
                    $errorCount++;
                }
            } else {
                echo "   [Would update] {$file}\n";
                $updatedCount++;
            }
        } else {
            echo "   ⏭️  Skipped: {$file} (already up to date)\n";
            $skippedCount++;
        }
    }
    
    // Update project's .cursorrules
    $cursorRulesSource = $masterStandardsPath . '/.cursorrules-template';
    $cursorRulesTarget = $projectPath . '/.cursorrules';
    if (file_exists($cursorRulesSource)) {
        $needsUpdate = true;
        if (file_exists($cursorRulesTarget) && !$force) {
            $sourceTime = filemtime($cursorRulesSource);
            $targetTime = filemtime($cursorRulesTarget);
            if ($targetTime >= $sourceTime) {
                $needsUpdate = false;
            }
        }
        
        if ($needsUpdate || $force) {
            if (!$dryRun) {
                if (copy($cursorRulesSource, $cursorRulesTarget)) {
                    echo "   ✅ Updated: .cursorrules\n";
                    $updatedCount++;
                } else {
                    echo "   ❌ Failed to update: .cursorrules\n";
                    $errorCount++;
                }
            } else {
                echo "   [Would update] .cursorrules\n";
                $updatedCount++;
            }
        } else {
            echo "   ⏭️  Skipped: .cursorrules (already up to date)\n";
            $skippedCount++;
        }
    }
    
    // Update admin/components files if directory exists
    $adminComponentsPath = $projectPath . '/admin/components';
    if (is_dir($adminComponentsPath)) {
        foreach (['NAMING_STANDARDS.md', 'COMPONENT_CREATION_PROCEDURE.md'] as $file) {
            $source = $masterStandardsPath . '/' . $file;
            $target = $adminComponentsPath . '/' . $file;
            
            if (file_exists($source)) {
                $needsUpdate = true;
                if (file_exists($target) && !$force) {
                    $sourceTime = filemtime($source);
                    $targetTime = filemtime($target);
                    if ($targetTime >= $sourceTime) {
                        $needsUpdate = false;
                    }
                }
                
                if ($needsUpdate || $force) {
                    if (!$dryRun) {
                        if (copy($source, $target)) {
                            echo "   ✅ Updated: admin/components/{$file}\n";
                            $updatedCount++;
                        } else {
                            echo "   ❌ Failed to update: admin/components/{$file}\n";
                            $errorCount++;
                        }
                    } else {
                        echo "   [Would update] admin/components/{$file}\n";
                        $updatedCount++;
                    }
                } else {
                    echo "   ⏭️  Skipped: admin/components/{$file} (already up to date)\n";
                    $skippedCount++;
                }
            }
        }
    }
    
    echo "\n";
}

// Summary
echo "📊 Summary:\n";
echo "   Updated: {$updatedCount} files\n";
echo "   Skipped: {$skippedCount} files\n";
if ($errorCount > 0) {
    echo "   Errors: {$errorCount}\n";
}
echo "\n";

if ($dryRun) {
    echo "💡 Run without --dry-run to apply changes.\n";
} else {
    echo "✅ Update complete!\n";
}

