#!/bin/bash

# Script to automatically generate CHANGELOG.md from git history
# Usage: bash scripts/generate-changelog.sh

set -e

CHANGELOG_FILE="CHANGELOG.md"
REPO_URL="https://github.com/kimael-code/Roble"

echo "📝 Generating CHANGELOG.md from git history..."

# Get all tags sorted by version
TAGS=$(git tag -l "v*" | sort -V)

if [ -z "$TAGS" ]; then
    echo "❌ No version tags found"
    exit 1
fi

# Start CHANGELOG
cat > "$CHANGELOG_FILE" << 'EOF'
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

EOF

# Process each tag
PREV_TAG=""
for TAG in $(echo "$TAGS" | tac); do  # Reverse order (newest first)
    echo "Processing $TAG..."
    
    # Get tag date
    TAG_DATE=$(git log -1 --format=%ai "$TAG" | cut -d' ' -f1)
    
    # Get commits for this tag
    if [ -z "$PREV_TAG" ]; then
        # First tag - get all commits up to this tag
        COMMITS=$(git log "$TAG" --pretty=format:"%s" --no-merges)
    else
        # Get commits between previous tag and this tag
        COMMITS=$(git log "${PREV_TAG}..${TAG}" --pretty=format:"%s" --no-merges)
    fi
    
    # Parse commits by type
    FEATURES=$(echo "$COMMITS" | grep "^feat" || true)
    FIXES=$(echo "$COMMITS" | grep "^fix" || true)
    DOCS=$(echo "$COMMITS" | grep "^docs" || true)
    REFACTOR=$(echo "$COMMITS" | grep "^refactor" || true)
    PERF=$(echo "$COMMITS" | grep "^perf" || true)
    CHORE=$(echo "$COMMITS" | grep "^chore" || true)
    
    # Write section for this version
    echo "" >> "$CHANGELOG_FILE"
    echo "## [$TAG] - $TAG_DATE" >> "$CHANGELOG_FILE"
    
    # Features
    if [ -n "$FEATURES" ]; then
        echo "" >> "$CHANGELOG_FILE"
        echo "### Added" >> "$CHANGELOG_FILE"
        echo "$FEATURES" | while read -r line; do
            # Remove "feat: " or "feat(scope): " prefix
            MSG=$(echo "$line" | sed 's/^feat[^:]*: //')
            echo "- $MSG" >> "$CHANGELOG_FILE"
        done
    fi
    
    # Fixes
    if [ -n "$FIXES" ]; then
        echo "" >> "$CHANGELOG_FILE"
        echo "### Fixed" >> "$CHANGELOG_FILE"
        echo "$FIXES" | while read -r line; do
            MSG=$(echo "$line" | sed 's/^fix[^:]*: //')
            echo "- $MSG" >> "$CHANGELOG_FILE"
        done
    fi
    
    # Refactoring/Performance/Chore
    CHANGES="$REFACTOR"$'\n'"$PERF"$'\n'"$CHORE"
    CHANGES=$(echo "$CHANGES" | grep -v "^$" || true)
    if [ -n "$CHANGES" ]; then
        echo "" >> "$CHANGELOG_FILE"
        echo "### Changed" >> "$CHANGELOG_FILE"
        echo "$CHANGES" | while read -r line; do
            [ -z "$line" ] && continue
            MSG=$(echo "$line" | sed 's/^[^:]*: //')
            echo "- $MSG" >> "$CHANGELOG_FILE"
        done
    fi
    
    # Documentation
    if [ -n "$DOCS" ]; then
        echo "" >> "$CHANGELOG_FILE"
        echo "### Documentation" >> "$CHANGELOG_FILE"
        echo "$DOCS" | while read -r line; do
            MSG=$(echo "$line" | sed 's/^docs[^:]*: //')
            echo "- $MSG" >> "$CHANGELOG_FILE"
        done
    fi
    
    PREV_TAG="$TAG"
done

# Add links at the end
echo "" >> "$CHANGELOG_FILE"
for TAG in $(echo "$TAGS" | tac); do
    echo "[$TAG]: $REPO_URL/releases/tag/$TAG" >> "$CHANGELOG_FILE"
done

echo "✅ CHANGELOG.md generated successfully!"
