# AlgoTask - Analytics Dashboard for Delayed Cases

A comprehensive Laravel 11 analytics dashboard for tracking and monitoring delayed housing cases with Elasticsearch-powered analytics and real-time insights.

## 📋 Overview

The AlgoTask application stores housing cases in PostgreSQL, determines which cases are delayed, calculates severity based on waiting days, and indexes active delayed cases into Elasticsearch for real-time analytics and reporting.

**Tech Stack:**
- **Backend:** Laravel 11, PHP 8.x
- **Database:** PostgreSQL
- **Search & Analytics:** Elasticsearch 8.x (Docker)
- **Frontend:** Blade Templates, Tailwind CSS
- **Package Manager:** Composer, NPM

## 🚀 Quick Start

### Prerequisites

- **Laragon** (development environment)
- **Docker Desktop** (for Elasticsearch)
- **PHP 8.x**
- **PostgreSQL**
- **Node.js & NPM**

### Installation Steps

1. **Clone the repository and navigate to the project:**
   ```bash
   cd d:\laragon\www\AlgoTask
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies:**
   ```bash
   npm install
   ```

4. **Start Elasticsearch container:**
   ```bash
   docker compose up -d
   ```
   Verify Elasticsearch is running at: `http://localhost:9200`

5. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Update `.env` with your database credentials:
   ```
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=algotask
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   
   ELASTICSEARCH_HOST=http://localhost:9200
   ELASTICSEARCH_INDEX=delay_cases
   ```

6. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

7. **Seed sample data:**
   ```bash
   php artisan db:seed
   ```
   This generates 10,000+ sample cases with financial releases and inspections.

8. **Build frontend assets:**
   ```bash
   npm run build
   ```

9. **Index delayed cases to Elasticsearch:**
   ```bash
   php artisan analytics:reindex-delays
   ```
   This command:
   - Deletes existing index
   - Creates index with proper mapping
   - Reads all cases from PostgreSQL
   - Chunks records (100 at a time)
   - Builds Elasticsearch documents
   - Bulk indexes all delayed cases
   - Shows progress bar and summary

10. **Start the development server:**
    ```bash
    php artisan serve
    ```
    Access the dashboard at: `http://localhost:8000`

## 📊 Dashboard Features

### ✨ Latest Features (v2.0)

**Queue-Based Indexing** - Background processing for large datasets
- Dispatch reindexing jobs to background queue
- No more blocking terminal during reindex
- Automatic retry on failure
- Perfect for production environments

**Automated Tests** - Comprehensive test coverage
- 6 stage detection tests
- 11 severity calculation tests
- Boundary value testing
- Run with: `./vendor/bin/pest`

**CSV Export** - Export filtered dashboard data
- Green "Export CSV" button in dashboard header
- Respects active filters and sorting
- Downloads as `delayed_cases_YYYY-MM-DD_HH-MM-SS.csv`
- Includes all visible columns

### KPI Cards
- **Total Delayed Cases** - Overall count of all delayed cases
- **Green Cases** - On-track cases (low risk)
- **Yellow Cases** - At-risk cases (medium risk)
- **Amber Cases** - Critical cases (high risk)
- **Red Cases** - Urgent cases (very high risk)

### Filters
- **District** - Filter by geographic district
- **Tehsil** - Filter by sub-district
- **Partner** - Filter by partner organization
- **Bank** - Filter by financial institution
- **Severity** - Filter by risk level (Green, Yellow, Amber, Red)
- **Stage** - Filter by current delay stage

### Search
- Search by **Applicant Name** (full-text search)
- Search by **CNIC** (national ID)
- Search by **Case UUID** (unique identifier)
- Real-time search with dropdown suggestions

### Charts & Analytics
- **Severity Distribution** - Doughnut chart showing case breakdown by severity
- **Stage Distribution** - Horizontal bar chart showing cases by delay stage
- **District Distribution** - Bar chart showing top 10 districts by case count

