# UAPS Excel Migration Guide

## Migrating from Excel to UAPS System

### Current Excel Structure vs UAPS Database

The UAPS system is designed to replace Excel-based accomplishment tracking with a robust database system.

### Migration Process

#### 1. **Excel Data Export**
Export your current Excel accomplishment data with these columns:
- Employee Name/ID
- Department
- Goal/Accomplishment Title
- Description
- Target Date
- Status
- Priority
- Category
- Progress %

#### 2. **Data Import to UAPS**
Use the built-in import feature:
```bash
php artisan uaps:import-excel {file.xlsx}
```

#### 3. **Data Validation**
The system will validate:
- User exists in system
- Department codes match
- Date formats are correct
- Status values are valid
- Progress percentages are 0-100

### Benefits Over Excel

| Excel System | UAPS System |
|--------------|-------------|
| Manual tracking | Automated notifications |
| No user permissions | Role-based access control |
| Version conflicts | Real-time collaboration |
| Limited reporting | Advanced analytics |
| Data duplication | Centralized database |
| Manual calculations | Automated progress tracking |

### Excel Template for Import

Download the Excel template with proper column headers:
- `employee_id` (required)
- `title` (required)
- `description` (optional)
- `target_date` (required - YYYY-MM-DD)
- `status` (pending/in_progress/completed)
- `priority` (low/medium/high/urgent)
- `category` (teaching/research/extension/administrative/professional_development)
- `progress_percentage` (0-100)

### Sample Import Command
```bash
# Import accomplishments from Excel
php artisan uaps:import-accomplishments accomplishments.xlsx

# Validate data before import
php artisan uaps:validate-import accomplishments.xlsx --dry-run

# Import with specific user mapping
php artisan uaps:import-accomplishments accomplishments.xlsx --map-users
```
