# Comprehensive Testing Checklist for Shelter HRMS

## Overview
This checklist covers all functionality, critical corner cases, and edge cases to ensure the application is user-ready. Test with different user roles and various data scenarios.

---

## 1. Authentication & User Management

### 1.1 Login
- [ ] **Valid credentials** - Login with correct username and password
- [ ] **Invalid username** - Try login with non-existent username
- [ ] **Invalid password** - Try login with wrong password
- [ ] **Empty fields** - Submit login form with empty username/password
- [ ] **SQL injection attempt** - Try `' OR '1'='1` in username field
- [ ] **XSS attempt** - Try `<script>alert('xss')</script>` in username field
- [ ] **Case sensitivity** - Test if username/password are case-sensitive
- [ ] **Session persistence** - Login, close browser, reopen - should stay logged in
- [ ] **Multiple sessions** - Login from two different browsers/devices
- [ ] **Logout functionality** - Verify logout clears session

### 1.2 Signup
- [ ] **Complete valid signup** - Fill all required fields correctly
- [ ] **Missing required fields** - Try submitting with empty required fields
- [ ] **Duplicate username** - Try signing up with existing username
- [ ] **Duplicate ID number** - Try signing up with existing ID number
- [ ] **Duplicate email** - Try signing up with existing email
- [ ] **Invalid email format** - Try `invalid-email`, `@domain.com`, `user@`
- [ ] **Invalid date of birth** - Try future date, invalid date format
- [ ] **Invalid phone number** - Try letters, special characters
- [ ] **Date of hire before date of birth** - Logical validation
- [ ] **Negative numbers** - Try -1 for children/dependents
- [ ] **Very long text** - Try 10,000+ character strings in text fields
- [ ] **Special characters** - Test with `!@#$%^&*()` in name fields
- [ ] **Unicode characters** - Test with emojis, non-English characters
- [ ] **Marital status toggle** - Verify spouse field appears/disappears
- [ ] **Terms agreement** - Submit without checking terms checkbox
- [ ] **OneDrive document link** - Test with invalid URL format

### 1.3 Password Management
- [ ] **Forgot password** - Request password reset
- [ ] **Password reset link** - Verify link works and expires correctly
- [ ] **Weak password** - Try password with less than 8 characters
- [ ] **Password visibility toggle** - If implemented

---

## 2. Role-Based Access Control (RBAC)

### 2.1 Regular Employee
- [ ] **Dashboard access** - Verify employee sees correct dashboard
- [ ] **Can apply for leave** - Verify leave application form works
- [ ] **Can apply for loan** - Verify loan application form works
- [ ] **Cannot approve applications** - Verify no approval tabs visible
- [ ] **Cannot access admin pages** - Try direct URL access to admin pages
- [ ] **Cannot see other employees' data** - Privacy check
- [ ] **Can view own profile** - Profile access
- [ ] **Can view own attendance** - Attendance tab works

### 2.2 Department Manager (Sales, Operations, etc.)
- [ ] **Can approve department leave** - First approval for employee leave
- [ ] **Cannot approve loans** - Verify loans tab not visible
- [ ] **Can see department attendance** - Department attendance tab
- [ ] **Can post department announcements** - Announcement functionality
- [ ] **Can create appraisals** - Appraisal creation for department employees
- [ ] **Cannot approve own leave** - Manager cannot approve their own leave
- [ ] **Manager leave workflow** - Manager's leave goes to HR → MD
- [ ] **Cannot see other departments' data** - Privacy check

### 2.3 Finance Manager
- [ ] **Can approve department leave** - First approval for Finance dept leave
- [ ] **Can approve loans** - Second approval after HR (except own loan)
- [ ] **Cannot approve own loan** - Finance Manager's loan goes to MD
- [ ] **Can see all loan applications** - After HR approval
- [ ] **Can create appraisals** - For Finance department employees

