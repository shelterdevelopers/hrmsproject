# Application Flow – Managers and HR (Leave & Loan)

This document describes how **leave** and **loan** applications are approved in the system, and who sees what in their Applications / Pending Approvals.

---

## 1. Leave applications

Who approves depends on **who the applicant is** (role and department).

### 1.1 Regular employee (role = employee)

| Step | Who approves | Where they see it | Result |
|------|----------------|-------------------|--------|
| **First approval** | **Department Manager** (same department as applicant) | Applications → Pending Application Approvals (leave from their department) | Approve → goes to HR. Deny → process ends, applicant notified. |
| **Second approval** | **HR** (hr / hr_manager) | Applications → Pending Application Approvals (employee leave where manager already approved) | Approve → final approved. Deny → final denied. Applicant notified. |

**Flow:** Employee applies → Department Manager (first) → HR (second).  
Department managers only approve **leave** (no loans). They see leave applications from **their department** that are still pending first approval.

---

### 1.2 Manager (e.g. Sales Manager, Operations Manager, Finance Manager – but not HR)

| Step | Who approves | Where they see it | Result |
|------|----------------|-------------------|--------|
| **First approval** | **HR** (hr / hr_manager) | Applications → Pending Application Approvals (manager leave, first approval) | Approve → goes to MD. Deny → process ends, applicant notified. |
| **Second approval** | **Managing Director (MD)** | MD Approvals page / Applications (MD’s pending list) | Approve → final approved. Deny → final denied. Applicant notified. |

**Flow:** Manager applies → HR (first) → MD (second).  
HR sees all manager leave applications that need first approval. MD sees manager leave applications that HR has already approved and need second approval.

---

### 1.3 HR (hr / hr_manager)

| Step | Who approves | Where they see it | Result |
|------|----------------|-------------------|--------|
| **Only approval** | **Managing Director (MD)** | MD Approvals page / Applications (MD’s pending list) | Approve → final approved. Deny → final denied. Applicant (HR) notified. |

**Flow:** HR applies → MD only (one step).  
No HR “first approval” for HR’s own leave; it goes straight to MD.

---

## 2. Loan applications

Who approves depends on **who the applicant is** (department and role).

### 2.1 Regular employee or non‑Finance Manager (e.g. Sales, Operations)

| Step | Who approves | Where they see it | Result |
|------|----------------|-------------------|--------|
| **First approval** | **HR** (hr / hr_manager) | Applications → Pending Application Approvals (all loans with no HR approval yet) | Approve → goes to Finance Manager. Deny → process ends, applicant notified; application does **not** go to Finance. |
| **Second approval** | **Finance Manager** (manager in Finance department) | Applications → Pending Application Approvals (loans where HR has already approved) | Approve → final approved; applicant and HR notified (different messages). Deny → final denied; applicant and HR notified. |

**Flow:** Applicant applies → HR (first) → Finance Manager (second).  
Only HR and Finance Manager see loans in this flow. Other department managers do **not** see or approve loans.

---

### 2.2 Finance Manager (applicant is in Finance department and is manager / finance_manager)

| Step | Who approves | Where they see it | Result |
|------|----------------|-------------------|--------|
| **First approval** | **HR** (hr / hr_manager) | Applications → Pending Application Approvals (same loan queue as above) | Approve → goes to MD (not to Finance Manager, to avoid self-approval). Deny → process ends, applicant notified. |
| **Second approval** | **Managing Director (MD)** | MD Approvals page / Applications (MD’s pending list – Finance Manager loans after HR approval) | Approve → final approved; applicant and HR notified. Deny → final denied; applicant and HR notified. |

**Flow:** Finance Manager applies → HR (first) → MD (second).  
Finance Manager does **not** approve their own loan; MD does second approval.

---

## 3. Who sees what – summary

| Role | Leave approvals | Loan approvals |
|------|-----------------|----------------|
| **Department Manager** (e.g. Sales, Operations; not Finance) | First approval only: leave from **their department** (employee leave). | None. |
| **Finance Manager** | First approval only: leave from **Finance department** (employee leave). | Second approval only: **all** loans already approved by HR (except Finance Manager’s own loan). |
| **HR** (hr / hr_manager) | • First approval: **manager** leave (any department).<br>• Second approval: **employee** leave (after department manager approved). | First approval only: **all** loan applications. |
| **Managing Director (MD)** | • Only approval: **HR** leave.<br>• Second approval: **manager** leave (after HR approved). | Second approval only: **Finance Manager’s** loan (after HR approved). |

---

## 4. Notifications (brief)

- **On apply:** HR and the applicant’s manager are notified (leave and loan).
- **First approval:** Applicant is always notified (approved or denied). If denied, process stops; for loans, the application does not go to Finance Manager.
- **Second approval:** Applicant is notified; for loans, HR is also notified (different message for applicant vs HR). If second approver denies, both applicant and (for loans) HR are notified.

---

## 5. “First approval” vs “Second approval” in the UI

- Pending Application Approvals show whether each item is **First approval** or **Second approval**.
- If it’s second approval, the screen shows **who approved first** (e.g. “Approved by [Manager name] first” or “Approved by HR first”).

This matches the flows above: department managers do first approval for leave; HR does first for manager leave and all loans, and second for employee leave; Finance Manager does second for loans (after HR); MD does second for manager leave and Finance Manager’s loan.
