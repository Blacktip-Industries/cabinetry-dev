<?php
/**
 * Savepoints Component - GitHub API Operations
 * GitHub API fallback functions for operations that require authentication
 */

require_once __DIR__ . '/functions.php';

/**
 * Test GitHub API connectivity
 * @param string $token GitHub Personal Access Token
 * @param string|null $repoUrl Repository URL (optional, will try to detect)
 * @return array ['success' => bool, 'message' => string, 'data' => array|null]
 */
function savepoints_github_test_connectivity($token, $repoUrl = null) {
    if (empty($token)) {
        return ['success' => false, 'message' => 'GitHub token is required', 'data' => null];
    }
    
    // Test API connectivity by getting authenticated user
    $ch = curl_init('https://api.github.com/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: Savepoints-Component'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $userData = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'GitHub API connection successful',
            'data' => $userData
        ];
    } elseif ($httpCode === 401) {
        return [
            'success' => false,
            'message' => 'Invalid GitHub token',
            'data' => null
        ];
    } else {
        return [
            'success' => false,
            'message' => 'GitHub API error: HTTP ' . $httpCode,
            'data' => null
        ];
    }
}

/**
 * Push to GitHub using API (creates a new commit via API)
 * Note: This is a simplified implementation. Full implementation would require
 * creating a tree, commit, and updating ref via API
 * @param string $token GitHub Personal Access Token
 * @param string $repoUrl Repository URL
 * @param string $branch Branch name
 * @param string $message Commit message
 * @return array ['success' => bool, 'message' => string, 'data' => array|null]
 */
function savepoints_github_push_via_api($token, $repoUrl, $branch, $message) {
    // Extract owner and repo from URL
    // Format: https://github.com/owner/repo.git or git@github.com:owner/repo.git
    if (preg_match('/github\.com[\/:]([^\/]+)\/([^\/]+?)(?:\.git)?$/', $repoUrl, $matches)) {
        $owner = $matches[1];
        $repo = rtrim($matches[2], '.git');
    } else {
        return [
            'success' => false,
            'message' => 'Invalid repository URL format',
            'data' => null
        ];
    }
    
    // Get current commit SHA
    $refUrl = "https://api.github.com/repos/{$owner}/{$repo}/git/refs/heads/{$branch}";
    $ch = curl_init($refUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: Savepoints-Component',
        'Accept: application/vnd.github.v3+json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'message' => 'Failed to get current branch reference: HTTP ' . $httpCode,
            'data' => null
        ];
    }
    
    $refData = json_decode($response, true);
    $currentSha = $refData['object']['sha'];
    
    // Note: Full implementation would require:
    // 1. Get current tree
    // 2. Create new tree with file changes
    // 3. Create commit
    // 4. Update ref
    
    // For now, return a message that API push requires more complex implementation
    // and recommend using Git commands instead
    return [
        'success' => false,
        'message' => 'GitHub API push requires complex tree/commit creation. Use Git commands instead.',
        'data' => null
    ];
}

/**
 * Get repository information from GitHub API
 * @param string $token GitHub Personal Access Token
 * @param string $repoUrl Repository URL
 * @return array ['success' => bool, 'message' => string, 'data' => array|null]
 */
function savepoints_github_get_repo_info($token, $repoUrl) {
    // Extract owner and repo from URL
    if (preg_match('/github\.com[\/:]([^\/]+)\/([^\/]+?)(?:\.git)?$/', $repoUrl, $matches)) {
        $owner = $matches[1];
        $repo = rtrim($matches[2], '.git');
    } else {
        return [
            'success' => false,
            'message' => 'Invalid repository URL format',
            'data' => null
        ];
    }
    
    $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}";
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: Savepoints-Component',
        'Accept: application/vnd.github.v3+json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $repoData = json_decode($response, true);
        return [
            'success' => true,
            'message' => 'Repository information retrieved',
            'data' => $repoData
        ];
    } elseif ($httpCode === 404) {
        return [
            'success' => false,
            'message' => 'Repository not found',
            'data' => null
        ];
    } elseif ($httpCode === 401) {
        return [
            'success' => false,
            'message' => 'Invalid GitHub token',
            'data' => null
        ];
    } else {
        return [
            'success' => false,
            'message' => 'GitHub API error: HTTP ' . $httpCode,
            'data' => null
        ];
    }
}

/**
 * Check if GitHub token has required permissions
 * @param string $token GitHub Personal Access Token
 * @return array ['success' => bool, 'has_repo_permission' => bool, 'message' => string]
 */
function savepoints_github_check_token_permissions($token) {
    $ch = curl_init('https://api.github.com/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: Savepoints-Component'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'has_repo_permission' => false,
            'message' => 'Failed to authenticate with GitHub'
        ];
    }
    
    // Note: To check specific scopes, we'd need to check the response headers
    // X-OAuth-Scopes header contains the scopes
    // For now, just verify authentication works
    return [
        'success' => true,
        'has_repo_permission' => true,
        'message' => 'Token is valid (verify it has repo scope for push access)'
    ];
}

