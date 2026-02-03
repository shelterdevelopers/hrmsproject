# Document Management System - Current State & Recommendations

## Current Implementation

### What Exists Now:
1. **Document URL Field** (`document_url` in `employee` table)
   - Stores a single URL (typically OneDrive link)
   - Can be set during employee signup/registration
   - Can be viewed/edited by employees in their profile

2. **Employee Documents Table** (`employee_documents` table)
   - Exists in database schema but **NOT actively used**
   - Designed to store multiple documents per employee
   - Fields: `document_id`, `employee_id`, `document_type`, `document_url`, `uploaded_at`

3. **Profile Picture Upload**
   - Working file upload system exists
   - Stores images in `img/` directory
   - Validates file type, size, converts to PNG

### Current Issues (FIXED):
1. ✅ **Bug Fixed:** `edit_profile.php` had wrong form field name (`banking_details` instead of `document_url`)
2. ✅ **Bug Fixed:** `update-employee-profile.php` wasn't saving `document_url` to database
3. ✅ **Bug Fixed:** Syntax error in `learning_admin.php` (trailing comma in function call)

---

## Recommendations for Document System

### Option 1: Keep Simple (Current Approach - RECOMMENDED)
**Best for:** Small to medium organizations, OneDrive/SharePoint users

**Implementation:**
- Employees provide OneDrive/SharePoint folder links
- HR/Admin can view links in employee profiles
- No file storage on server needed
- Simple and secure (files stay in Microsoft cloud)

**Pros:**
- No server storage costs
- No file upload security concerns
- Leverages existing cloud storage
- Easy to implement (already done)

**Cons:**
- Requires employees to have OneDrive/SharePoint
- No direct file upload
- Limited document organization

**Status:** ✅ **Already implemented and fixed**

---

### Option 2: Full Document Upload System
**Best for:** Organizations needing direct file uploads, multiple document types

**What Would Need to Be Built:**
1. **File Upload Interface:**
   - Upload form in employee profile
   - Support multiple file types (PDF, DOCX, images, etc.)
   - File size limits and validation

2. **Server Storage:**
   - Create `uploads/documents/` directory structure
   - Organize by employee ID: `uploads/documents/{employee_id}/`
   - Secure file naming (prevent directory traversal)

3. **Database Integration:**
   - Use existing `employee_documents` table
   - Store: document type, file path, upload date
   - Link documents to employees

4. **Access Control:**
   - Employees: Upload and view own documents
   - HR/Admin: View all employee documents
   - Managers: View department employee documents

5. **Features:**
   - Document categories (ID, Certificates, Contracts, etc.)
   - Document versioning
   - Download/view documents
   - Delete documents (with permissions)

**Estimated Development Time:** 4-6 hours

**Pros:**
- Complete control over documents
- No external dependencies
- Better organization
- Can track document types

**Cons:**
- Server storage costs
- Security concerns (file uploads)
- Maintenance overhead
- More complex implementation

---

### Option 3: Hybrid Approach
**Best for:** Organizations wanting flexibility

**Implementation:**
- Keep OneDrive link field (for folder links)
- Add document upload for specific document types
- Use `employee_documents` table for uploaded files
- Keep `document_url` for OneDrive links

**Pros:**
- Flexibility for employees
- Can use both methods
- Gradual migration possible

**Cons:**
- More complex to manage
- Two systems to maintain

---

## Current Fixes Applied

### 1. Fixed Document URL Form Field
**File:** `edit_profile.php`
- Changed from `<textarea name="banking_details">` to `<input type="url" name="document_url">`
- Now properly displays and allows editing of document URL
- Added placeholder and help text

### 2. Fixed Document URL Database Update
**File:** `app/update-employee-profile.php`
- Added `document_url` to SQL UPDATE query
- Added `document_url` to data array
- Now properly saves document URL when employees update profile

### 3. Fixed Syntax Error
**File:** `app/learning_admin.php`
- Removed trailing comma in function call: `Learning::get_all_courses(conn: $conn, )`
- Changed to: `Learning::get_all_courses($conn)`

---

## Recommendation

**For now, keep the simple OneDrive link approach** because:
1. ✅ It's already working (after fixes)
2. ✅ No additional development needed
3. ✅ Secure (no file upload vulnerabilities)
4. ✅ No server storage costs
5. ✅ Leverages existing Microsoft infrastructure

**If you need file uploads later**, we can implement Option 2, but it requires:
- Server configuration for file uploads
- Security hardening
- Storage management
- Additional development time

---

## Next Steps (If You Want File Uploads)

If you decide to implement full document uploads, I can:
1. Create upload interface in employee profile
2. Implement secure file handling
3. Use `employee_documents` table
4. Add document management for HR/Admin
5. Add document categories and organization

Let me know if you'd like to proceed with full document uploads or keep the current simple approach!
