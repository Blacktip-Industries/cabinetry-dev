<?php
/**
 * Formula Builder Component - Diff Calculation
 * Calculates differences between formula versions
 */

/**
 * Compare two versions and return diff
 * @param string $oldCode Old version code
 * @param string $newCode New version code
 * @return array Diff result with changes
 */
function formula_builder_compare_versions($oldCode, $newCode) {
    $oldLines = explode("\n", $oldCode);
    $newLines = explode("\n", $newCode);
    
    return formula_builder_calculate_diff($oldLines, $newLines);
}

/**
 * Calculate line-by-line differences
 * @param array $oldLines Old lines
 * @param array $newLines New lines
 * @return array Diff result
 */
function formula_builder_calculate_diff($oldLines, $newLines) {
    $diff = [];
    $oldCount = count($oldLines);
    $newCount = count($newLines);
    $maxLines = max($oldCount, $newCount);
    
    // Simple line-by-line comparison
    for ($i = 0; $i < $maxLines; $i++) {
        $oldLine = isset($oldLines[$i]) ? $oldLines[$i] : null;
        $newLine = isset($newLines[$i]) ? $newLines[$i] : null;
        
        if ($oldLine === null && $newLine !== null) {
            // Added line
            $diff[] = [
                'type' => 'added',
                'old_line' => null,
                'new_line' => $i + 1,
                'old_content' => null,
                'new_content' => $newLine
            ];
        } elseif ($oldLine !== null && $newLine === null) {
            // Deleted line
            $diff[] = [
                'type' => 'deleted',
                'old_line' => $i + 1,
                'new_line' => null,
                'old_content' => $oldLine,
                'new_content' => null
            ];
        } elseif ($oldLine !== $newLine) {
            // Modified line
            $diff[] = [
                'type' => 'modified',
                'old_line' => $i + 1,
                'new_line' => $i + 1,
                'old_content' => $oldLine,
                'new_content' => $newLine
            ];
        } else {
            // Unchanged line
            $diff[] = [
                'type' => 'unchanged',
                'old_line' => $i + 1,
                'new_line' => $i + 1,
                'old_content' => $oldLine,
                'new_content' => $newLine
            ];
        }
    }
    
    return $diff;
}

/**
 * Get formatted diff between versions
 * @param int $versionId1 First version ID
 * @param int $versionId2 Second version ID
 * @return array Formatted diff result
 */
function formula_builder_get_version_diff($versionId1, $versionId2) {
    require_once __DIR__ . '/versions.php';
    
    $version1 = formula_builder_get_version($versionId1);
    $version2 = formula_builder_get_version($versionId2);
    
    if (!$version1 || !$version2) {
        return ['success' => false, 'error' => 'One or both versions not found'];
    }
    
    $diff = formula_builder_compare_versions($version1['formula_code'], $version2['formula_code']);
    
    return [
        'success' => true,
        'version1' => $version1,
        'version2' => $version2,
        'diff' => $diff,
        'stats' => [
            'added' => count(array_filter($diff, function($d) { return $d['type'] === 'added'; })),
            'deleted' => count(array_filter($diff, function($d) { return $d['type'] === 'deleted'; })),
            'modified' => count(array_filter($diff, function($d) { return $d['type'] === 'modified'; })),
            'unchanged' => count(array_filter($diff, function($d) { return $d['type'] === 'unchanged'; }))
        ]
    ];
}

/**
 * Get diff between current formula and version
 * @param int $formulaId Formula ID
 * @param int $versionId Version ID
 * @return array Formatted diff result
 */
function formula_builder_get_formula_version_diff($formulaId, $versionId) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/versions.php';
    
    $formula = formula_builder_get_formula_by_id($formulaId);
    $version = formula_builder_get_version($versionId);
    
    if (!$formula || !$version) {
        return ['success' => false, 'error' => 'Formula or version not found'];
    }
    
    if ($version['formula_id'] != $formulaId) {
        return ['success' => false, 'error' => 'Version does not belong to formula'];
    }
    
    $diff = formula_builder_compare_versions($version['formula_code'], $formula['formula_code']);
    
    return [
        'success' => true,
        'formula' => $formula,
        'version' => $version,
        'diff' => $diff,
        'stats' => [
            'added' => count(array_filter($diff, function($d) { return $d['type'] === 'added'; })),
            'deleted' => count(array_filter($diff, function($d) { return $d['type'] === 'deleted'; })),
            'modified' => count(array_filter($diff, function($d) { return $d['type'] === 'modified'; })),
            'unchanged' => count(array_filter($diff, function($d) { return $d['type'] === 'unchanged'; }))
        ]
    ];
}

