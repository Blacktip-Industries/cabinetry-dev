#!/bin/bash
# Development Standards Setup Script (Linux/Mac)
# Usage: ./setup-standards.sh [target_path]

TARGET_PATH="${1:-$(pwd)}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "🚀 Setting up development standards..."
echo "Target: $TARGET_PATH"
echo ""

# Copy .cursorrules
if [ ! -f "$TARGET_PATH/.cursorrules" ]; then
    cp "$SCRIPT_DIR/.cursorrules-template" "$TARGET_PATH/.cursorrules"
    echo "✅ Copied: .cursorrules"
else
    echo "⏭️  Skipped: .cursorrules (already exists)"
fi

# Create _standards folder
mkdir -p "$TARGET_PATH/_standards"

# Copy standards files
for file in NAMING_STANDARDS.md COMPONENT_CREATION_PROCEDURE.md .cursorrules-template; do
    if [ ! -f "$TARGET_PATH/_standards/$file" ]; then
        cp "$SCRIPT_DIR/$file" "$TARGET_PATH/_standards/"
        echo "✅ Copied: _standards/$file"
    fi
done

# Copy to admin/components if it exists
if [ -d "$TARGET_PATH/admin/components" ]; then
    for file in NAMING_STANDARDS.md COMPONENT_CREATION_PROCEDURE.md; do
        if [ ! -f "$TARGET_PATH/admin/components/$file" ]; then
            cp "$SCRIPT_DIR/$file" "$TARGET_PATH/admin/components/"
            echo "✅ Copied: admin/components/$file"
        fi
    done
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Review .cursorrules in project root"
echo "   2. Review _standards/ folder"
echo "   3. Run verification: php _standards/verify-installation.php"
echo "   4. Test Cursor with verification prompts in README.md"

