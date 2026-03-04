# Admin Password & PIN Change System

## Overview
এই সিস্টেম Admin ইউজারদের জন্য Password এবং PIN পরিবর্তন করার সুবিধা প্রদান করে।

## Features
- ✅ Password Change (Direct - No old password verification)
- ✅ PIN Change (Direct - No old PIN verification)
- ✅ Password Confirmation
- ✅ PIN Confirmation
- ✅ Admin Panel Direct Access
- ✅ Responsive Design
- ✅ Error Handling
- ✅ Success Messages

## Files Created/Modified

### New Files:
1. `resources/views/admin/change-credentials.blade.php` - Password & PIN change page

### Modified Files:
1. `app/Http/Controllers/AdminController.php` - Added 3 new methods:
   - `showChangeCredentials()` - Show change credentials page
   - `updatePassword()` - Update admin password
   - `updatePin()` - Update admin PIN

2. `routes/web.php` - Added 3 new routes:
   - `GET /admin/change-credentials` - Show page
   - `POST /admin/update-password` - Update password
   - `POST /admin/update-pin` - Update PIN

3. `resources/views/admin.blade.php` - Updated sidebar link
4. `resources/views/admin/profile.blade.php` - Updated sidebar link
5. `resources/views/admin/manage-admins.blade.php` - Updated sidebar link

## Usage

### Access the Page:
1. Admin Dashboard এ লগইন করুন
2. Sidebar থেকে "Admin Account" > "Change Password & PIN" এ ক্লিক করুন
3. অথবা সরাসরি: `/admin/change-credentials`

### Change Password:
1. New Password দিন (minimum 6 characters)
2. Confirm New Password দিন
3. "Update Password" বাটনে ক্লিক করুন

### Change PIN:
1. New PIN দিন (4 digits)
2. Confirm New PIN দিন
3. "Update PIN" বাটনে ক্লিক করুন

## Security Features
- Direct admin access (no old password/PIN verification needed)
- Password hashing using Laravel's Hash facade
- Confirmation field validation
- Minimum password length (6 characters)
- PIN format validation (4 digits only)
- CSRF protection

## Validation Rules

### Password:
- New password: Required, minimum 6 characters, must be confirmed
- Password confirmation: Must match new password

### PIN:
- New PIN: Required, must be 4 digits, must be confirmed
- PIN confirmation: Must match new PIN

## Error Messages
- Validation errors for each field

## Success Messages
- "Password updated successfully!" - Password সফলভাবে পরিবর্তন হলে
- "PIN updated successfully!" - PIN সফলভাবে পরিবর্তন হলে

## Database
User model এ ইতিমধ্যে `password` এবং `pin` fields আছে এবং তারা hashed হিসেবে stored হয়।

## Testing
1. Admin হিসেবে লগইন করুন
2. Change Password & PIN page এ যান
3. Password change করার চেষ্টা করুন
4. PIN change করার চেষ্টা করুন
5. Wrong current password/PIN দিয়ে test করুন
6. Validation errors check করুন

## Notes
- শুধুমাত্র authenticated admin users এই feature ব্যবহার করতে পারবে
- Password এবং PIN আলাদাভাবে update করা যায়
- সব changes database এ hashed format এ save হয়