### Cases Table
- **Paginated** - 25 cases per page with navigation
- **Sortable** - Click column headers to sort
- **Searchable** - Combined search across all cases
- **Columns:**
  - Case UUID
  - Applicant Name
  - CNIC
  - District
  - Partner
  - Current Stage
  - Days Waiting
  - Severity Level

### AJAX Features
- **Real-time filtering** - No page reloads, smooth updates
- **Dynamic search** - Results update as you type
- **Live table updates** - Sort, paginate, and filter without refreshing
- **Responsive charts** - Charts update based on filters

## 🏗️ Architecture

### Database Schema

**cases (PostgreSQL)**
- `id` - Primary key
- `case_uuid` - Unique case identifier
- `applicant_name` - Applicant full name
- `applicant_cnic` - National ID (unique)
- `district` - Geographic district
- `tehsil` - Sub-district
- `partner_name` - Partner organization
- `bank_name` - Financial institution
- `branch_name` - Bank branch
- `timestamps` - Created/Updated at

**financial_releases**
- Tracks three release phases: first, second, final
- Stores release dates and amounts
- Links to cases via foreign key

**inspections**
- Tracks foundation and structure inspections
- Stores inspection dates and status
- Links to cases via foreign key

### Business Logic

**DelayStageService**
Determines current delay stage for a case:
1. Waiting for Foundation Inspection (after first release)
2. Waiting for 2nd Release (after foundation inspection)
3. Waiting for Structure Inspection (after second release)
4. Waiting for Final Release (after structure inspection)
5. Completed (when final release is done)

**SeverityService**
Calculates severity based on stage type and waiting days:
- **Release-to-Inspection stages:** 15/30/45 days thresholds
  - 0-14 days: Green
  - 15-29 days: Yellow
  - 30-44 days: Amber
  - 45+ days: Red
- **Inspection-to-Release stages:** 7/15/30 days thresholds
  - 0-6 days: Green
  - 7-14 days: Yellow
  - 15-29 days: Amber
  - 30+ days: Red

## 🔄 Data Flow

1. **Data Entry** → PostgreSQL stores case, financial, inspection data
2. **Delay Detection** → DelayStageService determines if case is delayed
3. **Severity Calculation** → SeverityService calculates risk level
4. **Document Building** → DelayDocumentBuilderService formats for Elasticsearch
5. **Indexing** → ElasticsearchService bulk indexes documents
6. **Query & Analytics** → DashboardService reads Elasticsearch for dashboard
7. **Display** → Laravel views render dashboard with real-time data

## 🛠️ Commands

### Reindex Delayed Cases

The `analytics:reindex-delays` command supports both synchronous and queue-based execution:

**Queue-Based (Background Processing - Recommended):**
```bash
# Dispatch job to queue
php artisan analytics:reindex-delays

# Start queue worker in separate terminal
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=default

# Monitor queue jobs
php artisan queue:failed  # View failed jobs
php artisan queue:retry all  # Retry failed jobs
```

The queue-based approach:
- Returns immediately, doesn't block terminal
- Processes large datasets in the background
- Supports automatic retries on failure
- Logs job status and errors
- Better for production environments

**Synchronous (Blocking - for Testing):**
```bash
# Run immediately with progress bar
php artisan analytics:reindex-delays --sync
```

### Run Tests

Comprehensive test suite for stage detection and severity calculation:

**Run All Tests:**
```bash
./vendor/bin/pest
```

**Run Specific Test Suite:**
```bash
# Stage Detection Tests (6 tests)
./vendor/bin/pest tests/Feature/StageDetectionTest.php

# Severity Calculation Tests (11 tests)
./vendor/bin/pest tests/Feature/SeverityCalculationTest.php
```

**Run Tests with Coverage:**
```bash
./vendor/bin/pest --coverage
./vendor/bin/pest --coverage tests/Feature/StageDetectionTest.php
```

**Test Descriptions:**

**StageDetectionTest.php**
- `can_detect_waiting_for_second_release_stage` - Identifies stage after first release
- `can_detect_waiting_for_structure_inspection_stage` - Identifies stage after second release
- `can_detect_waiting_for_final_release_stage` - Identifies stage after structure inspection
- `returns_null_for_completed_case` - Skips cases with final release
- `returns_null_when_no_financial_or_inspection_records` - Handles missing relations
- `stage_start_date_is_set_correctly` - Validates stage date accuracy

