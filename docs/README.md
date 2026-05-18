# UAPS: A WEB-BASED UNIVERSITY ACCOMPLISHMENT PLANNING SYSTEM
## FOR PANGASINAN STATE UNIVERSITY

### Project Overview
UAPS is a comprehensive web-based system designed to automate and streamline the accomplishment planning process at Pangasinan State University. This system replaces the traditional Excel-based manual processes with a modern, efficient digital solution.

### Key Features
- Digital accomplishment tracking and planning
- Automated report generation
- User role management (Faculty, Staff, Administrators)
- Goal setting and progress monitoring
- Performance analytics and insights
- Document management and file uploads

### Technology Stack
- **Backend**: Laravel 11
- **Frontend**: Blade Templates with Vite
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum/Breeze

### Current Status
🚧 **In Development** - Capstone Project

### Documentation Structure
- `/docs/requirements/` - System requirements and specifications
- `/docs/features/` - Detailed feature documentation
- `/docs/design/` - UI/UX design files and prototypes
- `/docs/api/` - API documentation
- `/docs/deployment/` - Deployment and setup guides

### Getting Started
1. Clone the repository
2. Install dependencies: `composer install && npm install`
3. Set up environment: `cp .env.example .env`
4. Generate key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Start development server: `php artisan serve`
