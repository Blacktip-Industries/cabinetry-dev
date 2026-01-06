<?php
/**
 * Savepoints Component - Git Operations
 * Git command execution and repository management
 */

require_once __DIR__ . '/functions.php';

/**
 * Get the Git repository root directory
 * @return string Git root path
 */
function savepoints_get_git_root() {
    $projectRoot = savepoints_get_project_root();
    
    // Normalize path separators for Windows
    $root = str_replace('\\', '/', $projectRoot);
    
    // Verify it's actually a Git repository
    if (!is_dir($root . '/.git')) {
        // Try to find Git root by looking for .git directory
        $current = $root;
        while ($current !== dirname($current)) {
            if (is_dir($current . '/.git')) {
                return $current;
            }
            $current = dirname($current);
        }
    }
    
    return $root;
}

/**
 * Check if Git is available
 * @return bool
 */
function savepoints_is_git_available() {
    exec('git --version 2>&1', $output, $returnVar);
    return $returnVar === 0;
}

/**
 * Get the current Git branch name
 * @return string|null Returns branch name or null if unavailable
 */
function savepoints_get_current_branch() {
    if (!savepoints_is_git_available()) {
        return null;
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return null;
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    
    exec("git -C {$gitRootEscaped} rev-parse --abbrev-ref HEAD 2>&1", $output, $returnVar);
    
    if ($returnVar === 0 && !empty($output[0])) {
        return trim($output[0]);
    }
    
    return null;
}

/**
 * Check if a Git remote exists
 * @param string $remoteName Remote name (default: 'origin')
 * @return bool
 */
function savepoints_has_remote($remoteName = 'origin') {
    if (!savepoints_is_git_available()) {
        return false;
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return false;
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $remoteEscaped = escapeshellarg($remoteName);
    
    exec("git -C {$gitRootEscaped} remote get-url {$remoteEscaped} 2>&1", $output, $returnVar);
    
    return $returnVar === 0;
}

/**
 * Get the current Git commit hash
 * @return string|null
 */
function savepoints_get_current_commit_hash() {
    if (!savepoints_is_git_available()) {
        return null;
    }
    
    $gitRoot = savepoints_get_git_root();
    
    // Normalize path for Windows (use forward slashes)
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    
    try {
        exec("git -C {$gitRootEscaped} rev-parse HEAD 2>&1", $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    return null;
}

/**
 * Check if there are uncommitted changes in the working directory
 * @return bool True if there are uncommitted changes, false otherwise
 */
function savepoints_has_uncommitted_changes() {
    if (!savepoints_is_git_available()) {
        return false;
    }
    
    $gitRoot = savepoints_get_git_root();
    
    // Normalize path for Windows (use forward slashes)
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    
    try {
        // Use git status --porcelain to check for changes
        // Returns empty output if working directory is clean
        exec("git -C {$gitRootEscaped} status --porcelain 2>&1", $output, $returnVar);
        
        if ($returnVar === 0) {
            // If output is not empty, there are uncommitted changes
            return !empty($output);
        }
    } catch (Exception $e) {
        // If we can't check, assume no changes to be safe
        return false;
    }
    
    return false;
}

/**
 * Stage all files in Git
 * @param array $excludedDirs Array of excluded directory patterns
 * @return array ['success' => bool, 'output' => array, 'error' => string|null]
 */
function savepoints_git_stage_all($excludedDirs = null) {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'output' => [], 'error' => 'Git is not available'];
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return ['success' => false, 'output' => [], 'error' => 'Not a Git repository'];
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    
    // Stage all files
    exec("git -C {$gitRootEscaped} add -A 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'output' => $output, 'error' => 'Failed to stage files'];
    }
    
    return ['success' => true, 'output' => $output, 'error' => null];
}

/**
 * Create a Git commit
 * @param string $message Commit message
 * @return array ['success' => bool, 'commit_hash' => string|null, 'output' => array, 'error' => string|null]
 */
function savepoints_git_commit($message) {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'commit_hash' => null, 'output' => [], 'error' => 'Git is not available'];
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return ['success' => false, 'commit_hash' => null, 'output' => [], 'error' => 'Not a Git repository'];
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $escapedMessage = escapeshellarg(savepoints_sanitize_message($message));
    
    // Create commit
    exec("git -C {$gitRootEscaped} commit -m {$escapedMessage} 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'commit_hash' => null, 'output' => $output, 'error' => 'Failed to create commit'];
    }
    
    // Get commit hash
    exec("git -C {$gitRootEscaped} rev-parse HEAD 2>&1", $hashOutput, $hashReturn);
    
    if ($hashReturn === 0 && !empty($hashOutput[0])) {
        $commitHash = trim($hashOutput[0]);
        return ['success' => true, 'commit_hash' => $commitHash, 'output' => $output, 'error' => null];
    }
    
    return ['success' => false, 'commit_hash' => null, 'output' => $output, 'error' => 'Failed to get commit hash'];
}

/**
 * Push to remote repository
 * @param string $remoteName Remote name (default: 'origin')
 * @param string|null $branchName Branch name (default: current branch)
 * @return array ['success' => bool, 'output' => array, 'error' => string|null]
 */
function savepoints_git_push($remoteName = 'origin', $branchName = null) {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'output' => [], 'error' => 'Git is not available'];
    }
    
    if (!savepoints_has_remote($remoteName)) {
        return ['success' => false, 'output' => [], 'error' => 'Remote not found'];
    }
    
    if ($branchName === null) {
        $branchName = savepoints_get_current_branch();
        if ($branchName === null) {
            return ['success' => false, 'output' => [], 'error' => 'Could not determine branch name'];
        }
    }
    
    $gitRoot = savepoints_get_git_root();
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $branchEscaped = escapeshellarg($branchName);
    
    exec("git -C {$gitRootEscaped} push {$remoteName} {$branchEscaped} 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'output' => $output, 'error' => 'Failed to push to remote'];
    }
    
    return ['success' => true, 'output' => $output, 'error' => null];
}

