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

### Run Specific Commands

**Reindex all delayed cases:**
```bash
php artisan analytics:reindex-delays
```

**Run tests:**
```bash
./vendor/bin/pest
```

**Clear application cache:**
```bash
php artisan cache:clear
php artisan config:clear
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
│   │   └── AnalyticsReindexDelays.php
│   ├── Http/Controllers/
│   │   ├── DashboardController.php
│   │   └── ProfileController.php
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
│   │   └── DashboardService.php
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
│   ├── web.php
│   ├── auth.php
│   └── console.php
└── config/
    ├── elasticsearch.php
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
QUEUE_DRIVER=sync
```