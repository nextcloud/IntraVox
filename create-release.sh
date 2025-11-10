#!/bin/bash

# IntraVox Release Creation Script
# Creates a tagged release with artifacts uploaded to Gitea
#
# Usage: ./create-release.sh <version> <label> <description>
# Example: ./create-release.sh 0.2.3 "Navigation" "Added navigation improvements"

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
GITEA_URL="https://gitea.rikdekker.nl"
GITEA_API="${GITEA_URL}/api/v1"
REPO_OWNER="rik"
REPO_NAME="IntraVox"

# Function to print colored output
print_step() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# Check if required arguments are provided
if [ $# -lt 3 ]; then
    print_error "Usage: $0 <version> <label> <description>"
    echo "Example: $0 0.2.3 Navigation 'Added navigation improvements'"
    exit 1
fi

VERSION=$1
LABEL=$2
DESCRIPTION=$3
TAG_NAME="v${VERSION}-${LABEL}"

# Check if Gitea token is set
if [ -z "$GITEA_TOKEN" ]; then
    print_error "GITEA_TOKEN environment variable is not set"
    echo "Please set it with: export GITEA_TOKEN='your-token-here'"
    echo "You can create a token at: ${GITEA_URL}/user/settings/applications"
    exit 1
fi

echo "================================================"
echo "  IntraVox Release Creation"
echo "================================================"
echo ""
print_step "Version: ${VERSION}"
print_step "Label: ${LABEL}"
print_step "Tag: ${TAG_NAME}"
echo ""

# Step 1: Check if we're on main branch
print_step "Step 1: Checking git branch..."
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "main" ]; then
    print_warning "You're not on the main branch (current: $CURRENT_BRANCH)"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi
print_success "Branch check completed"

# Step 2: Check for uncommitted changes
print_step "Step 2: Checking for uncommitted changes..."
if ! git diff-index --quiet HEAD --; then
    print_error "You have uncommitted changes. Please commit or stash them first."
    git status --short
    exit 1
fi
print_success "Working directory is clean"

# Step 3: Update version numbers
print_step "Step 3: Updating version numbers..."

# Update package.json
sed -i.bak "s/\"version\": \".*\"/\"version\": \"${VERSION}\"/" package.json
rm package.json.bak

# Update appinfo/info.xml
sed -i.bak "s/<version>.*<\/version>/<version>${VERSION}<\/version>/" appinfo/info.xml
rm appinfo/info.xml.bak

print_success "Version numbers updated to ${VERSION}"

# Step 4: Build the project
print_step "Step 4: Building frontend..."
npm run build
if [ $? -eq 0 ]; then
    print_success "Build completed successfully"
else
    print_error "Build failed"
    exit 1
fi

# Step 5: Create deployment package
print_step "Step 5: Creating deployment package..."
TEMP_DIR=$(mktemp -d)
PACKAGE_NAME="intravox-${VERSION}"
PACKAGE_DIR="${TEMP_DIR}/${PACKAGE_NAME}"
TARBALL_NAME="${PACKAGE_NAME}.tar.gz"

mkdir -p "$PACKAGE_DIR"

# Copy files
cp -r appinfo lib l10n templates css img js "$PACKAGE_DIR/"
cp README.md "$PACKAGE_DIR/" 2>/dev/null || true
cp CHANGELOG.md "$PACKAGE_DIR/" 2>/dev/null || true

# Create tarball
cd "$TEMP_DIR"
tar -czf "$TARBALL_NAME" "$PACKAGE_NAME"
mv "$TARBALL_NAME" "$OLDPWD/"
cd "$OLDPWD"

# Cleanup
rm -rf "$TEMP_DIR"

print_success "Deployment package created: ${TARBALL_NAME}"

# Step 6: Commit changes
print_step "Step 6: Committing changes..."
git add appinfo/info.xml package.json CHANGELOG.md

COMMIT_MSG="Release v${VERSION} - ${LABEL}

${DESCRIPTION}

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"

git commit -m "$COMMIT_MSG"
print_success "Changes committed"

# Step 7: Create git tag
print_step "Step 7: Creating git tag..."
TAG_MSG="Release v${VERSION} - ${LABEL}

${DESCRIPTION}

This release includes:
- Deployment tarball: ${TARBALL_NAME}
- Built JavaScript assets
- Full source code snapshot"

git tag -a "$TAG_NAME" -m "$TAG_MSG"
print_success "Git tag created: ${TAG_NAME}"

# Step 8: Push to Gitea
print_step "Step 8: Pushing to Gitea..."
git push origin main
git push origin --tags
print_success "Pushed to Gitea"

# Step 9: Create Gitea release
print_step "Step 9: Creating Gitea release..."

# Get the commit SHA for this tag
COMMIT_SHA=$(git rev-parse "$TAG_NAME")

# Create release via Gitea API
RELEASE_DATA=$(cat <<EOF
{
  "tag_name": "${TAG_NAME}",
  "target_commitish": "${COMMIT_SHA}",
  "name": "v${VERSION} - ${LABEL}",
  "body": "${DESCRIPTION}\n\n## Installation\n\n1. Download the \`${TARBALL_NAME}\` file\n2. Extract to your Nextcloud apps directory\n3. Enable the app: \`occ app:enable intravox\`\n4. Run setup: \`occ intravox:setup\`\n\n## Rollback\n\nTo rollback to this version:\n\`\`\`bash\ncd /path/to/nextcloud/apps\nrm -rf intravox\ntar -xzf ${TARBALL_NAME}\ncd /path/to/nextcloud\nocc app:enable intravox\n\`\`\`",
  "draft": false,
  "prerelease": false
}
EOF
)

RELEASE_RESPONSE=$(curl -s -X POST \
  -H "Authorization: token ${GITEA_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "${RELEASE_DATA}" \
  "${GITEA_API}/repos/${REPO_OWNER}/${REPO_NAME}/releases")

# Extract release ID
RELEASE_ID=$(echo "$RELEASE_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$RELEASE_ID" ]; then
    print_error "Failed to create Gitea release"
    echo "$RELEASE_RESPONSE"
    exit 1
fi

print_success "Gitea release created (ID: ${RELEASE_ID})"

# Step 10: Upload tarball as release asset
print_step "Step 10: Uploading deployment package..."

UPLOAD_RESPONSE=$(curl -s -X POST \
  -H "Authorization: token ${GITEA_TOKEN}" \
  -F "attachment=@${TARBALL_NAME}" \
  "${GITEA_API}/repos/${REPO_OWNER}/${REPO_NAME}/releases/${RELEASE_ID}/assets")

if echo "$UPLOAD_RESPONSE" | grep -q '"name"'; then
    print_success "Deployment package uploaded"
else
    print_error "Failed to upload deployment package"
    echo "$UPLOAD_RESPONSE"
fi

# Cleanup tarball
rm -f "$TARBALL_NAME"

echo ""
echo "================================================"
print_success "Release ${TAG_NAME} created successfully!"
echo "================================================"
echo ""
echo "📦 Release URL: ${GITEA_URL}/${REPO_OWNER}/${REPO_NAME}/releases/tag/${TAG_NAME}"
echo "🔗 Download: ${GITEA_URL}/${REPO_OWNER}/${REPO_NAME}/releases/download/${TAG_NAME}/${TARBALL_NAME}"
echo ""
echo "To deploy this release:"
echo "  ./deploy-dev.sh"
echo ""
