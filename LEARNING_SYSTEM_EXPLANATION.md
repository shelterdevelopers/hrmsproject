# Learning & Development System - Complete Explanation

## Overview
The HRMS has two separate learning interfaces:
1. **Learning & Development** (`app/learning.php`) - For employees
2. **Learning Admin** (`app/learning_admin.php`) - For HR Managers and System Admin

---

## 1. Learning & Development (Employee Perspective)

### Access
- **Who can access:** All employees (including Admin, HR, Managers, and regular employees)
- **Navigation:** Available in sidebar for all users
- **Purpose:** Employees can suggest courses, enroll in courses, track their progress, and submit feedback

### Features for Employees:

#### A. Suggest a New Course
- Employees can suggest courses they want to see added to the catalog
- **Form fields:**
  - Course Title (required)
  - Description (required)
  - Duration in hours (required)
  - Category (required)
  - Link (optional - URL to course material)
- **Workflow:**
  1. Employee submits suggestion
  2. Suggestion goes to HR Manager for approval
  3. HR Manager can:
     - Approve directly (adds to catalog)
     - Deny the suggestion
     - Forward to an Executive for final approval
  4. If forwarded to Executive, Executive can:
     - Approve (adds to catalog)
     - Deny
     - Forward to another Executive
- **Status tracking:** Employees can see the status of their suggestions:
  - `pending_manager` - Waiting for HR Manager approval
  - `pending_executive` - Waiting for Executive approval
  - `approved` - Added to course catalog
  - `denied` - Rejected

#### B. My Course Suggestions
- View all courses you've suggested
- See current status and any comments from managers/executives

#### C. Course Catalog
- Browse all available courses in the system
- Search/filter courses
- View course details:
  - Title
  - Description
  - Duration
  - Category
  - Link (if available)

#### D. Enroll in Courses
- Click "Enroll" on any course in the catalog
- Once enrolled, you can:
  - Track your progress (0-100%)
  - Update progress manually
  - Submit feedback and ratings
  - Mark course as complete (100% progress)

#### E. My Enrollments
- View all courses you're enrolled in
- See your progress percentage
- Update progress
- Submit feedback/ratings
- Mark courses as complete

#### F. HR Manager Approval Section (if you're HR Manager)
- If logged in as HR Manager, you'll see:
  - **Course Suggestions Pending Approval** - All employee suggestions waiting for your review
  - You can approve, deny, or forward to executives

---

## 2. Learning Admin (HR & Admin Perspective)

### Access
- **Who can access:** HR Managers and System Admin only
- **Navigation:** Available in sidebar for HR and Admin
- **Purpose:** Manage the course catalog directly, view all enrollments, and verify course completions

### Features for HR/Admin:

#### A. Course Catalog Management
- **Add Course Directly:**
  - HR/Admin can add courses directly to the catalog without going through the suggestion workflow
  - Form fields: Title, Description, Duration, Category, Link (optional)
  - Courses are immediately available in the catalog

- **Manage Existing Courses:**
  - View all courses in a table format
  - See: Title, Category, Duration, Status (Active/Inactive)
  - Actions available:
    - **Edit** - Modify course details
    - **Activate/Deactivate** - Toggle course availability
    - Courses can be hidden from employees without deletion

#### B. Enrollment Management & Verification
- **View All Enrollments:**
  - See all employee enrollments across all courses
  - Information displayed:
    - Employee name
    - Course title
    - Enrollment date
    - Progress percentage
    - Status (In Progress, Completed)
  
- **Verify Completions:**
  - HR/Admin can verify that employees have actually completed courses
  - Can see when employees marked courses as complete
  - Can review employee feedback and ratings

#### C. Note: Course Suggestions Removed
- **Important:** Course suggestions workflow has been removed from Learning Admin
- HR/Admin no longer see:
  - "Suggest a New Course" form
  - "My Course Suggestions" section
  - "Pending Approvals" sections
- HR/Admin now focus solely on direct course management

---

## Key Differences Summary

| Feature | Learning & Development (Employee) | Learning Admin (HR/Admin) |
|---------|-----------------------------------|---------------------------|
| **Add Courses** | Suggest (requires approval) | Add directly (no approval needed) |
| **Course Suggestions** | Yes - can suggest courses | No - removed from this interface |
| **Enroll in Courses** | Yes | No - view enrollments only |
| **Manage Catalog** | No | Yes - add, edit, activate/deactivate |
| **View All Enrollments** | No - only own enrollments | Yes - all employee enrollments |
| **Approve Suggestions** | Only if HR Manager | No - suggestions removed |
| **Track Progress** | Own progress only | All employees' progress |

---

## Document Upload System

### Current Implementation
- **Document upload is NOT a regular feature** in the employee profile
- Documents are only uploaded during:
  - **Employee signup/registration** - New employees can provide a document URL during registration
  - The document URL is stored in the `document_url` field in the employee table
- **In Profile Edit:**
  - There is a "OneDrive Documents" field that displays the document URL
  - This field is currently mapped to `banking_details` in the code (likely a bug/mislabeling)
  - Employees can view/edit the document URL text field
- **No file upload functionality** exists for:
  - Regular document uploads
  - Profile picture uploads (separate feature exists)
  - Document management

### Recommendation
If you want employees to upload documents regularly, you would need to:
1. Create a document upload interface
2. Use the `employee_documents` table (exists in database schema)
3. Implement file upload handling
4. Store files securely on the server or cloud storage

---

## Managing Director Attendance Visibility

### Current Implementation
- **HR can see all employee attendance** including Managing Director
- **Question raised:** Should HR see Managing Director's attendance?

### Recommendation
- **Managing Director attendance has been filtered out** from HR's "All Attendance" view
- This maintains hierarchy and privacy for the MD
- MD can still view their own attendance via "My Attendance" tab

---

## Summary of Changes Made

1. ✅ **Enlarged dropdown boxes** in manager accounts (Department and Employee selectors)
   - Increased from 300px/400px to 500px minimum width
   - Added padding and font-size for better readability
   - Applied to both `department_attendance_view.php` and `all_attendance_view.php`

2. ✅ **Filtered Managing Director** from HR attendance view
   - MD no longer appears in HR's "All Attendance" page
   - Maintains appropriate hierarchy

3. ✅ **Documented document upload system**
   - Currently only available during signup
   - No regular upload feature exists
   - Document URL field exists but may need proper implementation

4. ✅ **Explained Learning & Development system**
   - Employee perspective: suggest courses, enroll, track progress
   - HR/Admin perspective: manage catalog, view all enrollments
   - Course suggestions removed from Learning Admin interface
