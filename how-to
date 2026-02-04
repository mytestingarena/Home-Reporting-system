setup the site.


copy the contexxt of the schema.sql file to the server.
open mysql
mysql -u root -p
CREATE DATABASE house_info;
CREATE USER 'username'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON house_info.* TO 'username'@'localhost';
FLUSH PRIVILEGES;

mysql -u your_db_user -p house_info < schema.sql

After running, your database house_info will be ready with structure + two default houses + permanent item placeholders.

Additional first-run steps

Copy all PHP files (config.php, index.php, house.php) to /var/www/html/incur/
Upload your logo.png to the same folder
Create upload directories:
mkdir -p uploads/photos uploads/designs/thumbs
chown -R www-data:www-data uploads
chmod -R 775 uploads

Create/update config.php with real database credentials
Visit http://your-server/incur/index.php — everything should be ready
