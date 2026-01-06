<?php
/**
 * Code Extraction Tool
 * Extracts code from existing projects for import into library
 */

// Configuration
$sourceProjectPath = __DIR__ . '/../..';
$outputDir = __DIR__ . '/../extracted';

// Create output directory
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

/**
 * Extract a file and its metadata
 */
function extractFile($filePath, $relativePath, $projectPath) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $content = file_get_contents($filePath);
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    
    // Determine file type
    $fileType = 'other';
    if (in_array($extension, ['php'])) {
        $fileType = 'php';
    } elseif (in_array($extension, ['js'])) {
        $fileType = 'js';
    } elseif (in_array($extension, ['css'])) {
        $fileType = 'css';
    } elseif (in_array($extension, ['sql'])) {
        $fileType = 'sql';
    }
    
    // Detect dependencies (basic detection)
    $dependencies = [];
    if ($fileType === 'php') {
        // Look for require, include, require_once, include_once
        preg_match_all('/(?:require|include)(?:_once)?\s+[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[1])) {
            $dependencies = array_unique($matches[1]);
        }
    }
    
    return [
        'file_path' => $relativePath,
        'file_name' => basename($filePath),
        'file_type' => $fileType,
        'content' => $content,
        'dependencies' => $dependencies,
        'size' => filesize($filePath),
        'modified' => filemtime($filePath)
    ];
}

/**
 * Scan directory for PHP files
 */
function scanDirectory($dir, $baseDir, $excludeDirs = []) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $fullPath = $dir . '/' . $item;
        $relativePath = str_replace($baseDir . '/', '', $fullPath);
        
        // Skip excluded directories
        $shouldExclude = false;
        foreach ($excludeDirs as $exclude) {
            if (strpos($relativePath, $exclude) === 0) {
                $shouldExclude = true;
                break;
            }
        }
        if ($shouldExclude) {
            continue;
        }
        
        if (is_dir($fullPath)) {
            $files = array_merge($files, scanDirectory($fullPath, $baseDir, $excludeDirs));
        } elseif (pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
            $files[] = $relativePath;
        }
    }
    
    return $files;
}

// Main extraction
echo "Code Extraction Tool\n";
echo "===================\n\n";

if (!is_dir($sourceProjectPath)) {
    die("Error: Source project path not found: $sourceProjectPath\n");
}

// Exclude certain directories
$excludeDirs = [
    'vendor',
    'node_modules',
    '.git',
    'uploads',
    'backups'
];

echo "Scanning project for PHP files...\n";
$files = scanDirectory($sourceProjectPath, $sourceProjectPath, $excludeDirs);

echo "Found " . count($files) . " PHP files\n\n";

// Extract files
$extracted = [];
foreach ($files as $file) {
    $fullPath = $sourceProjectPath . '/' . $file;
    echo "Extracting: $file\n";
    
    $data = extractFile($fullPath, $file, $sourceProjectPath);
    if ($data) {
        $extracted[] = $data;
        
        // Save to output directory
        $outputPath = $outputDir . '/' . str_replace('/', '_', $file);
        file_put_contents($outputPath . '.txt', json_encode($data, JSON_PRETTY_PRINT));
    }
}

// Create summary
$summary = [
    'extracted_at' => date('Y-m-d H:i:s'),
    'source_project' => $sourceProjectPath,
    'total_files' => count($extracted),
    'files' => array_map(function($f) {
        return [
            'path' => $f['file_path'],
            'type' => $f['file_type'],
            'size' => $f['size'],
            'dependencies' => $f['dependencies']
        ];
    }, $extracted)
];

file_put_contents($outputDir . '/summary.json', json_encode($summary, JSON_PRETTY_PRINT));

echo "\nExtraction complete!\n";
echo "Extracted " . count($extracted) . " files\n";
echo "Output directory: $outputDir\n";
echo "Summary saved to: summary.json\n";

