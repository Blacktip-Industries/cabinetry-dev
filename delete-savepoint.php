<?php
/**
 * Command-Line Savepoint Deletion Script
 * Deletes a savepoint and optionally its Git commit
 * 
 * Usage: 
 *   php delete-savepoint.php <commit-hash>
 *   php delete-savepoint.php last
 *   php delete-savepoint.php last --remove-git
 */

require_once __DIR__ . '/admin/backups/scripts/savepoint-functions.php';

// Initialize timezone early for CLI
try {
    setSystemTimezone();
} catch (Exception $e) {
    date_default_timezone_set('Australia/Brisbane');
}

// Get arguments
$commitHash = isset($argv[1]) ? trim($argv[1]) : null;
$removeGitCommit = false;

// Check for --remove-git flag
if (in_array('--remove-git', $argv) || in_array('-r', $argv)) {
    $removeGitCommit = true;
}

if (empty($commitHash)) {
    echo "Usage: php delete-savepoint.php <commit-hash|last> [--remove-git]\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php delete-savepoint.php last\n";
    echo "  php delete-savepoint.php abc123def456\n";
    echo "  php delete-savepoint.php last --remove-git\n";
    echo "\n";
    echo "Options:\n";
    echo "  --remove-git, -r    Also remove Git commit if it's the current HEAD\n";
    exit(1);
}

// If "last" is specified, convert to null
if (strtolower($commitHash) === 'last') {
    $commitHash = 'last';
}

echo "Deleting savepoint...\n";
if ($commitHash === 'last') {
    echo "Target: Last savepoint\n";
} else {
    echo "Target: Commit hash " . substr($commitHash, 0, 12) . "...\n";
}

if ($removeGitCommit) {
    echo "Warning: Git commit will be removed if it's the current HEAD.\n";
    echo "Continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim(strtolower($line));
    fclose($handle);
    
    if ($confirmation !== 'yes' && $confirmation !== 'y') {
        echo "Cancelled.\n";
        exit(0);
    }
}

$result = deleteSavepoint($commitHash, $removeGitCommit);

if ($result['success']) {
    echo "✓ Savepoint deleted successfully!\n\n";
    
    if (!empty($result['deleted_savepoint'])) {
        $sp = $result['deleted_savepoint'];
        echo "Deleted savepoint:\n";
        echo "  Message: " . $sp['message'] . "\n";
        echo "  Timestamp: " . $sp['timestamp'] . "\n";
        if ($sp['commit_hash']) {
            echo "  Commit Hash: " . $sp['commit_hash'] . "\n";
        }
    }
    
    if (!empty($result['deleted_files'])) {
        echo "\nDeleted files:\n";
        foreach ($result['deleted_files'] as $file) {
            echo "  - " . $file . "\n";
        }
    }
    
    if ($result['git_commit_removed']) {
        echo "\n✓ Git commit removed.\n";
    }
    
    if (!empty($result['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - " . $warning . "\n";
        }
    }
    
    exit(0);
} else {
    echo "✗ Failed to delete savepoint!\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