### 2.4 HR Manager
- [ ] **Can approve manager leave** - First approval for all managers' leave
- [ ] **Can approve employee leave** - Second approval after department manager
- [ ] **Can approve all loans** - First approval for all loans
- [ ] **Can post company announcements** - Company-wide announcements
- [ ] **Can view all employees** - Employee management
- [ ] **Can approve signups** - Pending user approvals
- [ ] **HR leave workflow** - HR leave goes directly to MD only

### 2.5 Managing Director (MD)
- [ ] **Read-only access** - Verify MD account is read-only where appropriate
- [ ] **Can approve HR leave** - Only approval needed
- [ ] **Can approve manager leave** - Second approval after HR
- [ ] **Can approve Finance Manager loan** - Second approval after HR
- [ ] **Can appraise all managers** - Including HR Manager
- [ ] **Can view activity log** - Executive dashboard/insights
- [ ] **Cannot see Applications tab** - Should not appear
- [ ] **Cannot see Learning & Development tab** - Should not appear
- [ ] **Can post/view company announcements** - Announcement access

---

## 3. Leave Applications

### 3.1 Leave Application Submission
- [ ] **Normal leave** - Apply for normal leave with valid dates
- [ ] **Sick leave** - Apply for sick leave
- [ ] **Special leave** - Apply for special case leave
- [ ] **Maternity leave** - Apply for maternity leave (if applicable)
- [ ] **End date before start date** - Invalid date range
- [ ] **Start date in the past** - Past date validation
- [ ] **Leave exceeds balance** - Request more days than available
- [ ] **Zero days** - Start and end date same day
- [ ] **Very long leave** - Request 100+ days
- [ ] **Weekend dates** - Leave spanning weekends
- [ ] **Holiday dates** - Leave spanning public holidays
- [ ] **Empty reason field** - Submit without reason
- [ ] **Very long reason** - 5000+ character reason
- [ ] **Special characters in reason** - Test with HTML, SQL, scripts

### 3.2 Leave Approval Workflows

#### Regular Employee Leave
- [ ] **Department Manager approval** - First approval works
- [ ] **HR approval after manager** - Second approval works
- [ ] **Manager denies** - Application ends, employee notified
- [ ] **HR denies after manager approves** - Application ends, employee notified
- [ ] **Manager approves, HR denies** - Workflow stops at HR denial

#### Manager Leave (Sales, Operations, etc.)
- [ ] **HR first approval** - HR can approve manager leave
- [ ] **MD second approval** - MD can approve after HR
- [ ] **HR denies** - Application ends, manager notified
- [ ] **MD denies after HR approves** - Application ends, manager notified
- [ ] **MD tries to approve before HR** - Should show error
- [ ] **Department manager tries to approve manager leave** - Should fail

#### HR Manager Leave
- [ ] **MD only approval** - MD can approve HR leave directly
- [ ] **HR tries to approve own leave** - Should fail
- [ ] **Other managers try to approve HR leave** - Should fail

#### Finance Manager Leave
- [ ] **HR first approval** - HR approves Finance Manager leave
- [ ] **MD second approval** - MD approves after HR
- [ ] **Same workflow as other managers** - Verify consistency

### 3.3 Leave Balance Deduction
- [ ] **Balance decreases on approval** - Verify deduction happens
- [ ] **Correct leave type deducted** - Normal, sick, special, maternity
- [ ] **Balance doesn't go negative** - Verify GREATEST(0, ...) works
- [ ] **Multiple leaves** - Apply and approve multiple leaves
- [ ] **Leave days recorded in attendance** - Check attendance table

### 3.4 Leave Notifications
- [ ] **Employee notified on manager approval** - Notification received
- [ ] **Employee notified on HR approval** - Notification received
- [ ] **Employee notified on denial** - Notification received
- [ ] **Manager notified when employee applies** - Notification received
- [ ] **HR notified when manager approves** - Notification received
- [ ] **MD notified when HR approves manager leave** - Notification received

---

## 4. Loan Applications