**SeverityCalculationTest.php**
- `calculates_green_severity_for_release_to_inspection_new_case` - 0-15 days = green
- `calculates_yellow_severity_for_release_to_inspection_medium_delay` - 16-30 days = yellow
- `calculates_amber_severity_for_release_to_inspection_high_delay` - 31-45 days = amber
- `calculates_red_severity_for_release_to_inspection_critical_delay` - 46+ days = red
- `calculates_green_severity_for_inspection_to_release_new_case` - 0-7 days = green
- `calculates_yellow_severity_for_inspection_to_release_medium_delay` - 8-15 days = yellow
- `calculates_amber_severity_for_inspection_to_release_high_delay` - 16-30 days = amber
- `calculates_red_severity_for_inspection_to_release_critical_delay` - 31+ days = red
- `release_to_inspection_thresholds_are_consistent` - Threshold validation
- `inspection_to_release_thresholds_are_consistent` - Threshold validation
- `handles_boundary_values_for_release_to_inspection` - Boundary testing (15/16, 30/31, 45/46)
- `handles_boundary_values_for_inspection_to_release` - Boundary testing (7/8, 15/16, 30/31)
- `accepts_carbon_date_object` - Flexible date input
- `throws_exception_for_invalid_stage_type` - Error handling

### Clear Cache

**Clear application cache:**
```bash
php artisan cache:clear
php artisan config:clear
```

### Export Dashboard Data

**Export filtered cases to CSV:**
- Click "Export CSV" button in dashboard header
- Downloads `delayed_cases_YYYY-MM-DD_HH-MM-SS.csv`
- Respects active filters and sorting
- Includes: Case UUID, Applicant, CNIC, District, Partner, Stage, Days, Severity

**Programmatic Export:**
```bash
curl "http://localhost:8000/api/dashboard/export-csv?district=Lahore&severity=red" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o delayed_cases.csv
```

## 🔐 Authentication

The dashboard requires user authentication via Laravel Breeze:
- Protected routes: `/dashboard` and all API endpoints

## 📝 Development Notes

### Key Design Decisions

1. **Service Layer Architecture** - Business logic separated into dedicated services
2. **Chunk Processing** - Database queries chunked to prevent memory issues
3. **Bulk Indexing** - Elasticsearch operations batched for performance
4. **Normalized Fields** - Lowercase fields for case-insensitive filtering
5. **Search Blob** - Combined field for comprehensive full-text search

## ⚠️ Assumptions & Limitations

### Assumptions

The project assumes the following about your environment:

- **PHP Version:** PHP 8.1 or higher with `ext-pdo_pgsql` and `ext-curl` extensions
- **PostgreSQL:** Version 12+ running on `localhost:5432` (or configured host)
- **Elasticsearch:** Version 8.x running in Docker on `localhost:9200`
- **Docker:** Docker Desktop installed and running for Elasticsearch container
- **Node.js:** Node.js 18+ with NPM for frontend asset building
- **Development Environment:** Laragon, XAMPP, or similar local development setup
- **Port Availability:** 
  - `8000` for Laravel dev server
  - `9200` for Elasticsearch
  - `5432` for PostgreSQL
- **User Authentication:** Users are authenticated via Laravel Breeze (included)
- **Composer:** Global Composer installation or `php artisan` available
- **npm:** Global NPM available in PATH

### Limitations

Current version has the following limitations:

1. **Queue Processing**
   - Requires manual `php artisan queue:work` in separate terminal
   - No built-in queue manager (UI) - limited to CLI
   - Database driver (no Redis) suitable for small-to-medium workloads only
   - Not recommended for >10,000 queued jobs

2. **Elasticsearch**
   - Requires Docker (no standalone/cloud fallback option)
   - No automatic index migration on schema changes
   - Single-shard index (not clustered) - suitable for <100K documents
   - Index must be manually recreated for schema updates

