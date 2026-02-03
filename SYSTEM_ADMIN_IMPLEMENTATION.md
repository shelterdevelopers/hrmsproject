# System Admin Role Implementation

## Overview
The System Admin role has been fully implemented with the following responsibilities:
- **Password resets** - Admin-only responsibility
- **Employee registration** - Admin-only responsibility  
- **All employee functions** - Admin can use the system as a regular employee too

---

## ✅ What Was Implemented

### 1. **Admin Role Helper Functions**
- Added `ROLE_ADMIN` constant to `RoleHelper.php`
- Added `is_admin()` method to check if user is System Admin
- Added `get_admin_id()` method to get admin employee ID

### 2. **Password Reset - Admin Only**
- ✅ `app/reset_employee_password.php` - Admin-only access
- ✅ `user.php` - Password reset button only visible to Admin
- ✅ `forgot_password.php` - Directs users to contact Admin (not HR)
- ✅ HR can no longer reset passwords

### 3. **Employee Registration - Admin Only**
- ✅ `app/add-user.php` - Admin-only access
- ✅ `add-user.php` - Admin-only access
- ✅ HR is **notified** when Admin creates a new employee
- ✅ HR can no longer register employees directly

### 4. **Profile Update Notifications**
- ✅ When employees update their profile, **both HR and Admin are notified**
- ✅ Notification includes employee name and change details
- ✅ Implemented in `app/update-employee-profile.php`

### 5. **Admin Dashboard & Navigation**
- ✅ Admin has **both admin functions AND employee functions**
- ✅ Dashboard shows:
  - Admin functions: Total employees, pending employees, register employee, pending repayments
  - Employee functions: My pending applications, courses completed, notifications
- ✅ Navigation includes:
  - Employee functions: Dashboard, Applications, My Attendance, Appraisals, Learning, Notifications
  - Admin functions: Manage Users, Register Employee, All Attendance

### 6. **Access Control Updates**
- ✅ `user.php` - Admin and HR can view, but only Admin can reset passwords
- ✅ `edit-user.php` - Admin and HR can edit users
- ✅ `pending-employees.php` - Admin and HR can approve pending employees
- ✅ `app/approve-user.php` - Admin and HR can approve users

---

## 📋 Admin Capabilities

### Admin Can:
- ✅ **Register new employees** (create accounts directly)
- ✅ **Reset employee passwords** (assist with forgotten passwords)
- ✅ **View all employees** (user management)
- ✅ **Edit employee profiles** (full access)
- ✅ **Approve pending employees** (if self-signup enabled)
- ✅ **View all attendance** (company-wide)
- ✅ **View pending repayments** (loan repayments)
- ✅ **All employee functions**:
  - View own attendance
  - Apply for leave
  - Apply for loans
  - View own applications
  - Complete learning courses
  - View appraisals
  - Update own profile
  - View notifications

### Admin Cannot:
- ❌ Approve leave applications (HR/Manager responsibility)
- ❌ Approve loan applications (HR/Finance responsibility)
- ❌ Manage learning courses (HR responsibility)
- ❌ Post announcements (HR/Manager responsibility)
- ❌ View activity logs (MD responsibility)

---

## 🔔 Notification System

### When Admin Creates Employee:
1. Admin registers new employee via "Add User" page
2. **HR receives notification**: "System Admin (Admin Name) has registered a new employee: [Employee Name] ([Job Title], [Department]). Please review the employee details."

### When Employee Updates Profile:
1. Employee updates their profile information
2. **HR receives notification**: "Employee [Name] has updated their profile information. Please review the changes."
3. **Admin receives notification**: "Employee [Name] has updated their profile information. Please review the changes."

---

## 🎯 Role Separation

### System Admin Responsibilities:
- Password resets
- Employee registration
- System maintenance
- User account management

### HR Manager Responsibilities:
- Leave/loan approvals
- Learning & Development
- Employee lifecycle management
- Policy enforcement
- Announcements

### Clear Separation:
- ✅ Admin handles **technical/account** tasks
- ✅ HR handles **people/business** tasks
- ✅ Both are notified of important changes
- ✅ Admin can still use system as employee

---

## 📝 Files Modified

### Core Files:
1. ✅ `app/Model/RoleHelper.php` - Added admin role constants and methods
2. ✅ `app/add-user.php` - Admin-only, notifies HR
3. ✅ `app/reset_employee_password.php` - Admin-only
4. ✅ `app/update-employee-profile.php` - Notifies HR and Admin
5. ✅ `index.php` - Admin dashboard with employee functions
6. ✅ `inc/nav.php` - Admin navigation with employee functions

### Access Control Files:
7. ✅ `user.php` - Admin and HR can view, only Admin can reset passwords
8. ✅ `add-user.php` - Admin-only
9. ✅ `edit-user.php` - Admin and HR can edit
10. ✅ `pending-employees.php` - Admin and HR can approve
11. ✅ `app/approve-user.php` - Admin and HR can approve
12. ✅ `forgot_password.php` - Directs to contact Admin

---

## 🚀 How to Create Admin Account

### Option 1: Direct Database Insert
```sql
INSERT INTO employee (
    first_name, last_name, id_no, date_of_birth, gender,
    email_address, phone_number, residential_address,
    emergency_contact_name, emergency_contact_number,
    next_of_kin_relationship, job_title, department,
    date_of_hire, employment_type, status, work_location,
    username, password, role, basic_salary
) VALUES (
    'Admin', 'User', 'ADMIN001', '1990-01-01', 'Male',
    'admin@company.com', '1234567890', 'Office Address',
    'Emergency Contact', '0987654321', 'Friend',
    'System Administrator', 'IT', CURDATE(), 'Full-time',
    'active', 'Office', 'admin', 
    '$2y$10$...', -- Use password_hash('yourpassword', PASSWORD_DEFAULT)
    'admin', 0
);
```

### Option 2: Use Add User Page (if you have another admin)
1. Login as existing admin
2. Go to "Register Employee"
3. Fill in admin details
4. Set role to "admin"
5. Save

### Option 3: Update Existing User
```sql
UPDATE employee SET role = 'admin' WHERE username = 'your_username';
```

---

## ✅ Testing Checklist

- [ ] Admin can login
- [ ] Admin sees both admin and employee functions in dashboard
- [ ] Admin can register new employees
- [ ] HR receives notification when admin creates employee
- [ ] Admin can reset employee passwords
- [ ] Only admin sees "Reset Password" button in user management
- [ ] Admin can view own attendance
- [ ] Admin can apply for leave
- [ ] Admin can apply for loans
- [ ] Admin can view own applications
- [ ] HR and Admin receive notifications when employee updates profile
- [ ] HR cannot reset passwords (access denied)
- [ ] HR cannot register employees (access denied)

---

## 📞 Support

If you need to:
- **Reset your admin password**: Contact database administrator
- **Create admin account**: Use one of the methods above
- **Change admin role**: Update database directly

**Last Updated**: January 2026