### 4.1 Loan Application Submission
- [ ] **Valid loan amount** - Apply with reasonable amount
- [ ] **Zero amount** - Try $0 loan
- [ ] **Negative amount** - Try -$1000
- [ ] **Very large amount** - Try $1,000,000
- [ ] **Decimal amounts** - Try $100.50
- [ ] **Empty amount field** - Submit without amount
- [ ] **Non-numeric amount** - Try "abc" as amount
- [ ] **Empty reason** - Submit without reason
- [ ] **Very long reason** - 5000+ characters
- [ ] **Special characters** - Test with HTML, SQL, scripts

### 4.2 Loan Approval Workflows

#### Regular Employee Loan
- [ ] **HR first approval** - HR can approve
- [ ] **Finance Manager second approval** - Finance Manager can approve after HR
- [ ] **HR denies** - Application ends, employee notified
- [ ] **Finance Manager denies after HR** - Application ends, employee notified
- [ ] **Finance Manager tries to approve before HR** - Should show error

#### Manager Loan (Non-Finance)
- [ ] **HR first approval** - HR approves manager loan
- [ ] **Finance Manager second approval** - Finance Manager approves after HR
- [ ] **Same as employee workflow** - Verify consistency

#### Finance Manager Loan
- [ ] **HR first approval** - HR approves Finance Manager loan
- [ ] **MD second approval** - MD approves after HR (not Finance Manager)
- [ ] **Finance Manager cannot approve own loan** - Should fail
- [ ] **MD tries to approve before HR** - Should show error

### 4.3 Loan Disbursement
- [ ] **Disbursed amount set on approval** - Verify amount recorded
- [ ] **Outstanding balance equals amount** - Initial balance correct
- [ ] **Next payment date set** - 1 month from approval date
- [ ] **Loan status updated** - Status changes to approved/denied

### 4.4 Loan Notifications
- [ ] **Employee notified on HR approval** - Notification with timestamp
- [ ] **Employee notified on Finance Manager approval** - Final notification
- [ ] **Employee notified on MD approval (FM loan)** - Final notification
- [ ] **Employee notified on denial** - Denial notification
- [ ] **HR notified on Finance Manager approval** - Disbursement notification
- [ ] **HR notified on MD approval (FM loan)** - Disbursement notification

---

## 5. Attendance Management

### 5.1 Check-in/Check-out
- [ ] **Check-in works** - Record check-in time
- [ ] **Check-out works** - Record check-out time
- [ ] **Multiple check-ins same day** - Verify only first one counts
- [ ] **Check-out without check-in** - Should handle gracefully
- [ ] **Check-in after check-out** - Should allow (new session)
- [ ] **Timezone handling** - Verify Harare timezone used
- [ ] **Auto check-out at 5 PM** - Verify automatic check-out works

### 5.2 Attendance Records
- [ ] **Attendance displayed correctly** - View own attendance
- [ ] **Leave days marked** - Approved leave shows as 'leave' status
- [ ] **Date range filtering** - Filter by month/year
- [ ] **Manager sees department attendance** - Department view works
- [ ] **HR sees all attendance** - Company-wide view

### 5.3 Attendance Notifications
- [ ] **Manager notified on check-in** - Notification received
- [ ] **MD notified on check-in** - Activity notification
- [ ] **Manager notified on check-out** - Notification received
- [ ] **MD notified on check-out** - Activity notification

---

## 6. Appraisals

### 6.1 Appraisal Creation
- [ ] **Manager creates appraisal** - For department employee
- [ ] **MD creates appraisal** - For any manager (including HR)
- [ ] **Cannot create for self** - Self-appraisal prevention
- [ ] **Cannot create for MD** - MD not in dropdown
- [ ] **Dropdown shows correct employees** - Only eligible employees
- [ ] **Empty fields validation** - Required fields check
- [ ] **Date validation** - Appraisal date logic

### 6.2 Appraisal Completion
- [ ] **Manager completes appraisal** - Fill all sections
- [ ] **MD completes appraisal** - Fill all sections
- [ ] **Save draft** - If implemented
- [ ] **Submit final** - Final submission
- [ ] **Print appraisal** - Print functionality works