3. **Data & Filtering**
   - CSV export limited to current page size (max 10,000 records per export)
   - No custom date range filters (only predefined stages)
   - No role-based access control (all authenticated users see all data)
   - No audit trail or case history tracking

4. **Real-time Updates**
   - Dashboard does not auto-refresh (requires manual action)
   - New cases require manual reindex to appear in analytics
   - Filter changes require browser refresh (AJAX only)

5. **Performance**
   - Recommended for <100K delayed cases
   - Bulk indexing processes 100 records at a time (slower for massive datasets)
   - No pagination limit on dashboard table (renders all 25 rows at once)
   - Search spans multiple fields (may be slow with very large datasets)

6. **Deployment**
   - No caching layer beyond Laravel cache (no Varnish/CDN integration)
   - Designed for single-server deployment (no multi-instance support)
   - No load balancing or horizontal scaling built-in
   - Queue processing single-threaded (no parallel job processing)

7. **Features Not Included**
   - User management UI (admin panel)
   - Email notifications for case status changes
   - Scheduled background jobs (relies on manual queue worker)
   - API rate limiting
   - Multi-language support

## 🐛 Troubleshooting

**Elasticsearch Connection Error:**
```bash
# Verify Elasticsearch is running
curl http://localhost:9200

# Check Docker container status
docker ps
```

**Database Connection Error:**
- Verify PostgreSQL is running
- Check `.env` database credentials
- Run migrations: `php artisan migrate`

**Index Missing/Empty:**
```bash
# Reindex everything
php artisan analytics:reindex-delays

# Verify index
curl http://localhost:9200/delay_cases/_count
```

## 📚 Project Structure

```
├── app/
│   ├── Console/Commands/
│   │   └── AnalyticsReindexDelays.php (queue dispatch)
│   ├── Http/Controllers/
│   │   ├── DashboardController.php (dashboard + CSV export)
│   │   └── ProfileController.php
│   ├── Jobs/
│   │   └── IndexDelayedCases.php (async indexing job)
│   ├── Models/
│   │   ├── ApplicantCase.php
│   │   ├── FinancialRelease.php
│   │   ├── Inspection.php
│   │   └── User.php
│   ├── Services/
│   │   ├── DelayStageService.php
│   │   ├── SeverityService.php
│   │   ├── DelayDocumentBuilderService.php
│   │   ├── ElasticsearchService.php
│   │   ├── DashboardService.php
│   │   ├── ElasticsearchMappingService.php
│   │   └── DashboardExportService.php (CSV export)
|---Public/
|   ├── Screenshots/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── dashboard/
│   │   │   ├── index.blade.php
│   │   │   └── components/
│   │   │       ├── kpi-cards.blade.php
│   │   │       ├── filters.blade.php
│   │   │       ├── search.blade.php
│   │   │       ├── charts.blade.php
│   │   │       └── table.blade.php
│   │   └── layouts/
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php (includes export route)
│   ├── auth.php
│   └── console.php
├── tests/
│   ├── Feature/
│   │   ├── StageDetectionTest.php (6 tests)
│   │   └── SeverityCalculationTest.php (11 tests)
│   ├── Unit/
│   ├── Pest.php
│   └── TestCase.php
└── config/
    ├── elasticsearch.php
    ├── queue.php
    └── ...
```

## 📄 Environment Variables

```env
APP_NAME=AlgoTask
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=algotask
DB_USERNAME=postgres
DB_PASSWORD=secret

ELASTICSEARCH_HOST=http://localhost:9200
ELASTICSEARCH_INDEX=delay_cases

MAIL_DRIVER=smtp
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### Queue Configuration

The application uses **database queue driver** (default):
- No external services needed (Redis not required)
- Jobs stored in `jobs` table
- Failed jobs stored in `failed_jobs` table
- Suitable for development and small-to-medium production workloads

**To use queue:**
```bash
# Ensure jobs table exists (created by migration)
php artisan migrate

# Start queue worker
php artisan queue:work

# Process jobs with custom settings
php artisan queue:work --queue=default --tries=3 --timeout=3600
```

**Queue Configuration File:** `config/queue.php`