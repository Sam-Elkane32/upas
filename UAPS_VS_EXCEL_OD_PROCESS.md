# UAPS vs Excel/CSV OD Process (VPASS Templates)

This document compares the **current UAPS application** template and submission flow with the **Excel/CSV-based OD process** (2025 VPASS SGs1@4 KRAs-KPIs-AccompTemplates: vpass.csv, T1–T10).

---

## 1. Overall structure

| Aspect | Excel/CSV OD process | UAPS (current) |
|--------|----------------------|-----------------|
| **Master consolidation** | vpass.csv: SG → KRA → KPI rows with TARGET, ACCOMPLISHMENT (by quarter), VARIANCE, RATE OF ACCOMP, DESCRIPTIVE RATING, quarterly columns by campus (MAIN, AL, AS, BA, BI, IN, LI, SC, SM, UR, SAS, OUS), ROOT CAUSE, CORRECTIVE ACTION | VPASS-style export from **approved** submissions (SG → KRA → KPI); data comes from template submissions, not a single master sheet |
| **Per-KPI tables** | One CSV per table (T1, T2, … T10): each has a specific KPI title, Responsible Work Units, and columns for that KPI | One **Template** per KPI (or table): `template_code` (e.g. T1), `kra_title`, `kpi_title`, `fields_json` defines columns |
| **Data entry** | By campus; rows per program/quarter; summary/total row per campus (e.g. count or percentage) | By **campus** (Planning Coordinator); **table_data** rows per program/quarter; **summary (blue) row** per coordinator block with formulas (Count Unique, Sum, Average, Formula A+B, Remove formula) |
| **Evidence** | “Google drive link for the supporting documents”, “Evidence Verified by the QA through M&E (YES/NO)”, “CI OFFICE COMMENTS” or “REMARKS” | “Supporting Document” / “Google Drive Link”, “Evidence Verified” (YES/NO), “Remarks” / “CI Office Comments” — **same meaning**, different labels |

So at a high level, **the current state is aligned**: templates = tables, submissions = campus/quarter data, summary row = blue row with calculations.

---

## 2. Column alignment: CSV T1/T2 vs UAPS T1 template

### CSV T1 (SG1 Table 1 – Number of reviewed, enhanced, and CHED-approved curriculum)

- Responsible Work Units  
- Quarter  
- No.  
- Program Name  
- Major Name  
- “Programs with Course Syllabi OR Curricula of Programs Reviewed and Evaluated”  
- “Google drive link for the supporting documents”  
- “Evidence Verified by the QA through M&E (YES/NO)”  
- CI OFFICE COMMENTS  

### UAPS T1 (T1TemplateSeeder)

- Responsible Work Units  
- Quarter  
- Program Name  
- Major Name  
- Target Output  
- Actual Output  
- Supporting Document  
- Evidence Verified  
- Remarks  

**Differences:**

- **No.** column: CSV has explicit “No.” (row number); UAPS does not have a dedicated column (row index can serve the same purpose).
- **Accomplishment column**: CSV has the long description column; UAPS has **Target Output** and **Actual Output**, which support the same KPI (target vs actual).
- **Evidence**: “Supporting Document” = “Google drive link for the supporting documents”; “Evidence Verified” = “Evidence Verified by the QA through M&E (YES/NO)”; “Remarks” = “CI OFFICE COMMENTS”.

So the **current state is functionally like the Excel process** for T1; only labels and presence of “No.” differ.

---

## 3. CSV T2, T3, T4

- **T2** (Percentage of programs with enhanced ILOs, POs and Course Objectives): Same structure as T1 (Responsible Work Units, Quarter, No., Program Name, Major Name, description, Google drive link, Evidence Verified, CI OFFICE COMMENTS). UAPS can represent this with the same template shape (e.g. T2 with same fields as T1 or with a “percentage” result column).
- **T3** (STEAM programs, etc.): Responsible Work Units, Quarter, No., STEAM description, Google drive link, Evidence Verified, REMARKS. Same pattern; UAPS supports it via custom `fields_json`.
- **T4** (Percentage of undergraduate population in priority programs): Many columns (Semester/SY, Program Name, Major Name, Is it Priority?, 1st–6th year M/F, Total, Grand Total, Google drive link, Evidence Verified). UAPS can support this by defining a template with those columns in `fields_json` (no structural limitation).

So the **current state can mirror the Excel tables** as long as each template’s `fields_json` matches the desired columns.

---

## 4. Summary (blue) row and formulas

- **Excel**: Per-campus summary row with totals/counts/percentages (e.g. “6” for count of programs, “48%” for percentage).
- **UAPS**: Blue summary row per coordinator block; supports:
  - **Count Unique Values**, **Count All Values**, **Sum Numbers**, **Average** (persisted to template `summary_rules` → reflected for Planning Coordinator and on reload).
  - **Remove formula** (persisted: removes from `summary_rules` and saves table data).
  - **Formula (A+B)** and **Summary Formula (blue row)** with **Sum**, **A÷B (ratio)**, **A÷B×100 (percent of)** persisted to `summary_rules`; other operations (subtract, multiply, etc.) are DOM-only until backend supports them.

So the **current state is like Excel** in having a summary row and formulas, with persistence fixed for remove and for the supported formula types.

---

## 5. Campus grouping and roles

- **Excel**: Data grouped by “Responsible Work Units” (e.g. ALAMINOS CITY, ASINGAN, BAYAMBANG); footer with “Planning Coordinator, [Campus]” and “Campus Executive Director, [Campus]”.
- **UAPS**: Data grouped by **campus** (and submission); each block is one coordinator’s submission; Super Admin can view/edit all in one view; Planning Coordinator sees only their campus. Sign-off/approval flow exists separately.

So the **current state matches** the idea of campus-based grouping and roles (Planning Coordinator vs Super Admin / CED).

---

## 6. Conclusion: Is the current state “just like” the Excel OD process?

- **Conceptually yes**: Templates = tables (T1–T10), submissions = campus/quarter accomplishment data, summary row = blue row with calculations, evidence = supporting document + Evidence Verified + remarks.
- **Structurally yes**: Same kinds of columns (Responsible Work Units, Quarter, Program Name, Major Name, evidence link, Evidence Verified, comments); optional “No.” and exact CSV labels can be added if desired.
- **Functionally yes**: Formulas and “Remove formula” persist; summary row reflects for Planning Coordinator; table data is saved per submission.

To make the app **label-alike** with the Excel sheets you can:

1. Add a “No.” field to T1/T2-style seeders (or any template) if you want explicit row numbers.
2. Rename “Supporting Document” to “Google drive link for the supporting documents” and “Evidence Verified” to “Evidence Verified by the QA through M&E (YES/NO)” in `fields_json` for templates that should mirror the CSV headers exactly.
3. Ensure each CSV table (T3–T10) has a corresponding template with `fields_json` columns matching that table’s structure (as with T4’s many columns).

No change to application logic is required for the flow to match the OD process; only template definitions and optional labels need to align with the Excel/CSV design.