### 6.3 Appraisal Viewing
- [ ] **Employee views own appraisal** - Access granted
- [ ] **Manager views department appraisals** - Access granted
- [ ] **MD views all appraisals** - Access granted
- [ ] **Cannot view other departments** - Privacy check

---

## 7. Announcements

### 7.1 Department Announcements
- [ ] **Manager posts announcement** - Department announcement works
- [ ] **Only department sees announcement** - Privacy check
- [ ] **Empty announcement** - Validation check
- [ ] **Very long announcement** - 10,000+ characters
- [ ] **Special characters** - HTML, scripts, SQL
- [ ] **Date/time display** - Correct timestamp

### 7.2 Company Announcements
- [ ] **HR posts company announcement** - Company-wide works
- [ ] **MD posts company announcement** - MD can post
- [ ] **All employees see** - Visibility check
- [ ] **View announcements** - View functionality works

---

## 8. Learning & Development

### 8.1 Course Suggestions
- [ ] **Employee suggests course** - Suggestion submission works
- [ ] **Suggestion goes to HR** - Direct to HR Manager
- [ ] **Empty fields validation** - Required fields
- [ ] **Very long description** - 5000+ characters
- [ ] **Invalid URL** - Course link validation
- [ ] **View own suggestions** - Employee can see their suggestions
- [ ] **HR sees all suggestions** - HR view works

### 8.2 Course Approval
- [ ] **HR approves suggestion** - Approval works
- [ ] **HR denies suggestion** - Denial works
- [ ] **Employee notified** - Notification received

---

## 9. Activity Log & Dashboard

### 9.1 Employee Dashboard
- [ ] **Leave balance displayed** - Correct balances shown
- [ ] **Loan balance displayed** - Correct balances shown
- [ ] **Pending applications** - Shows pending status
- [ ] **Recent activity** - Activity feed works

### 9.2 Manager Dashboard
- [ ] **Department stats** - Employee count, attendance stats
- [ ] **Pending approvals** - Shows applications needing approval
- [ ] **Team attendance** - Department attendance overview

### 9.3 HR Dashboard
- [ ] **Company-wide stats** - All employees, departments
- [ ] **Pending approvals** - All pending applications
- [ ] **Activity overview** - System-wide activity

### 9.4 MD Dashboard (Executive Insights)
- [ ] **KPI cards** - Key metrics displayed
- [ ] **Activity growth** - Trends shown
- [ ] **Action items** - Pending approvals highlighted
- [ ] **Attendance overview** - Today's attendance
- [ ] **Department insights** - Department statistics
- [ ] **Activity distribution** - Charts/graphs work
- [ ] **Key insights** - Summary information
- [ ] **No micromanaging details** - High-level view only

### 9.5 Activity Log
- [ ] **All activities logged** - Check-ins, approvals, applications
- [ ] **Correct timestamps** - Harare timezone
- [ ] **Filter by type** - Filter functionality
- [ ] **Pagination works** - Large datasets
- [ ] **Search functionality** - If implemented

---

## 10. Notifications

### 10.1 Notification Display
- [ ] **Notifications appear** - Real-time or on page load
- [ ] **Unread count** - Badge shows unread count
- [ ] **Mark as read** - Read status updates
- [ ] **Notification types** - Application, activity, team_attendance
- [ ] **Timestamp display** - Correct time shown

### 10.2 Notification Triggers
- [ ] **Application submitted** - Notify relevant approvers
- [ ] **Application approved** - Notify applicant
- [ ] **Application denied** - Notify applicant
- [ ] **Check-in/out** - Notify manager and MD
- [ ] **Course suggestion** - Notify HR

---

## 11. Data Integrity & Security

### 11.1 SQL Injection
- [ ] **All input fields** - Try `' OR '1'='1` in every form
- [ ] **Search fields** - SQL injection attempts
- [ ] **URL parameters** - Manipulate GET parameters
- [ ] **Prepared statements** - Verify all queries use prepared statements

### 11.2 XSS (Cross-Site Scripting)
- [ ] **All text inputs** - Try `<script>alert('xss')</script>`
- [ ] **Textarea fields** - XSS attempts
- [ ] **URL parameters** - XSS in GET parameters
- [ ] **Output encoding** - Verify `htmlspecialchars()` used

