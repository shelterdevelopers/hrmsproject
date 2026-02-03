# Performance Appraisal Flow – Who Appraises Who

## The Three Appraisal Forms

| Form | Who it’s for | Who uses it (who fills it) |
|------|----------------|----------------------------|
| **Self Assessment** | The individual being appraised | **Everyone except the MD** – employees and managers fill their own self-assessment; the MD does not view or print the Self Assessment form. |
| **Non-Managerial Performance Evaluation** | Subordinates (regular employees) | **Managers** (and HR) – used when a manager or HR appraises a **subordinate** (role = employee). |
| **Management Performance Evaluation** | Managers (including HR) | **MD only** – used when the MD appraises **managers** (including HR). |

So in the system: **Self** = available to everyone except the MD; **Non-Managerial** = available to managers (and HR) to appraise subordinates; **Managerial** = available to the MD to appraise managers.

---

## Summary

- **Admin** does **not** appraise anyone (Admin is a regular employee with admin rights only).
- **Managing Director (MD)** appraises **all managers, including HR** (anyone with role = manager, hr, hr_manager, or executive member). The MD is never in the appraisal dropdown (no one appraises the MD).
- **Employees** (role = employee) can be appraised by **both** their **departmental manager** (direct report: `manager_id` = that manager) **and** by **HR**. So HR can create and conduct appraisals for any regular employee; departmental managers can appraise their direct reports only.

---

## Who Can Create a New Appraisal

| Role | Can create appraisal? | Who they can appraise |
|------|------------------------|------------------------|
| **Admin** | No | — (Admin does not appraise anyone) |
| **Managing Director** | Yes | **All managers including HR** (role = manager, hr, hr_manager, or executive member; never the MD) |
| **Department Manager** | Yes | Only their **direct reports** (`manager_id` = this manager) |
| **Finance Manager** | Yes | Only their **direct reports** in Finance (same as department manager) |
| **HR** | Yes | **All (regular) employees** (role = employee); HR conducts the appraisal |
| **Employee** (no direct reports) | No | — |

---

## Flow in Practice

1. **MD** – Creates appraisals for managers (including HR). Dropdown lists all managers and HR; MD is excluded.
2. **HR** – Creates appraisals for any regular employee (role = employee). HR is the appraiser (manager_id = HR).
3. **Department managers** (including Finance Manager) – Create appraisals only for their **direct reports** (employees whose `manager_id` = this manager).

So: **employees** can be appraised by **both** their departmental manager **and** HR. **Managers** (including HR) are appraised by the **MD** only.

---

## What Happens When You Click “Create Appraisal”

1. You choose an **employee** (from the dropdown), **period start**, and **period end**, then click **Create Appraisal**.
2. The page **redirects** to the **Active Appraisals** tab.
3. A **green success message** appears at the top: *“Appraisal created successfully! You can now fill it in under Active Appraisals and share it with the employee.”*
4. The **new appraisal** appears under **"Appraisals you're conducting"** with status **Draft**.
5. Click **Edit** on that row to fill in scores and comments, then **Share** it with the employee when ready.

If something goes wrong (e.g. invalid dates or no employee selected), you stay on the **Create New Appraisal** tab and see a **red error message** at the top.

---

## Active & Completed: Two Sides for Managers

Managers (and HR) both **conduct** appraisals and **receive** appraisals (e.g. from MD). The **Active Appraisals** and **Completed Appraisals** tabs are split into:

- **Appraisals you're conducting** – You are the appraiser (Employee column = person you're appraising). Drafts appear here; use **Edit** to fill and **Share**.
- **Appraisals for you** / **Your completed appraisals** – You are the employee being appraised (Appraiser column = who appraised you). Use **View** to see and acknowledge.

---

## End-to-End Flow (Who Does What)

### Average employees (regular staff)

1. **Employee** fills in the **Self Assessment** first (ratings and the five sections: goals, strengths, weaknesses, achievements, training).
2. When the employee has completed and acknowledged, the appraisal goes to their **manager’s** account.
3. The **manager** appraises them using the **Non-Managerial Performance Evaluation** form (scores, HOD comments, etc.).
4. When the manager is done and final-submits, **both forms** (Self Assessment + Non-Managerial Evaluation) appear in **HR’s account** under **Completed appraisals (for filing)**.
5. **HR** uses the **Print for filing** button to print both forms, then signs and files them.

### Managers (including HR)

1. **Manager** (including HR) fills in the **Self Assessment** first.
2. That form goes to the **MD** (Managing Director).
3. The **MD** appraises them using the **Management Performance Evaluation** form.
4. When the MD is done and final-submits, **both forms** (Self Assessment + Management Evaluation) appear in **HR’s account** under **Completed appraisals (for filing)**.
5. **HR** uses the **Print for filing** button to print both forms, then signs and files them.

### Technical flow (status)

1. **Creation** – MD / HR / department manager creates an appraisal (selects employee, period start/end). Creates `appraisal_form` and `appraisal` (status `draft`).
2. **Appraiser fills and shares** – The creator (MD, HR, or manager) fills scores/comments and **shares** the appraisal with the employee (status `shared`).
3. **Employee review** – The employee sees the appraisal, fills/edits Self Assessment, and **acknowledges** it.
4. **Completion** – The appraiser **final-submits** the appraisal (status `completed`). Completed appraisals then appear in HR’s Completed appraisals (for filing) list for printing and filing.

---

## Data / Logic Reference

- **Create New Appraisal** is shown only for: MD, HR, or users who have direct reports (`Appraisal::is_manager()`). **Not** shown for Admin.
- **MD dropdown:** `WHERE status = 'active' AND employee_id != ? AND (LOWER(role) IN ('manager','hr','hr_manager') OR executive_member = 1)`.
- **HR dropdown:** All active employees with `LOWER(role) = 'employee'` (MD excluded).
- **Department managers (including Finance Manager):** `WHERE manager_id = ? AND status = 'active'`.

---

## HR File-Keeping: Print & File

When an appraisal is **completed**, both forms (Self Assessment + the relevant evaluation form) appear in HR’s **Completed appraisals (for filing)** list. HR can:

1. **Completed appraisals (for filing)** – From **Appraisals → Completed Appraisals**, HR sees a single list with all completed appraisals (Employee, Department, Period, Completed date).
2. **Print for filing** – For each row, **Print for filing** opens both forms in new tabs: **Self Assessment** and either **Non-Managerial Performance Evaluation** (for regular employees) or **Management Performance Evaluation** (for managers/HR). HR prints both, signs, and files them.
3. **View** – Opens the appraisal detail page, where HR can also use **Print both forms for filing** or print each form individually (Self Assessment, Non-Managerial form, or Management form), or **Print this page** for the combined form view.
4. **Sign and file** – After printing (or saving as PDF), HR signs and files the forms.

Form files: `appraisal_print_self.php` (Self Assessment – access denied for MD), `appraisal_print_evaluation.php` (Non-Managerial), `appraisal_print_management.php` (Management).
