#!/bin/bash
set -e

echo "=== Moodle 5.1.3 Container Starting ==="

# Ensure moodledata has correct permissions
chown -R www-data:www-data /var/moodledata
chmod -R 777 /var/moodledata

# Ensure web root has correct ownership
chown -R www-data:www-data /var/www/html

# Wait for MariaDB to be truly ready
echo "Waiting for MariaDB to accept connections..."
max_tries=30
count=0
while ! php -r "new mysqli('mariadb', 'moodleuser', 'moodlepass', 'moodle');" 2>/dev/null; do
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "ERROR: MariaDB not ready after ${max_tries} attempts. Exiting."
        exit 1
    fi
    echo "  Attempt $count/$max_tries - MariaDB not ready yet..."
    sleep 3
done
echo "MariaDB is ready!"

# Print loaded PHP extensions for verification
echo ""
echo "=== Loaded PHP Extensions ==="
php -m | sort
echo ""

# Verify critical extensions
echo "=== Verifying Moodle-required extensions ==="
REQUIRED_EXTS="curl gd intl mbstring soap sodium zip exif mysqli opcache"
ALL_OK=true
for ext in $REQUIRED_EXTS; do
    if php -m | grep -qi "^${ext}$"; then
        echo "  ✓ $ext"
    else
        echo "  ✗ $ext MISSING!"
        ALL_OK=false
    fi
done

if [ "$ALL_OK" = false ]; then
    echo "WARNING: Some required extensions are missing!"
fi

# Verify php.ini settings
echo ""
echo "=== PHP Configuration ==="
echo "  max_input_vars = $(php -r 'echo ini_get("max_input_vars");')"
echo "  upload_max_filesize = $(php -r 'echo ini_get("upload_max_filesize");')"
echo "  post_max_size = $(php -r 'echo ini_get("post_max_size");')"
echo "  max_execution_time = $(php -r 'echo ini_get("max_execution_time");')"
echo "  memory_limit = $(php -r 'echo ini_get("memory_limit");')"
echo ""

echo "=== Starting Apache ==="
exec apache2-foreground
