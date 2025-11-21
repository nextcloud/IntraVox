#!/bin/bash

# Upload demo content to server
SERVER="rdekker@145.38.191.31"
SSH_KEY="~/.ssh/sur"
REMOTE_PATH="/var/www/nextcloud/data/__groupfolders/4/files/en"

echo "🚀 Uploading IntraVox Demo Content"
echo "=================================="

# Create en directory if it doesn't exist
echo "📁 Creating English language directory..."
ssh -i $SSH_KEY $SERVER "sudo mkdir -p $REMOTE_PATH"

# Upload JSON files
echo "📄 Uploading page files..."
for file in *.json; do
  echo "  - $file"
  scp -i $SSH_KEY "$file" $SERVER:/tmp/
  ssh -i $SSH_KEY $SERVER "sudo mv /tmp/$file $REMOTE_PATH/"
done

# Upload images (if any)
if [ -d "images" ]; then
  echo "🖼️  Uploading images..."
  for img in images/*.jpg images/*.png; do
    if [ -f "$img" ]; then
      echo "  - $(basename $img)"
      scp -i $SSH_KEY "$img" $SERVER:/tmp/
      ssh -i $SSH_KEY $SERVER "sudo mv /tmp/$(basename $img) $REMOTE_PATH/images/"
    fi
  done 2>/dev/null || true
fi

# Set permissions
echo "🔐 Setting permissions..."
ssh -i $SSH_KEY $SERVER "sudo chown -R www-data:www-data $REMOTE_PATH && sudo chmod -R 770 $REMOTE_PATH"

# Scan files in Nextcloud
echo "🔍 Scanning files in Nextcloud..."
ssh -i $SSH_KEY $SERVER "sudo -u www-data php /var/www/nextcloud/occ files:scan --path=/__groupfolders/4/files/en"

echo ""
echo "✅ Demo content uploaded successfully!"
echo ""
echo "🌐 Access your demo intranet at:"
echo "   https://145.38.191.31/apps/intravox"
echo ""
