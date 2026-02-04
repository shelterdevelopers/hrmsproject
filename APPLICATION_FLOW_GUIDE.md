# Application Flow Guide for Shelter HRMS

This document explains the complete application workflow for **Leave** and **Loan** applications for all roles in the system.

---

## 📋 Table of Contents
1. [Leave Application Flows](#leave-application-flows)
2. [Loan Application Flows](#loan-application-flows)
3. [Who Can Apply](#who-can-apply)
4. [Who Can Approve](#who-can-approve)
5. [Notifications](#notifications)

---

## 🏖️ Leave Application Flows

### 1. **Regular Employee Leave**
**Flow:** Employee → Department Manager → HR → ✅ Approved

**Step-by-Step:**
1. **Employee submits leave application**
   - Employee fills out leave form (dates, type, reason)
   - Application status: `pending`
   - Notification sent to: **Department Manager**

2. **Department Manager (First Approval)**
   - Manager sees application in "Pending Approvals"
   - Manager can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `manager_approval_status` = `approved`
     - Notification sent to: **Employee** (informing first approval)
     - Notification sent to: **HR** (requesting second approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Employee** (informing denial)

3. **HR (Second Approval)**
   - HR sees application in "Pending Approvals" (only if manager approved)
   - HR can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `hr_approval_status` = `approved`
     - Application status = `approved` (FINAL)
     - Leave days deducted from employee's balance
     - Notification sent to: **Employee** (final approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Employee** (informing denial)

---

### 2. **Manager Leave** (Sales Manager, Operations Manager, etc.)
**Flow:** Manager → HR → Managing Director → ✅ Approved

**Step-by-Step:**
1. **Manager submits leave application**
   - Manager fills out leave form
   - Application status: `pending`
   - Notification sent to: **HR**

2. **HR (First Approval)**
   - HR sees application in "Pending Approvals"
   - HR can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `hr_approval_status` = `approved`
     - Notification sent to: **Manager** (informing first approval)
     - Notification sent to: **Managing Director** (requesting second approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Manager** (informing denial)

3. **Managing Director (Second Approval)**
   - MD sees application in "Pending Approvals" (only if HR approved)
   - MD can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `md_approval_status` = `approved`
     - Application status = `approved` (FINAL)
     - Leave days deducted from manager's balance
     - Notification sent to: **Manager** (final approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Manager** (informing denial)

**Note:** Finance Manager follows the same flow as other managers for leave applications.

---

### 3. **HR Leave**
**Flow:** HR → Managing Director → ✅ Approved

**Step-by-Step:**
1. **HR submits leave application**
   - HR fills out leave form
   - Application status: `pending`
   - Notification sent to: **Managing Director**

2. **Managing Director (Only Approval)**
   - MD sees application in "Pending Approvals"
   - MD can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `manager_approval_status` = `approved` (MD uses manager_approval_status for HR)
     - Application status = `approved` (FINAL)
     - Leave days deducted from HR's balance
     - Notification sent to: **HR** (final approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **HR** (informing denial)

---

### 4. **Managing Director Leave**
**Managing Director does NOT apply for leave or loans** - they only approve applications from others.

---

## 💰 Loan Application Flows

### 1. **Regular Employee/Manager Loan** (Non-Finance Manager, Non-HR Manager)
**Flow:** Employee/Manager → HR → Finance Manager → ✅ Approved

**Step-by-Step:**
1. **Employee/Manager submits loan application**
   - Employee/Manager fills out loan form (amount, repayment plan, reason)
   - Application status: `pending`
   - Notification sent to: **HR**

2. **HR (First Approval)**
   - HR sees application in "Pending Approvals"
   - HR can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `hr_approval_status` = `approved`
     - Notification sent to: **Applicant** (informing first approval, timestamped)
     - Notification sent to: **Finance Manager** (requesting second approval, timestamped)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Applicant** (informing denial, timestamped)

3. **Finance Manager (Second Approval)**
   - Finance Manager sees application in "Pending Approvals" (only if HR approved)
   - Finance Manager can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `finance_approval_status` = `approved`
     - Application status = `approved` (FINAL)
     - `disbursed_amount` = loan amount
     - `outstanding_balance` = loan amount
     - `next_payment_date` = 1 month from today
     - Notification sent to: **Applicant** (final approval, timestamped - "Go see HR to collect money")
     - Notification sent to: **HR** (informing final approval, timestamped - "Prepare disbursement")
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Applicant** (informing denial, timestamped)
     - Notification sent to: **HR** (informing denial, timestamped)

---

### 2. **Finance Manager Loan**
**Flow:** Finance Manager → HR → Managing Director → ✅ Approved

**Step-by-Step:**
1. **Finance Manager submits loan application**
   - Finance Manager fills out loan form
   - Application status: `pending`
   - Notification sent to: **HR**

2. **HR (First Approval)**
   - HR sees application in "Pending Approvals"
   - HR can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `hr_approval_status` = `approved`
     - Notification sent to: **Finance Manager** (informing first approval)
     - Notification sent to: **Managing Director** (requesting second approval)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Finance Manager** (informing denial)

3. **Managing Director (Second Approval)**
   - MD sees application in "Pending Approvals" (only if HR approved)
   - MD can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `md_approval_status` = `approved`
     - Application status = `approved` (FINAL)
     - `disbursed_amount` = loan amount
     - `outstanding_balance` = loan amount
     - `next_payment_date` = 1 month from today
     - Notification sent to: **Finance Manager** (final approval)
     - Notification sent to: **HR** (informing final approval - "Prepare disbursement")
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **Finance Manager** (informing denial)
     - Notification sent to: **HR** (informing denial)

---

### 3. **HR Manager Loan**
**Flow:** HR Manager → Finance Manager → Managing Director → ✅ Approved

**Step-by-Step:**
1. **HR Manager submits loan application**
   - HR Manager fills out loan form
   - Application status: `pending`
   - **Note:** HR cannot approve their own loan, so it goes directly to Finance Manager
   - Notification sent to: **Finance Manager**

2. **Finance Manager (First Approval)**
   - Finance Manager sees application in "Pending Approvals"
   - Finance Manager can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `finance_approval_status` = `approved`
     - Notification sent to: **HR Manager** (informing first approval, timestamped)
     - Notification sent to: **Managing Director** (requesting second approval, timestamped)
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **HR Manager** (informing denial, timestamped)

3. **Managing Director (Second Approval)**
   - MD sees application in "Pending Approvals" (only if Finance Manager approved)
   - MD can: Approve ✅ or Deny ❌
   - If **Approved**: 
     - `md_approval_status` = `approved`
     - Application status = `approved` (FINAL)
     - `disbursed_amount` = loan amount
     - `outstanding_balance` = loan amount
     - `next_payment_date` = 1 month from today
     - Notification sent to: **HR Manager** (final approval)
     - Notification sent to: **HR** (informing final approval - "Prepare disbursement")
   - If **Denied**: 
     - Application status = `denied` (FINAL)
     - Notification sent to: **HR Manager** (informing denial)
     - Notification sent to: **HR** (informing denial)

---

## ✅ Who Can Apply

### Leave Applications:
- ✅ **Regular Employees** - Can apply for leave
- ✅ **Department Managers** (Sales, Operations, Finance) - Can apply for leave
- ✅ **HR** - Can apply for leave
- ❌ **Managing Director** - Cannot apply for leave

### Loan Applications:
- ✅ **Regular Employees** - Can apply for loans
- ✅ **Department Managers** (Sales, Operations) - Can apply for loans
- ✅ **Finance Manager** - Can apply for loans
- ✅ **HR Manager** - Can apply for loans
- ❌ **Managing Director** - Cannot apply for loans
- ❌ **Other Department Managers** (Sales, Operations) - Cannot apply for loans (they can only view existing loans if they have any)

---

## 🔐 Who Can Approve

### Leave Approvals:

| Approver | Can Approve For | Approval Level |
|----------|----------------|----------------|
| **Department Manager** | Employees in their department | First approval (for employees only) |
| **HR** | Regular employees (after manager approval) | Second approval |
| **HR** | Managers (Sales, Operations, Finance) | First approval |
| **Managing Director** | HR leave applications | Only approval |
| **Managing Director** | Manager leave applications (after HR approval) | Second approval |

### Loan Approvals:

| Approver | Can Approve For | Approval Level |
|----------|----------------|----------------|
| **HR** | Regular employees/managers (non-Finance, non-HR) | First approval |
| **HR** | Finance Manager loans | First approval |
| **Finance Manager** | Regular employees/managers (after HR approval) | Second approval |
| **Finance Manager** | HR Manager loans | First approval |
| **Managing Director** | Finance Manager loans (after HR approval) | Second approval |
| **Managing Director** | HR Manager loans (after Finance Manager approval) | Second approval |

**Important Notes:**
- HR cannot approve their own loans (goes to Finance Manager first)
- Finance Manager cannot approve their own loans (goes to MD after HR approval)
- Each approver can only approve at their designated level

---

## 🔔 Notifications

### Notification Flow:
1. **Application Submitted:**
   - First approver receives notification

2. **First Approval (Approved):**
   - Applicant receives notification (informing first approval)
   - Second approver receives notification (requesting approval)

3. **First Approval (Denied):**
   - Applicant receives notification (informing denial)
   - Application status becomes `denied` (FINAL)

4. **Second Approval (Approved):**
   - Applicant receives notification (final approval)
   - For loans: HR receives notification to prepare disbursement
   - Application status becomes `approved` (FINAL)

5. **Second Approval (Denied):**
   - Applicant receives notification (informing denial)
   - Application status becomes `denied` (FINAL)

### Notification Timestamps:
- All loan-related notifications include timestamps (Harare time)
- Leave notifications do not include timestamps

---

## 📊 Application Status Flow

```
pending → [First Approval] → pending → [Second Approval] → approved/denied
         (if denied here) → denied (FINAL)
```

**Status Values:**
- `pending` - Waiting for approval
- `approved` - Fully approved (FINAL)
- `denied` - Denied at any stage (FINAL)

**Approval Status Columns:**
- `manager_approval_status` - Department manager approval (for employee leave)
- `hr_approval_status` - HR approval (for employee leave second approval, manager leave first approval, loans first approval)
- `finance_approval_status` - Finance Manager approval (for loans second approval, HR Manager loans first approval)
- `md_approval_status` - Managing Director approval (for HR leave, manager leave second approval, Finance Manager/HR Manager loans second approval)

---

## 🎯 Quick Reference

### Leave Applications:
| Applicant | First Approver | Second Approver | Final Status |
|-----------|---------------|-----------------|--------------|
| Employee | Department Manager | HR | Approved/Denied |
| Manager | HR | Managing Director | Approved/Denied |
| HR | Managing Director | - | Approved/Denied |

### Loan Applications:
| Applicant | First Approver | Second Approver | Final Status |
|-----------|---------------|-----------------|--------------|
| Employee/Manager | HR | Finance Manager | Approved/Denied |
| Finance Manager | HR | Managing Director | Approved/Denied |
| HR Manager | Finance Manager | Managing Director | Approved/Denied |

---

## 📝 Important Rules

1. **No Self-Approval:** Approvers cannot approve their own applications
2. **Sequential Approval:** Second approver can only approve after first approver has approved
3. **Denial is Final:** If any approver denies, the application is immediately `denied` (FINAL)
4. **Leave Days Deduction:** Leave days are only deducted when the application is fully approved
5. **Loan Disbursement:** Loans are only disbursed when fully approved (HR prepares disbursement after Finance Manager/MD approval)
6. **Managing Director:** Does not apply for leave or loans, only approves

---

## 📚 Learning & Development – When HR Adds a Course to the Catalogue

**What happens when HR (or Admin) adds a course to the catalogue:**

1. **HR/Admin** goes to **Learning Admin** and uses **"Add Course to Catalog"** (form: title, description, duration, category, link).
2. On submit, the course is **inserted into the `learning_courses` table** (title, description, duration, category, link). It is available immediately in the catalogue.
3. **Employees** see the new course in **Learning & Development** and can **enroll** in it.
4. Once enrolled, the course appears in their learning list; they can complete it and submit feedback. HR/Admin can view enrollments and mark completion from Learning Admin.
5. **Summary:** Adding a course to the catalogue makes it visible to all employees so they can enroll and complete it; no approval step is required for catalogue additions.

---

*Last Updated: Based on current codebase implementation*
