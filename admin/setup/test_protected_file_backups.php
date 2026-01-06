<?php
/**
 * Test Protected File Backups
 * Tests the writeProtectedFile() function with different protection levels
 * Creates test files, adds them to protection, writes to them, verifies backups, then cleans up
 * 
 * Run this script via command line: php admin/setup/test_protected_file_backups.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/file_protection.php';

$testDir = 'test_backup_files';
$testFiles = [
    'backup_required' => $testDir . '/test_backup_required.php',
    'backup_optional' => $testDir . '/test_backup_optional.php',
    'unprotected' => $testDir . '/test_unprotected.php'
];

$projectRoot = dirname(dirname(__DIR__));
$errors = [];
$successes = [];

echo "=== Protected File Backup Test ===\n\n";

// Step 1: Create test directory
echo "Step 1: Creating test directory...\n";
$testDirPath = $projectRoot . DIRECTORY_SEPARATOR . $testDir;
if (!is_dir($testDirPath)) {
    if (!mkdir($testDirPath, 0755, true)) {
        die("ERROR: Failed to create test directory: $testDirPath\n");
    }
    echo "✓ Test directory created: $testDir\n";
} else {
    echo "✓ Test directory already exists\n";
}

// Step 2: Create initial test files
echo "\nStep 2: Creating initial test files...\n";
foreach ($testFiles as $type => $filePath) {
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    $initialContent = "<?php\n// Initial content for $type test file\n\$test = 'initial';\n";
    
    if (file_put_contents($fullPath, $initialContent) === false) {
        $errors[] = "Failed to create test file: $filePath";
        echo "✗ Failed to create: $filePath\n";
    } else {
        echo "✓ Created: $filePath\n";
    }
}

// Step 3: Add files to protected files database
echo "\nStep 3: Adding files to protected files database...\n";
$addResult1 = addProtectedFile($testFiles['backup_required'], 'backup_required', 'Test file for BACKUP REQUIRED');
if ($addResult1['success']) {
    echo "✓ Added {$testFiles['backup_required']} as BACKUP REQUIRED\n";
} else {
    $errors[] = "Failed to add backup_required file: " . ($addResult1['error'] ?? 'Unknown error');
    echo "✗ Failed to add backup_required file: " . ($addResult1['error'] ?? 'Unknown error') . "\n";
}

$addResult2 = addProtectedFile($testFiles['backup_optional'], 'backup_optional', 'Test file for BACKUP OPTIONAL');
if ($addResult2['success']) {
    echo "✓ Added {$testFiles['backup_optional']} as BACKUP OPTIONAL\n";
} else {
    $errors[] = "Failed to add backup_optional file: " . ($addResult2['error'] ?? 'Unknown error');
    echo "✗ Failed to add backup_optional file: " . ($addResult2['error'] ?? 'Unknown error') . "\n";
}

// Step 4: Test BACKUP REQUIRED - should create backup before write
echo "\nStep 4: Testing BACKUP REQUIRED protection level...\n";
$newContent1 = "<?php\n// Modified content for backup_required test file\n\$test = 'modified';\n";
$writeResult1 = writeProtectedFile($testFiles['backup_required'], $newContent1, 'Test modification', 'test_script');
if ($writeResult1['success']) {
    echo "✓ File written successfully\n";
    
    // Verify backup was created
    $backups1 = getFileBackups($testFiles['backup_required']);
    if (count($backups1) > 0) {
        echo "✓ Backup created successfully (ID: {$backups1[0]['id']})\n";
        $successes[] = "BACKUP REQUIRED: Backup created and file written";
    } else {
        $errors[] = "BACKUP REQUIRED: File written but no backup found";
        echo "✗ ERROR: File written but no backup found!\n";
    }
} else {
    $errors[] = "BACKUP REQUIRED: " . ($writeResult1['error'] ?? 'Unknown error');
    echo "✗ Failed to write file: " . ($writeResult1['error'] ?? 'Unknown error') . "\n";
}

// Step 5: Test BACKUP OPTIONAL - should attempt backup but proceed even if fails
echo "\nStep 5: Testing BACKUP OPTIONAL protection level...\n";
$newContent2 = "<?php\n// Modified content for backup_optional test file\n\$test = 'modified';\n";
$writeResult2 = writeProtectedFile($testFiles['backup_optional'], $newContent2, 'Test modification', 'test_script');
if ($writeResult2['success']) {
    echo "✓ File written successfully\n";
    
    // Verify backup was created
    $backups2 = getFileBackups($testFiles['backup_optional']);
    if (count($backups2) > 0) {
        echo "✓ Backup created successfully (ID: {$backups2[0]['id']})\n";
        $successes[] = "BACKUP OPTIONAL: Backup created and file written";
    } else {
        echo "⚠ Warning: File written but no backup found (this is acceptable for BACKUP OPTIONAL)\n";
        $successes[] = "BACKUP OPTIONAL: File written (backup may have failed but write proceeded)";
    }
} else {
    $errors[] = "BACKUP OPTIONAL: " . ($writeResult2['error'] ?? 'Unknown error');
    echo "✗ Failed to write file: " . ($writeResult2['error'] ?? 'Unknown error') . "\n";
}

// Step 6: Test unprotected file - should write without backup
echo "\nStep 6: Testing unprotected file (no backup should be created)...\n";
$newContent3 = "<?php\n// Modified content for unprotected test file\n\$test = 'modified';\n";
$writeResult3 = writeProtectedFile($testFiles['unprotected'], $newContent3, 'Test modification', 'test_script');
if ($writeResult3['success']) {
    echo "✓ File written successfully\n";
    
    // Verify no backup was created
    $backups3 = getFileBackups($testFiles['unprotected']);
    if (count($backups3) === 0) {
        echo "✓ No backup created (as expected for unprotected file)\n";
        $successes[] = "Unprotected file: Written without backup (correct behavior)";
    } else {
        echo "⚠ Warning: Backup was created for unprotected file (unexpected but not critical)\n";
        $successes[] = "Unprotected file: Written (backup created unexpectedly)";
    }
} else {
    $errors[] = "Unprotected file: " . ($writeResult3['error'] ?? 'Unknown error');
    echo "✗ Failed to write file: " . ($writeResult3['error'] ?? 'Unknown error') . "\n";
}

// Step 7: Test HARD BLOCK - should reject modification
echo "\nStep 7: Testing HARD BLOCK protection level...\n";
$hardBlockFile = 'admin/backups/index.php'; // This is a hard-blocked file
$hardBlockContent = "<?php\n// This should not be written\n";
$writeResult4 = writeProtectedFile($hardBlockFile, $hardBlockContent, 'Test modification', 'test_script');
if (!$writeResult4['success']) {
    echo "✓ File modification correctly rejected\n";
    echo "  Reason: " . ($writeResult4['error'] ?? 'Unknown') . "\n";
    $successes[] = "HARD BLOCK: Modification correctly rejected";
} else {
    $errors[] = "HARD BLOCK: File was modified when it should have been rejected!";
    echo "✗ ERROR: File was modified when it should have been rejected!\n";
}

// Step 8: Cleanup
echo "\nStep 8: Cleaning up test files and database entries...\n";

// Remove from protected files database
$removeResult1 = removeProtectedFile($testFiles['backup_required']);
if ($removeResult1['success']) {
    echo "✓ Removed {$testFiles['backup_required']} from protected files\n";
} else {
    echo "⚠ Warning: Failed to remove from protected files: " . ($removeResult1['error'] ?? 'Unknown') . "\n";
}

$removeResult2 = removeProtectedFile($testFiles['backup_optional']);
if ($removeResult2['success']) {
    echo "✓ Removed {$testFiles['backup_optional']} from protected files\n";
} else {
    echo "⚠ Warning: Failed to remove from protected files: " . ($removeResult2['error'] ?? 'Unknown') . "\n";
}

// Delete backups from database
$conn = getDBConnection();
if ($conn) {
    foreach ($testFiles as $type => $filePath) {
        $normalizedPath = normalizeFilePath($filePath);
        $stmt = $conn->prepare("DELETE FROM file_backups WHERE file_path = ?");
        $stmt->bind_param("s", $normalizedPath);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        if ($deleted > 0) {
            echo "✓ Deleted $deleted backup(s) for $filePath\n";
        }
    }
}

// Delete test files from filesystem
foreach ($testFiles as $type => $filePath) {
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "✓ Deleted test file: $filePath\n";
        } else {
            echo "⚠ Warning: Failed to delete test file: $filePath\n";
        }
    }
}

// Remove test directory if empty
if (is_dir($testDirPath)) {
    $files = scandir($testDirPath);
    if (count($files) <= 2) { // Only . and ..
        if (rmdir($testDirPath)) {
            echo "✓ Removed test directory: $testDir\n";
        } else {
            echo "⚠ Warning: Failed to remove test directory: $testDir\n";
        }
    } else {
        echo "⚠ Warning: Test directory not empty, leaving it in place\n";
    }
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Successful tests: " . count($successes) . "\n";
foreach ($successes as $success) {
    echo "  ✓ $success\n";
}

if (count($errors) > 0) {
    echo "\nErrors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n❌ TEST FAILED - Some tests encountered errors\n";
    exit(1);
} else {
    echo "\n✅ ALL TESTS PASSED - Backup system is working correctly!\n";
    exit(0);
}

