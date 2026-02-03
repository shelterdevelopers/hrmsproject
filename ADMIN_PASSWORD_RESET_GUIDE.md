# Admin-Assisted Password Reset Guide

## Overview
The HRMS system uses an **admin-assisted password reset** approach instead of email-based reset. This is simpler, more secure, and doesn't require email configuration.

---

## 🔐 Password Reset Process

### For Employees (Forgot Password)

1. **Employee clicks "Forgot Password"** on login page
2. **System directs them to contact HR/Admin** (no email needed)
3. **Employee contacts HR Manager or System Administrator**
4. **HR/Admin resets password** using the system
5. **Employee receives notification** and can login with new password

### For Admin/HR (Resetting Passwords)

**Option 1: Quick Reset from User Management Page**
1. Go to **Users** page (`user.php`)
2. Find the employee in the list
3. Click the **Actions** menu (three dots)
4. Select **"Reset Password"**
5. Enter new password (minimum 8 characters)
6. Confirm password
7. Click **"Reset Password"**
8. Employee is notified automatically

**Option 2: Reset via Edit User Page**
1. Go to **Users** page
2. Click **"Edit User"** for the employee
3. Scroll to **Password** field
4. Enter new password (leave blank to keep current)
5. Save changes

---

## 👥 Employee Registration

### Current Approach: Admin/HR Registers Employees

**HR Manager or System Admin can:**
- ✅ Create new employee accounts directly
- ✅ Set all employee details (job title, department, manager, salary)
- ✅ Assign username and initial password
- ✅ Set employee status to "active" immediately (no approval needed)

**Steps:**
1. Go to **Add User** page (`add-user.php`)
2. Fill in all employee information
3. Set username and password
4. Assign department, manager, role
5. Save - employee can login immediately

**Benefits:**
- ✅ No pending accounts
- ✅ Better control over account creation
- ✅ Immediate access for new employees
- ✅ HR verifies information before creating account

---

## 🔑 Access Control

### Who Can Reset Passwords?
- ✅ **HR Manager** - Can reset any employee's password
- ✅ **System Admin** - Can reset any employee's password
- ❌ **Department Managers** - Cannot reset passwords
- ❌ **Employees** - Cannot reset other employees' passwords

### Who Can Register Employees?
- ✅ **HR Manager** - Can create new employee accounts
- ✅ **System Admin** - Can create new employee accounts
- ❌ **Department Managers** - Cannot create accounts
- ❌ **Employees** - Cannot create accounts (self-signup disabled or requires approval)

---

## 📋 Features Implemented

### ✅ Password Reset Features
1. **Forgot Password Page** - Directs users to contact HR/Admin
2. **Quick Password Reset Modal** - In user management page
3. **Password Reset via Edit User** - Full user editing with password reset
4. **Activity Logging** - All password resets are logged
5. **Notifications** - Employees notified when password is reset

### ✅ Employee Registration Features
1. **Add User Page** - HR/Admin can create accounts directly
2. **Complete Employee Setup** - All fields can be set during creation
3. **Immediate Activation** - No approval needed when created by HR/Admin
4. **Role Assignment** - HR/Admin assigns roles during creation

---

## 🚫 What's Disabled

### Self-Service Password Reset
- ❌ **Email-based reset** - Not implemented (for future)
- ❌ **Token-based reset** - Not used (admin-assisted instead)
- ✅ **Admin-assisted reset** - Current approach (simpler, more secure)

### Self-Signup (Optional)
- Currently employees can self-signup and wait for approval
- **Recommendation**: Consider disabling self-signup and have HR register all employees
- This gives better control and security

---

## 💡 Best Practices

1. **Password Security**
   - Minimum 8 characters required
   - Employees should change password after admin reset
   - HR should verify employee identity before resetting

2. **Account Creation**
   - HR should verify all information before creating account
   - Set appropriate role and department
   - Assign manager during creation

3. **Documentation**
   - Keep record of password resets (logged in activity log)
   - Notify employees when password is reset
   - Encourage employees to change password after reset

---

## 📝 Usage Examples

### Example 1: Employee Forgot Password
```
Employee: "I forgot my password"
HR: "No problem, let me reset it for you. What's your username?"
HR: [Opens user management, finds employee, resets password]
HR: "Your password has been reset to [temporary password]. Please login and change it."
Employee: [Logs in, changes password]
```

### Example 2: New Employee Onboarding
```
HR: [Receives new employee information]
HR: [Goes to Add User page]
HR: [Creates account with all details]
HR: [Sets temporary password]
HR: "Your account is ready. Username: [username], Password: [temp password]"
Employee: [Logs in, changes password]
```

---

## 🔍 Security Notes

- ✅ All password resets are logged in activity log
- ✅ Employees are notified when password is reset
- ✅ HR/Admin must be authenticated to reset passwords
- ✅ Password complexity enforced (minimum 8 characters)
- ✅ Employees cannot reset their own password without current password (via profile edit)

---

## 📞 Support

If employees have issues:
1. Contact HR Manager for password reset
2. Contact System Administrator for technical issues
3. All password resets are logged for audit purposes

**Last Updated**: January 2026
