# ROTUFitTracker - Setup Instructions

## Problems Fixed ✅

1. **Role Value Inconsistencies** - Standardized role names across all files
2. **Variable Usage Order** - Fixed `$user_id` being used before definition
3. **Missing Logo Handling** - Added fallback for missing logo images
4. **Database Schema** - Updated SQL to create all required tables and columns

## Setup Steps

### 1. Start XAMPP
- Start **Apache** and **MySQL** services from XAMPP Control Panel

### 2. Create Database
Open phpMyAdmin (http://localhost/phpmyadmin) and run the complete SQL from `database_updates.sql`:

```sql
-- This will create the database and all required tables
CREATE DATABASE IF NOT EXISTS rotufit_tracker_db;
USE rotufit_tracker_db;

-- Creates users table with all columns
-- Creates training_schedules table
-- Creates training_types table
```

### 3. Access Your Application
- Navigate to: `http://localhost/rotufittracker/authentication/index.php`
- Register a new user with one of these roles:
  - **Chief Instructor** (full access)
  - **Officer** (full access)
  - **Trainer** (limited access)
  - **Cadet Officer** (limited access)

### 4. Test the Application
1. **Login** with your credentials
2. You should be redirected to **Homepage** ([homepage/index.php](homepage/index.php))
3. From there you can access:
   - **User Profile** - View/edit profile, upload photo, set height/weight
   - **Training Management** (Chief Instructors and Officers only)

## Common Issues & Solutions

### Issue: "Connection failed" error
**Solution:** Check that MySQL is running in XAMPP and database credentials in [authentication/config.php](authentication/config.php) are correct:
```php
$host = "localhost";
$user = "root";
$password = "";
$database = "rotufit_tracker_db";
```

### Issue: "Table doesn't exist" error
**Solution:** Run the complete SQL from [database_updates.sql](database_updates.sql) in phpMyAdmin

### Issue: Profile picture upload not working
**Solution:** Ensure the `user_profile/uploads/` directory exists and has write permissions

### Issue: Blank page or PHP errors
**Solution:** 
1. Enable error display in PHP by adding to the top of your PHP files:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
2. Check Apache error logs in `C:\xampp\apache\logs\error.log`

## File Structure

```
rotufittracker/
├── authentication/
│   ├── config.php          (Database connection)
│   ├── index.php           (Login/Register page)
│   ├── login_register.php  (Authentication handler)
│   ├── logout.php          (Logout handler)
│   ├── style.css
│   └── script.js
├── homepage/
│   ├── index.php           (Main dashboard after login)
│   └── homepage.css
├── user_profile/
│   ├── dashboard.php       (User profile page)
│   ├── profile.php
│   ├── dashboard.css
│   └── uploads/            (Profile pictures directory)
├── training_management/
│   ├── training_management.php (Training schedule management)
│   └── training.css
├── database_updates.sql    (Database schema)
└── SETUP_INSTRUCTIONS.md   (This file)
```

## Role Permissions

| Feature | Chief Instructor | Officer | Trainer | Cadet Officer |
|---------|-----------------|---------|---------|---------------|
| Login/Register | ✅ | ✅ | ✅ | ✅ |
| User Profile | ✅ | ✅ | ✅ | ✅ |
| Training Management | ✅ | ✅ | ❌ | ❌ |
| Create Schedules | ✅ | ✅ | ❌ | ❌ |
| Delete Schedules | ✅ | ✅ | ❌ | ❌ |

## Next Steps

1. **Add Logo**: Place a logo image named `ROTUFitTrackerLogo.png` in:
   - `user_profile/` directory
   - `training_management/` directory

2. **Test All Features**:
   - User registration
   - Login
   - Profile updates
   - Photo uploads
   - Training schedule creation (for CI/Officers)

3. **Customize**: Modify CSS files to match your branding

## Need Help?

Check the browser console (F12) for JavaScript errors and the Network tab for failed requests.
