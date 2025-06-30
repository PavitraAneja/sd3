# SD3 - Trestle API Real Estate Project

A PHP application that displays property listings and open houses from Trestle API.

## Quick Setup

### 1. Install Requirements
- PHP 7.4+ with `mysqli` and `curl` extensions
- MySQL 5.7+

### 2. Setup Database
```bash
# Create database and user
mysql -u root -p
CREATE DATABASE sd3;
CREATE USER 'sd3_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sd3.* TO 'sd3_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import tables
mysql -u sd3_user -p sd3 < setup_tables.sql
```

### 3. Configure Database
Edit `includes/db_local.php` with your credentials:
```php
$username = 'sd3_user';
$password = 'your_password';
$dbname = 'sd3';
```

### 4. Start Server
```bash
cd sd3
php -S localhost:8000
```

### 5. Setup API Token
Visit: `http://localhost:8000/generate_token.php`

### 6. Sync Data
Visit: `http://localhost:8000/sync_properties.php`
Visit: `http://localhost:8000/sync_openhouse.php`

### 7. View App
Visit: `http://localhost:8000`

## Files
- `index.php` - Property listings
- `openhouse.php` - Open house listings
- `sync_properties.php` - Sync properties from API
- `sync_openhouse.php` - Sync open houses from API
- `generate_token.php` - Generate API token 