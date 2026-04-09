#!/bin/bash
#sed -i 's/\r$//' fix_jk.sh; chmod +x fix_jk.sh; sudo ./fix_jk.sh;
# ── CONFIG ─────────────────────────────
PROJECT_PATH="/var/www/dppic.com/gallery/home"
FTP_USER="suadmin"
WEB_USER="www-data"
WEB_GROUP="www-data"


echo "🔧 Fixing ownership..."
cd "$PROJECT_PATH" || exit

# Set owner to FTP user and group to web server
chown -R $FTP_USER:$WEB_GROUP $PROJECT_PATH

echo "🔧 Setting directory permissions..."

# Directories → 775
find $PROJECT_PATH -type d -exec chmod 775 {} \;

echo "🔧 Setting file permissions..."

# Files → 664
find $PROJECT_PATH -type f -exec chmod 664 {} \;

echo "🔧 Setting special permissions..."

# Ensure output folder writable
mkdir -p $PROJECT_PATH/output
chmod -R 775 $PROJECT_PATH/output
chown -R $FTP_USER:$WEB_GROUP $PROJECT_PATH/output

echo "🔧 Setting setgid bit (inherit group)..."

# Important: new files inherit www-data group
find $PROJECT_PATH -type d -exec chmod g+s {} \;

echo "✅ Permissions fixed successfully!"