### 11.3 CSRF (Cross-Site Request Forgery)
- [ ] **Form submissions** - Verify CSRF tokens if implemented
- [ ] **State-changing operations** - Approvals, applications

### 11.4 Authorization
- [ ] **Direct URL access** - Try accessing other users' data via URL
- [ ] **Role escalation** - Try changing role in session
- [ ] **Privilege checks** - Verify all operations check permissions

### 11.5 Data Validation
- [ ] **Input sanitization** - All inputs cleaned
- [ ] **Type validation** - Numbers, dates, emails
- [ ] **Length validation** - Max length enforcement
- [ ] **Required fields** - Server-side validation

---

## 12. Edge Cases & Corner Cases

### 12.1 Date/Time Edge Cases
- [ ] **Leap year dates** - February 29
- [ ] **Year boundary** - December 31 to January 1
- [ ] **Timezone changes** - DST handling
- [ ] **Midnight boundary** - 23:59:59 to 00:00:00
- [ ] **Very old dates** - 1900-01-01
- [ ] **Very future dates** - 2100-12-31

### 12.2 Numeric Edge Cases
- [ ] **Zero values** - 0 days, $0 amount
- [ ] **Negative values** - Should be rejected
- [ ] **Very large numbers** - Integer overflow
- [ ] **Decimal precision** - Currency calculations
- [ ] **Scientific notation** - 1e10 in numeric fields

### 12.3 String Edge Cases
- [ ] **Empty strings** - ""
- [ ] **Whitespace only** - "   "
- [ ] **Newlines** - \n, \r\n in text fields
- [ ] **Tabs** - \t in text fields
- [ ] **Null bytes** - \0 in strings
- [ ] **Unicode** - Emojis, non-ASCII characters
- [ ] **SQL keywords** - SELECT, INSERT, DELETE in text

### 12.4 Database Edge Cases
- [ ] **Missing columns** - Graceful handling if columns don't exist
- [ ] **Concurrent updates** - Two users approve same application
- [ ] **Transaction rollback** - Error handling
- [ ] **Foreign key constraints** - Deleting referenced records
- [ ] **Unique constraints** - Duplicate prevention

### 12.5 User Interface Edge Cases
- [ ] **Very long names** - 200+ character names
- [ ] **Special characters in names** - O'Brien, José, etc.
- [ ] **Browser back button** - After form submission
- [ ] **Multiple tabs** - Same user in multiple tabs
- [ ] **Session timeout** - Expired session handling
- [ ] **Slow network** - Timeout handling

### 12.6 Workflow Edge Cases
- [ ] **Approver unavailable** - What if HR is on leave?
- [ ] **Multiple applications** - Same employee, multiple pending
- [ ] **Application during approval** - Employee edits while pending
- [ ] **Deleted employee** - Applications for deleted employees
- [ ] **Role change during approval** - Employee role changes
- [ ] **Department change** - Employee moves departments

---

## 13. Performance & Scalability

### 13.1 Load Testing
- [ ] **100+ employees** - System handles large user base
- [ ] **Many pending applications** - 50+ pending approvals
- [ ] **Large activity log** - 10,000+ activity records
- [ ] **Concurrent users** - 20+ simultaneous users
- [ ] **Database queries** - No N+1 query problems

### 13.2 Response Times
- [ ] **Page load** - < 2 seconds
- [ ] **Form submission** - < 1 second
- [ ] **Dashboard load** - < 3 seconds
- [ ] **Search/filter** - < 1 second

---

## 14. Browser Compatibility

### 14.1 Desktop Browsers
- [ ] **Chrome** - Latest version
- [ ] **Firefox** - Latest version
- [ ] **Edge** - Latest version
- [ ] **Safari** - Latest version (if applicable)

### 14.2 Mobile Browsers
- [ ] **Mobile Chrome** - Responsive design
- [ ] **Mobile Safari** - Responsive design
- [ ] **Touch interactions** - Buttons, forms work on touch

