# Password Reset Feature - Setup & Testing Guide

## ✅ Files Created

1. **authentication/email.php** - Email sending helper functions
2. **authentication/forgot_password.php** - Request password reset page
3. **authentication/reset_password.php** - Set new password page
4. **database_updates.sql** - Updated with password_resets table

## 📋 Setup Steps

### Step 1: Update Database
Run this SQL in phpMyAdmin (http://localhost/phpmyadmin):

```sql
USE rotufit_tracker_db;

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);
```

Or run the complete [database_updates.sql](../database_updates.sql) file.

### Step 2: Verify XAMPP is Running
- ✅ Apache - Running
- ✅ MySQL - Running

### Step 3: Test the Feature

## 🧪 Testing Instructions

### Test 1: Request Password Reset

1. Go to login page: `http://localhost/rotufittracker/authentication/index.php`
2. Click **"Forgot Password?"** link
3. Enter an existing user email
4. Click **"Send Reset Link"**
5. **Expected Result:** 
   - Success message appears
   - Since email server isn't configured, you'll see a clickable reset link on screen
   - **Copy this link!**

### Test 2: Reset Password

1. Click the reset link (or paste in browser)
2. Enter new password (min 6 characters)
3. Confirm the password
4. Click **"Reset Password"**
5. **Expected Result:**
   - Success message appears
   - Auto-redirect to login page after 3 seconds

### Test 3: Login with New Password

1. On login page, enter your email
2. Enter the NEW password you just set
3. Click **"Login"**
4. **Expected Result:**
   - Successfully logged in
   - Redirected to homepage

## 🔐 Security Features

✅ **Secure tokens** - 64 character random tokens
✅ **Expiration** - Links expire after 1 hour
✅ **One-time use** - Tokens marked as used after reset
✅ **Password hashing** - Passwords stored with bcrypt
✅ **SQL injection protection** - Prepared statements used
✅ **Token validation** - Checks expiry and usage status

## 📧 Email Configuration (Optional)

For production use with real email sending, edit [authentication/config.php](authentication/config.php):

```php
$smtp = [
    'host' => 'smtp.gmail.com',        // Your SMTP server
    'port' => 587,                      // SMTP port
    'username' => 'your@email.com',     // Your email
    'password' => 'your_app_password',  // Your password
    'secure' => 'tls',                  // tls or ssl
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'ROTUFitTracker'
];
```

**For Gmail:**
1. Enable 2-factor authentication
2. Create App Password: https://myaccount.google.com/apppasswords
3. Use the app password in config

## 🎯 Quick Test Script

Open browser console and run SQL directly in phpMyAdmin to check:

```sql
-- See all password reset requests
SELECT * FROM password_resets;

-- Clean up old/expired tokens
DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1;
```

## 🐛 Troubleshooting

### Issue: "Table password_resets doesn't exist"
**Solution:** Run Step 1 SQL in phpMyAdmin

### Issue: Reset link doesn't work
**Solution:** 
1. Check if token exists: `SELECT * FROM password_resets WHERE token='YOUR_TOKEN'`
2. Check if token expired: Look at `expires_at` column
3. Check if already used: Look at `used` column

### Issue: Can't login after reset
**Solution:**
1. Verify password was updated: Check `users` table
2. Try the old password - reset might have failed
3. Reset again with a new request

### Issue: Email not sending
**Solution:** This is normal for local development! The reset link will be displayed on screen instead. For production, configure SMTP settings.

## 📱 User Flow Diagram

```
User forgets password
    ↓
Click "Forgot Password?"
    ↓
Enter email address
    ↓
Receive reset link (on screen for testing / via email for production)
    ↓
Click reset link
    ↓
Enter new password
    ↓
Password updated
    ↓
Login with new password
    ↓
Success! ✅
```

## 🔄 Token Lifecycle

1. **Created** - User requests reset, token generated, expires_at = NOW + 1 hour
2. **Valid** - Token can be used for password reset
3. **Used** - After successful reset, marked as used (used = 1)
4. **Expired** - After 1 hour, link no longer works
5. **Deleted** - When user requests new reset, old tokens deleted

## 📝 Database Structure

```
password_resets table:
- id (Primary Key)
- email (User's email)
- token (Random 64-char hex string)
- created_at (When token was created)
- expires_at (When token expires - created_at + 1 hour)
- used (0 = unused, 1 = already used)
```

## ✨ Features Included

✅ Forgot password link on login page
✅ Email validation
✅ Secure token generation
✅ Token expiration (1 hour)
✅ One-time use tokens
✅ Password strength validation
✅ Password confirmation
✅ Auto-redirect after success
✅ User-friendly error messages
✅ Responsive design
✅ Local testing support (shows link on screen)

## 🚀 Ready to Test!

Everything is set up. Just follow the testing instructions above!
