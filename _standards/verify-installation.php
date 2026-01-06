<?php
/**
 * Verify Standards Installation
 * Checks if all required files exist and are readable
 */

echo "🔍 Verifying standards installation...\n\n";

$checks = [
    '.cursorrules' => 'Project root',
    '_standards/NAMING_STANDARDS.md' => '_standards folder',
    '_standards/COMPONENT_CREATION_PROCEDURE.md' => '_standards folder',
    '_standards/.cursorrules-template' => '_standards folder',
];

$allPassed = true;

foreach ($checks as $file => $location) {
    if (file_exists($file)) {
        echo "✅ {$file} exists ({$location})\n";
    } else {
        echo "❌ {$file} MISSING ({$location})\n";
        $allPassed = false;
    }
}

// Optional checks
echo "\n📋 Optional files:\n";
$optional = [
    'admin/components/NAMING_STANDARDS.md',
    'admin/components/COMPONENT_CREATION_PROCEDURE.md',
];

foreach ($optional as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists\n";
    } else {
        echo "⚠️  {$file} missing (optional)\n";
    }
}

echo "\n";
if ($allPassed) {
    echo "✅ All required files are present!\n";
    echo "💡 Next: Test Cursor with verification prompts in README.md\n";
    echo "\n";
    echo "📝 Verification Tests:\n";
    echo "   1. Ask Cursor: 'What is the naming convention for component names?'\n";
    echo "   2. Ask Cursor: 'What are the CSS variable naming conventions?'\n";
    echo "   3. Ask Cursor: 'Can I use hardcoded colors in CSS?'\n";
    echo "   4. Generate code and verify it follows naming conventions\n";
    exit(0);
} else {
    echo "❌ Some required files are missing!\n";
    echo "💡 Run: php _standards/setup-standards.php\n";
    exit(1);
}

