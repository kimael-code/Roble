#!/bin/bash

# Wrapper script to generate CHANGELOG and commit it to main
# Usage: bash scripts/update-changelog.sh

set -e

echo "📝 Updating CHANGELOG.md..."

# Ensure we're on main and up to date
echo "🔄 Pulling latest changes..."
git checkout main
git pull --tags origin main

# Generate CHANGELOG
echo "� Generating CHANGELOG from git history..."
bash scripts/generate-changelog.sh

# Check if there are changes
if git diff --quiet CHANGELOG.md 2>/dev/null; then
    echo "ℹ️  No changes to CHANGELOG.md"
    exit 0
fi

# Show the changes
echo ""
echo "📄 Changes to CHANGELOG.md:"
git diff CHANGELOG.md

# Ask for confirmation
echo ""
read -p "Commit and push these changes? (y/n) " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    LATEST_TAG=$(git describe --tags --abbrev=0)
    git add CHANGELOG.md
    git commit -m "docs: update CHANGELOG for ${LATEST_TAG}"
    git push origin main
    
    echo "✅ CHANGELOG updated and pushed to main!"
else
    echo "❌ Changes not committed"
    git restore CHANGELOG.md
fi