/**
 * Reset working directory to a specific commit (hard reset)
 * @param string $commitHash Commit hash to reset to
 * @return array ['success' => bool, 'output' => array, 'error' => string|null]
 */
function savepoints_git_reset_hard($commitHash) {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'output' => [], 'error' => 'Git is not available'];
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return ['success' => false, 'output' => [], 'error' => 'Not a Git repository'];
    }
    
    // Validate commit hash format
    if (!preg_match('/^[a-f0-9]{7,40}$/i', $commitHash)) {
        return ['success' => false, 'output' => [], 'error' => 'Invalid commit hash format'];
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $commitEscaped = escapeshellarg($commitHash);
    
    exec("git -C {$gitRootEscaped} reset --hard {$commitEscaped} 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'output' => $output, 'error' => 'Failed to reset to commit'];
    }
    
    return ['success' => true, 'output' => $output, 'error' => null];
}

/**
 * Initialize Git repository
 * @return array ['success' => bool, 'output' => array, 'error' => string|null]
 */
function savepoints_git_init() {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'output' => [], 'error' => 'Git is not available'];
    }
    
    $gitRoot = savepoints_get_git_root();
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    
    exec("git -C {$gitRootEscaped} init 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'output' => $output, 'error' => 'Failed to initialize Git repository'];
    }
    
    return ['success' => true, 'output' => $output, 'error' => null];
}

/**
 * Add remote repository
 * @param string $remoteName Remote name (default: 'origin')
 * @param string $remoteUrl Remote URL
 * @return array ['success' => bool, 'output' => array, 'error' => string|null]
 */
function savepoints_git_add_remote($remoteName, $remoteUrl) {
    if (!savepoints_is_git_available()) {
        return ['success' => false, 'output' => [], 'error' => 'Git is not available'];
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return ['success' => false, 'output' => [], 'error' => 'Not a Git repository'];
    }
    
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $remoteEscaped = escapeshellarg($remoteName);
    $urlEscaped = escapeshellarg($remoteUrl);
    
    exec("git -C {$gitRootEscaped} remote add {$remoteEscaped} {$urlEscaped} 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        return ['success' => false, 'output' => $output, 'error' => 'Failed to add remote'];
    }
    
    return ['success' => true, 'output' => $output, 'error' => null];
}

/**
 * Get remote URL
 * @param string $remoteName Remote name (default: 'origin')
 * @return string|null Remote URL or null if not found
 */
function savepoints_git_get_remote_url($remoteName = 'origin') {
    if (!savepoints_is_git_available()) {
        return null;
    }
    
    if (!savepoints_has_remote($remoteName)) {
        return null;
    }
    
    $gitRoot = savepoints_get_git_root();
    $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
    $gitRootEscaped = escapeshellarg($gitRootNormalized);
    $remoteEscaped = escapeshellarg($remoteName);
    
    exec("git -C {$gitRootEscaped} remote get-url {$remoteEscaped} 2>&1", $output, $returnVar);
    
    if ($returnVar === 0 && !empty($output[0])) {
        return trim($output[0]);
    }
    
    return null;
}

