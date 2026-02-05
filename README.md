# House Info / Incur

Simple web application for managing house-related information, photos, designs, and more.

## Quick Setup Guide

### 1. Database Setup

1. Make sure `schema.sql` is on your server.

2. Log in to MySQL as root:

```bash
mysql -u root -p

3. Run these SQL commands:

CREATE DATABASE house_info 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'house_user'@'localhost' 
  IDENTIFIED BY 'your_very_strong_password_here';

GRANT ALL PRIVILEGES ON house_info.* 
  TO 'house_user'@'localhost';

FLUSH PRIVILEGES;

EXIT;

Replace 'house_user' and the password with your own secure values.

4. Import the schema + initial data:

mysql -u house_user -p house_info < schema.sql

After this step, your database should include:

All required tables
Two example houses
Permanent placeholder items/categories

2. Web Files & Folders
Assuming you're using a typical Apache setup on Ubuntu/Debian:

# Navigate to web root (adjust if different)
cd /var/www/html

# Create app directory
mkdir -p incur

# Copy the PHP files
cp config.php index.php house.php incur/

# (Optional but recommended) Copy your logo
cp logo.png incur/

3. Create & Secure Upload Directories

cd incur

mkdir -p uploads/photos uploads/designs/thumbs

# Make sure the web server can write to these folders
chown -R www-data:www-data uploads
chmod -R 775 uploads

Note: If your web server runs as a different user (e.g. nginx, apache), replace www-data accordingly.
4. Configure config.php
Edit incur/config.php and update these lines with your real values:

<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'house_user');                    // ← your DB username
define('DB_PASS', 'your_very_strong_password_here'); // ← your strong password
define('DB_NAME', 'house_info');

// Site settings
define('SITE_URL', 'http://your-domain.com/incur');  // no trailing slash
define('UPLOAD_DIR', __DIR__ . '/uploads/');

5. Test It
Open your browser and visit:

http://your-server/incur/index.php

If everything is configured correctly, you should see the main page load.

## Troubleshooting Checklist

- [ ] Database credentials in `config.php` match what you created
- [ ] `uploads/` folders exist and are owned by `www-data` (or equivalent)
- [ ] PHP MySQL extension is enabled (`php -m | grep mysql`)
- [ ] `schema.sql` imported without errors
- [ ] Web server is configured to serve `/incur/` correctly
- [ ] No typos in file paths or permissions

Good luck — complaints can go straight to the trash can.
Made with mild annoyance somewhere in a dell.
@mytestingarena • 2026



If it works for you, great.  If it doesn't, send your complaints to the trashbin.  You're on your own from here.  I will update it as I add things, but I am not fixing anything for anyone. :P