---

## 15. UI/UX Testing

### 15.1 Visual Design
- [ ] **Shelter color palette** - Colors match brand
- [ ] **Logo visibility** - Logo visible on login/signup
- [ ] **Consistent styling** - Same styles across pages
- [ ] **Responsive layout** - Works on different screen sizes
- [ ] **Scrollbars** - Scrollbars work and are styled

### 15.2 User Experience
- [ ] **Navigation** - Easy to navigate
- [ ] **Form validation** - Clear error messages
- [ ] **Success messages** - Confirmation of actions
- [ ] **Loading states** - Loading indicators
- [ ] **Error handling** - User-friendly error messages

---

## 16. Data Migration & Backup

### 16.1 Database Migration
- [ ] **Column additions** - md_approval_status, md_comment
- [ ] **Backward compatibility** - Works if columns missing
- [ ] **Data integrity** - No data loss during migration

### 16.2 Backup & Recovery
- [ ] **Database backup** - Backup procedure works
- [ ] **Data recovery** - Can restore from backup
- [ ] **File backups** - Document/logo backups

---

## 17. Integration Testing

### 17.1 End-to-End Workflows
- [ ] **Complete leave workflow** - Employee applies → Manager approves → HR approves → Leave deducted
- [ ] **Complete loan workflow** - Employee applies → HR approves → Finance Manager approves → Loan disbursed
- [ ] **Manager leave workflow** - Manager applies → HR approves → MD approves → Leave deducted
- [ ] **HR leave workflow** - HR applies → MD approves → Leave deducted
- [ ] **Finance Manager loan** - FM applies → HR approves → MD approves → Loan disbursed

### 17.2 Multi-User Scenarios
- [ ] **Multiple employees apply** - Concurrent applications
- [ ] **Multiple approvers** - Different approvers working simultaneously
- [ ] **Notification delivery** - All parties receive notifications

---

## 18. Regression Testing

### 18.1 Previously Fixed Issues
- [ ] **Manager leave workflow** - HR → MD approval works
- [ ] **Scrollbar functionality** - All scrollbars work
- [ ] **Role detection** - hr_manager, sales_manager detected correctly
- [ ] **Column existence checks** - Graceful handling of missing columns
- [ ] **Activity log pagination** - LIMIT/OFFSET work correctly

---

## 19. Documentation & Help

### 19.1 User Documentation
- [ ] **Help text** - Tooltips, help icons
- [ ] **Form instructions** - Clear field descriptions
- [ ] **Error messages** - Helpful error descriptions

---

## 20. Final Checklist

### 20.1 Pre-Launch
- [ ] **All critical bugs fixed** - No blocking issues
- [ ] **Security audit** - SQL injection, XSS, CSRF protected
- [ ] **Performance acceptable** - Response times meet requirements
- [ ] **Browser compatibility** - Works on target browsers
- [ ] **Mobile responsive** - Works on mobile devices
- [ ] **Data backup** - Backup procedures in place
- [ ] **User training** - Users trained on system
- [ ] **Support plan** - Support procedures defined

---

## Testing Notes

### Test Data Requirements
- Create test users for each role:
  - Regular employee
  - Sales Manager
  - Operations Manager
  - Finance Manager
  - HR Manager
  - Managing Director

### Test Scenarios
- Use realistic data (names, dates, amounts)
- Test with edge case data (boundary values, special characters)
- Test with invalid data (SQL injection, XSS attempts)
- Test concurrent operations (multiple users)

### Bug Reporting
- Document all bugs with:
  - Steps to reproduce
  - Expected behavior
  - Actual behavior
  - Screenshots (if applicable)
  - Browser/OS information

---

## Priority Levels

- **P0 (Critical)**: Blocks core functionality, security issues
- **P1 (High)**: Major functionality broken, data loss risk
- **P2 (Medium)**: Minor functionality issues, UI problems
- **P3 (Low)**: Cosmetic issues, nice-to-have features

---

**Last Updated**: [Current Date]
**Version**: 1.0
**Prepared for**: Shelter HRMS Production Release
