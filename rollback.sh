#!/bin/bash

# IntraVox Rollback Script
# Rollback to a specific release version
#
# Usage: ./rollback.sh <tag-name>
# Example: ./rollback.sh v0.2.2-Footer

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
SERVER="145.38.191.31"
SERVER_USER="rdekker"
SERVER_PATH="/var/www/nextcloud/apps/intravox"

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
if [ $# -lt 1 ]; then
    print_error "Usage: $0 <tag-name>"
    echo ""
    echo "Available tags:"
    git tag -l | tail -10
    echo ""
    echo "Example: $0 v0.2.2-Footer"
    exit 1
fi

TAG_NAME=$1

echo "================================================"
echo "  IntraVox Rollback"
echo "================================================"
echo ""
print_warning "This will rollback your local repository and server to tag: ${TAG_NAME}"
echo ""
read -p "Are you sure you want to continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    print_error "Rollback cancelled"
    exit 1
fi
echo ""

# Step 1: Check if tag exists
print_step "Step 1: Checking if tag exists..."
if ! git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    print_error "Tag ${TAG_NAME} does not exist"
    echo ""
    echo "Available tags:"
    git tag -l
    exit 1
fi
print_success "Tag ${TAG_NAME} found"

# Step 2: Check for uncommitted changes
print_step "Step 2: Checking for uncommitted changes..."
if ! git diff-index --quiet HEAD --; then
    print_warning "You have uncommitted changes"
    git status --short
    echo ""
    read -p "Stash changes and continue? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git stash save "Pre-rollback stash $(date +%Y%m%d_%H%M%S)"
        print_success "Changes stashed"
    else
        print_error "Please commit or stash your changes first"
        exit 1
    fi
else
    print_success "Working directory is clean"
fi

# Step 3: Checkout the tag
print_step "Step 3: Checking out tag ${TAG_NAME}..."
git checkout "$TAG_NAME"
print_success "Checked out ${TAG_NAME}"

# Step 4: Show tag information
print_step "Step 4: Tag information..."
git show "$TAG_NAME" --quiet
echo ""

# Step 5: Ask about server deployment
read -p "Deploy this version to server ${SERVER}? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_step "Step 5: Deploying to server..."

    # Build
    print_step "Building frontend..."
    npm run build
    if [ $? -eq 0 ]; then
        print_success "Build completed"
    else
        print_error "Build failed"
        exit 1
    fi

    # Deploy
    print_step "Deploying to ${SERVER}..."
    ./deploy-dev.sh

    print_success "Deployment completed"
else
    print_warning "Skipped server deployment"
fi

echo ""
echo "================================================"
print_success "Rollback to ${TAG_NAME} completed!"
echo "================================================"
echo ""
print_warning "Important notes:"
echo "  • Your local repository is now in 'detached HEAD' state"
echo "  • To continue development, create a new branch:"
echo "    git checkout -b rollback-branch"
echo "  • Or return to main branch:"
echo "    git checkout main"
echo ""
echo "  • Data files (JSON) on the server are NOT rolled back"
echo "  • Only the application code has been rolled back"
echo ""
