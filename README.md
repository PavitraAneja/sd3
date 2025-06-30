## Quick Setup

### 1. Setup Database Locally
```bash
# Open Terminal and run these commands to create database and user
mysql -u root -p
CREATE DATABASE sd3;
CREATE USER 'sd3_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON sd3.* TO 'sd3_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Create tables in the database
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
Open Browser and visit: `http://localhost:8000/api/generate_token.php`

### 6. Sync Data
Open Browser and visit: `http://localhost:8000/api/sync_properties.php`
Open Browser and visit: `http://localhost:8000/api/sync_openhouse.php`

### 7. View App
Visit: `http://localhost:8000`


### Frontend Pages
- **`index.php`** - Property listings and search
- **`openhouse.php`** - Open house calendar and details

### API Endpoints
- **`api/sync_properties.php`** - Fetches and stores property data from Trestle API
- **`api/sync_openhouse.php`** - Syncs open house information from Trestle API
- **`api/generate_token.php`** - Creates authentication token for API access 