# BMMS — Claude Code Project Memory

**Board Meeting Management System**
Multi-Tenant SaaS Platform | Laravel 12 | PHP 8.4+ | MariaDB 10.11+

---

## Project Overview

BMMS is a secure, multi-tenant SaaS board governance platform that replaces paper-based board packs,
fragmented email, manual minutes, disconnected voting, and spreadsheet action tracking with one digital
platform. It supports Indian governance and compliance workflows including Companies Act 2013,
Secretarial Standards, audit trails, conflict-of-interest declarations, and board-level reporting.

**SRS Version:** 2.1 (04 May 2026)
**Current Phase:** Phase 1 — Foundation (starting fresh)
**Project folder:** `/var/www/html/bmms`
**Database:** `bmms_dev` (MariaDB 10.11+)

---

## Tech Stack

| Layer       | Technology                                      |
|-------------|--------------------------------------------------|
| Framework   | Laravel 12                                       |
| PHP         | 8.4+                                             |
| Database    | MariaDB 10.11+ / MySQL 8.0+                      |
| Server      | Ubuntu 24.04 LTS + Apache                        |
| Admin panel | Filament (for superadmin + tenant admin panels)  |
| Frontend    | Blade + Tailwind CSS + Alpine.js or Livewire     |
| Charts      | Chart.js or Filament charts                      |
| RBAC        | spatie/laravel-permission                        |
| Audit logs  | spatie/laravel-activitylog                       |
| PDF export  | barryvdh/laravel-dompdf                          |
| Excel export| maatwebsite/excel                                |
| Payments    | Razorpay (future phase)                          |
| Queue       | Laravel queues (database driver initially)       |
| Scheduler   | Laravel scheduler                                |

---

## UI Design Reference

A working HTML prototype exists at: `bmms-v21-prototype.html`
**Always refer to this prototype for UI layout, colour scheme, and screen structure.**

### Design tokens from prototype:
```css
--bg: #f4f6f8
--surface: #ffffff
--surface-2: #eef3f7
--ink: #17202a
--muted: #647184
--line: #d8e0e8
--nav: #18212b          /* sidebar background */
--nav-soft: #263241
--blue: #1f6feb
--teal: #087f83
--green: #238636
--amber: #b7791f
--red: #b42318
--violet: #6f42c1
--radius: 8px
--shadow: 0 12px 30px rgba(23,32,42,0.08)
```

### Screens in prototype (use as blueprint):
- `#workbench` — Board member landing page (upcoming meetings, pending tasks, metrics)
- `#meetings` — Meeting list with lifecycle status
- `#pack` — Board pack builder with page numbering + bulk upload
- `#documents` — Document viewer with annotation modes (private/shared)
- `#minutes` — Minutes drafting + approval workflow
- `#voting` — Voting/resolutions with secret ballot toggle
- `#actions` — Action item tracker
- `#atr` — Action Taken Report generation
- `#analytics` — Per-meeting + historical analytics
- `#reports` — Compliance reports + audit bundle
- `#admin` — Tenant admin panel

---

## Architecture — Critical Rules

### Multi-Tenancy
- Every table that holds tenant data **MUST have `tenant_id`** as a foreign key
- Every query MUST be filtered by `tenant_id` — use global scopes or middleware
- Tenant context resolved via middleware on every request
- **Superadmin sees cross-tenant usage summaries ONLY — never sensitive meeting content**
- Use Laravel Policies to enforce tenant ownership on every model action
- Add automated tests for tenant isolation before going live

### Roles (6 total)
```
superadmin        — SaaS platform owner, manages all tenants
tenant_admin      — Company Secretary / org administrator
board_secretary   — Prepares meetings, agenda, board packs, minutes
board_member      — Director/trustee, views packs, votes, annotates
department_head   — Department/committee level access
guest             — External observer, assigned meeting only, no admin access
```

### Meeting Lifecycle (10 stages)
```
1. draft
2. scheduled
3. agenda_prepared
4. board_pack_generated
5. rsvp_active
6. in_progress
7. minutes_drafted
8. minutes_under_approval
9. closed
10. archived
```

### Minutes Approval Workflow
```
draft_prepared → chair_review → director_review → revisions_requested → approved → locked → archived
```

### Action Item Statuses
```
to_do → in_progress → completed
                    → overdue
                    → deferred
```

### RSVP Options
```
yes | no | maybe | excused
```

### Attendance Statuses
```
present | absent | remote | excused | late | left_early
```

### Resolution Types
```
ordinary | special | circular | poll_question
```

### Vote Options
```
for | against | abstain
```

### Conflict Declaration Statuses
```
no_conflict | conflict_declared | recused | pending_declaration
```

---

## Database — All Tables

### Core / Tenant Tables
```sql
tenants                  — tenant root record
tenant_settings          — org profile, branding, preferences
users                    — has tenant_id + role
departments              — has tenant_id + chairperson_id
committees               — has tenant_id + chairperson_id + secretary_id
roles                    — spatie permission roles
permissions              — spatie permissions
```

### Meeting Tables
```sql
meetings                 — has tenant_id, type, lifecycle status, location
meeting_attendees        — user, rsvp_status, attendance_status
meeting_guests           — external guests
meeting_status_logs      — lifecycle stage change history
agenda_items             — ordered, time_allocated, resolution_required
```

### Document Tables
```sql
documents                — has tenant_id, version controlled
document_versions        — version_number, uploaded_by, change_note
meeting_documents        — links documents to meetings/agenda items, staged/published
document_annotations     — highlight, comment, drawing — visibility: private/shared
document_comments        — linked to document or annotation
document_read_receipts   — who opened, when, acknowledgement, time_spent
```

### Board Pack Tables
```sql
board_packs              — version, published_at, generated_by
board_pack_items         — ordered items with auto page_number
```

### Minutes Tables
```sql
minutes                  — linked to meeting, status (draft → locked)
minute_versions          — revision history
minute_approvals         — approval chain (chair → directors)
```

### Voting Tables
```sql
resolutions              — linked to meeting/agenda_item, type, secret_ballot flag
votes                    — user, resolution, response, timestamp, ip_address
polls                    — linked to meeting
poll_responses           — user, poll, response
```

### Governance Tables
```sql
conflict_declarations    — linked to meeting/agenda/resolution/vendor
action_items             — owner, due_date, priority, status, linked meeting
action_taken_reports     — generated ATR records
audit_logs               — all major actions (login, upload, vote, approve, etc.)
notifications            — per user, per tenant
```

### Subscription / Billing Tables
```sql
subscriptions            — tenant subscription to a plan
subscription_plans       — plan name, price, limits (meetings, storage, users)
invoices                 — invoice_number, gst_number, tax_amount, payment_status
```

### Analytics Tables (Phase 4)
```sql
meeting_analytics        — aggregated per-meeting stats
tenant_usage_summaries   — cross-meeting tenant summaries
engagement_scores        — per member per meeting
```

---

## Key Packages to Install

```bash
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require filament/filament
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

---

## Development Phases

### ✅ Phase 1 — Foundation (IN PROGRESS — session 2026-06-18)
- [x] Laravel 13 project setup (spec said 12; 13 is current stable — no functional difference)
- [x] Tenant model + TenantSetting model (soft deletes, getSetting/setSetting helpers)
- [x] tenant_id global scopes via `BelongsToTenant` trait (`app/Models/Traits/BelongsToTenant.php`)
- [x] `SetTenantContext` middleware — registered on web stack, sets `app('current_tenant_id')`
- [x] `EnsureTenantIsActive` middleware — alias `tenant.active`, blocks suspended tenants + inactive users
- [x] Authentication (Breeze Blade stack — login, logout, password reset)
- [x] RBAC with spatie/laravel-permission v8 — 6 roles seeded (see Roles section)
- [x] User model updated — tenant_id, status, invitation_token, invited_by, last_login_at, soft deletes
- [x] User statuses: active, invited, suspended, deactivated (column on users table)
- [x] Superadmin Filament panel at `/admin` — TenantResource + UserResource built
- [x] Tenant Admin Filament panel at `/manage` — panel wired + `UserResource` built (session 2026-06-29)
- [x] Basic audit logs installed (spatie/laravel-activitylog v5, LogsActivity on User model)
- [x] Tenant Admin UserResource — list, edit, invite, suspend/activate, resend-invite actions
- [x] User invitation flow — `InviteUserMail`, `InvitationController` (show/accept), `/invite/{token}` routes, Blade accept-invitation page
- [x] Superadmin dashboard widget — `PlatformStatsOverview` (total/active tenants, users)
- [x] Automated tenant isolation tests — 6 tests all pass (`tests/Feature/TenantIsolationTest.php`)
- [x] **BUGFIX:** User model now uses `BelongsToTenant` trait — queries properly scoped by tenant_id

### ✅ Phase 2 — Core BMMS (Partial)
- [x] Departments and committees CRUD — `DepartmentResource`, `CommitteeResource` (Tenant panel, Organisation group)
- [x] Meeting creation + lifecycle management — `MeetingResource`, `Meeting` model with 10-stage state machine, `transitionTo()`, `TRANSITIONS` constant; EditMeeting/ViewMeeting with dynamic transition header buttons
- [x] Agenda builder with drag-to-reorder — `AgendaRelationManager` (reorderable, `order_column`)
- [x] Attendee management + RSVP — `AttendeesRelationManager`, `GuestsRelationManager`, `MeetingAttendee`, `MeetingGuest` models
- [x] Document upload + versioning — `DocumentsRelationManager`, `Document` + `DocumentVersion` models
- [x] Board member landing page (workbench) — `WorkbenchController`, `resources/views/workbench.blade.php`, routes `workbench` + `rsvp.respond`
- [x] RSVP response handler — `RsvpController@respond`
- [x] BmsNotification model + static `notify()` helper (named `bms_notifications` to avoid Laravel conflict)
- [x] BoardPack + BoardPackItem models and migration
- [x] `navigationGroup` type fixed (`string|\\UnitEnum|null`) in all 3 tenant resources
- [x] Document management resource — `DocumentResource` (Tenant panel, Documents group); create/edit/delete; file upload with type/size; version history via `VersionsRelationManager`; secure download via `DocumentDownloadController`
- [x] Board pack generator — `BoardPackService` (generate cover PDF + TOC via dompdf, ZIP package download); `BoardPacksRelationManager` on MeetingResource (generate, publish, download PDF, download ZIP); auto-advances meeting to `board_pack_generated` on publish; `BoardPackController` for download routes
- [x] Notification dispatch — `MeetingNotificationService::onTransition()` hooked into `Meeting::transitionTo()`; 6 triggers (scheduled, agenda_prepared, board_pack_generated, rsvp_active, in_progress, minutes_under_approval); creates `BmsNotification` records + queues `MeetingNotificationMail` email; recipient strategy per type (attendees / pending-RSVP / chair+secretary)

### ⏳ Phase 2 — Remaining
- [ ] Calendar view for meetings
- [ ] Departmental drop folders + staged submission

### ✅ Phase 3 — Governance
- [x] Resolutions (ordinary/special/circular) + voting — `Resolution`, `ResolutionVote` models; `ResolutionsRelationManager` on MeetingResource (propose → open voting → cast vote → close & decide); secret ballot hides individual votes
- [x] Decision Register — `DecisionRegisterResource` (Governance group); lists all resolutions; create standalone circular resolutions; open/close voting actions
- [x] Action Items — `ActionItem` model (overdue detection via `isOverdue()`/`scopeOverdue()`); `ActionItemsRelationManager` on MeetingResource; `ActionItemResource` standalone (Governance group) with overdue filter and mark-done action
- [x] Minutes drafting + approval + locking — `Minutes` model (JSON content per agenda item); `MinutesRelationManager` on MeetingResource (start draft pre-filled with agenda items → submit for review → approve & lock → download PDF); `MinutesService` generates PDF + advances meeting to closed
- [x] Circular resolutions (Flying Minutes) — board members notified by signed-URL email (`CircularResolutionMail`); vote at `/vote/resolution/{id}` without login; `CircularResolutionController` handles pre-vote from email buttons; `ResolutionService::notifyCircularResolutionVoters()` dispatches to all tenant board members
- [x] Minutes PDF — `resources/views/pdf/minutes.blade.php` (cover + attendance + per-agenda-item content + resolutions + signature block); `MinutesController@download`

### ⏳ Phase 3 — Remaining
- [ ] Action Taken Report (ATR) PDF/Excel export
- [ ] Conflict-of-interest declarations + register
- [ ] Document annotations (private)

### ✅ Phase 4 — Analytics (complete 2026-06-29)
- [x] AnalyticsService — all query methods (meeting, attendance, resolution, action item, document stats)
- [x] TenantMeetingStatsWidget — StatsOverviewWidget (5 KPI cards)
- [x] MeetingsByMonthChart — bar chart with 6/12/24-month filter
- [x] AttendanceRateChart — line chart (last 10 meetings)
- [x] ResolutionOutcomeChart — doughnut (passed/failed/pending/withdrawn)
- [x] ActionItemStatusChart — doughnut (open/done/overdue/cancelled)
- [x] AnalyticsDashboard page — /manage/analytics, Reports nav group, 3 export header actions
- [x] TenantDashboard page — replaces default Dashboard, only AccountWidget (prevents widget bleed)
- [x] PlatformGrowthChart (admin) — new organisations per month bar chart
- [x] PlatformMeetingsChart (admin) — platform meetings per month line chart
- [x] AnalyticsExportController — /analytics/export/meetings.csv, /analytics/export/actions.csv, /analytics/export/governance-report.pdf
- [x] governance-report.blade.php — 4-section PDF (meetings, attendance, resolutions, action items)
- KEY: $heading/$maxHeight are NON-static in ChartWidget/StatsOverviewWidget; $sort is static
- KEY: $navigationIcon type must be string|\BackedEnum|null (not ?string)

### ⏳ Phase 5 — SaaS Commercial
- [ ] Subscription plans management
- [ ] Billing records + GST invoice generation
- [ ] Razorpay payment integration + webhooks
- [ ] Tenant usage metrics + limits enforcement
- [ ] Tenant suspension for non-payment

### ⏳ Phase 6 — Production Hardening
- [ ] Security review + penetration testing
- [ ] Performance optimisation (indexes, caching, queued exports)
- [ ] Backup automation
- [ ] Queue workers + scheduler setup
- [ ] SSL + HTTPS enforcement
- [ ] Monitoring + error reporting
- [ ] Offline sync security hardening (future)
- [ ] Digital signature review (future)

---

## Access Control Matrix

| Module          | Superadmin     | Tenant Admin    | Board Secretary | Board Member       | Dept Head      | Guest         |
|-----------------|----------------|-----------------|-----------------|--------------------|----------------|---------------|
| Tenant Mgmt     | Full           | Own settings    | No              | No                 | No             | No            |
| Users / RBAC    | Full           | Full (tenant)   | Limited         | No                 | No             | No            |
| Meetings        | Usage summary  | Full (tenant)   | Create/manage   | Assigned only      | Dept only      | Assigned only |
| Documents       | Support access | Full (tenant)   | Manage          | Assigned only      | Dept only      | Assigned only |
| Minutes         | No content     | Full (tenant)   | Manage          | Review/approve     | Dept only      | View if assigned |
| Voting          | Summary        | Full (tenant)   | Manage          | Vote               | Vote if eligible | Usually no  |
| Analytics       | Cross-tenant   | Full (tenant)   | Meeting level   | Own/high-level     | Dept view      | No            |
| Billing         | Full           | Own subscription| No              | No                 | No             | No            |

---

## Coding Conventions

- **Always scope queries by `tenant_id`** — use global scopes on models, middleware sets tenant context
- Use `spatie/laravel-permission` for all role/permission checks — never hardcode role strings in logic
- Use Laravel Policies for authorisation — every model needs a Policy
- Soft deletes enabled on all main models
- `$request->validate([...])` on all form inputs
- Queue all heavy operations (PDF generation, bulk email, analytics export)
- Audit log all major actions via `spatie/laravel-activitylog`
- Documents stored in private storage — never in `public/` — serve via signed temporary URLs
- Flash messages: `session()->flash('success', '...')` / `session()->flash('error', '...')`
- Blade layout: `@extends('layouts.app')`
- Tailwind CSS only for styling
- All dates stored as UTC, displayed in user's timezone
- Use Filament for admin panels (superadmin + tenant admin)
- Use Blade + Alpine.js or Livewire for board member-facing UI

---

## Common Artisan Commands

```bash
# Create project (run once)
composer create-project laravel/laravel bmms
cd /var/www/html/bmms

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Seed subscription plans / roles
php artisan db:seed

# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list

# Generate Filament resource
php artisan make:filament-resource Tenant --generate

# Tail logs
tail -f storage/logs/laravel.log

# Fix permissions (run after file changes as root)
chown -R umesh:www-data /var/www/html/bmms
chmod -R 775 /var/www/html/bmms/storage /var/www/html/bmms/bootstrap/cache
```

---

## Environment

```
APP_ENV=local
APP_URL=http://localhost (or server IP)
DB_CONNECTION=mysql
DB_DATABASE=bmms_dev
DB_USERNAME=umesh (or root)
QUEUE_CONNECTION=database
MAIL_MAILER=smtp  (configure before notifications)
FILESYSTEM_DISK=local  (private storage for documents)
```

---

## Security Rules (non-negotiable)

- All documents in private storage — never public — signed URLs only
- Tenant isolation tested with automated tests before any production use
- Voting records are immutable after casting
- Approved minutes are locked — no edits without audit trail
- Secret ballot: voter identity hidden from normal viewers but preserved for audit
- CSRF protection on all forms (Laravel default)
- Input validation on every form and API endpoint
- Audit log: login, document upload, document view, board pack generation, vote cast, minutes approval, user/role changes, tenant settings changes
- HTTPS mandatory in production

---

## Known Decisions Made

- **Tenancy approach:** Single database, `tenant_id` column on every tenant-owned table (NOT stancl/tenancy package for Phase 1 — custom middleware for control and simplicity)
- **Admin panels:** Filament for superadmin and tenant admin panels
- **Interactive UI:** Alpine.js or Livewire for board member-facing screens
- **Document storage:** Private Laravel disk, signed temporary URLs
- **Payments:** Razorpay (India) + GST invoices — Phase 5 only
- **Out of scope Phase 1:** Video conferencing, AI transcription, native apps, digital signatures, white-label, dedicated DB per tenant
- **Laravel version:** Installed 13 (current stable as of 2026-06-18); spec said 12 but 13 is API-compatible for our use
- **Filament version:** v5 (not v3/v4) — significant API changes from v3 (see Filament v5 Notes below)

---

## Out of Scope (Do Not Build Yet)

- Built-in video conferencing
- AI-based live transcription or document summarisation
- Native Android/iOS apps
- Digital signature integration (Phase 6+)
- Self-hosted white-label for customers
- Enterprise dedicated database per tenant (Phase 2+)
- Offline sync (Phase 3+)

---

## Filament v5 Notes (Critical — API changed from v3/v4)

These traps will cause fatal errors if you use the old Filament v3 syntax:

```php
// WRONG (Filament v3/v4):
use Filament\Forms\Form;
public static function form(Form $form): Form { ... }
protected static ?string $navigationIcon = 'heroicon-o-users';
// In panel: ->authorization(fn($user) => ...)

// CORRECT (Filament v5):
use Filament\Schemas\Schema;
public static function form(Schema $schema): Schema { ... }
protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
// In User model: implement FilamentUser, add canAccessPanel(Panel $panel): bool

// Schema vs Form layout:
return $schema->components([
    \Filament\Schemas\Components\Section::make('Title')->schema([...]),  // Section from schemas
    \Filament\Forms\Components\TextInput::make('name'),                  // Inputs from forms
]);
```

**Spatie activitylog v5 namespace changes:**
```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;  // NOT Spatie\Activitylog\Traits\LogsActivity
use Spatie\Activitylog\Support\LogOptions;             // NOT Spatie\Activitylog\LogOptions
// dontSubmitEmptyLogs() is now dontLogEmptyChanges()
```

---

## Superadmin Credentials

- **URL:** `/admin`
- **Email:** `admin@bmms.in`
- **Password:** `Admin@1234`

---

## File Structure Created (Phase 1)

```
app/
  Models/
    Tenant.php
    TenantSetting.php
    User.php                        ← updated with BMMS fields + FilamentUser
    Traits/
      BelongsToTenant.php           ← use on ALL tenant-scoped models
  Http/Middleware/
    SetTenantContext.php            ← auto-registered on web stack
    EnsureTenantIsActive.php        ← alias: tenant.active
  Filament/
    Admin/                          ← superadmin panel (/admin)
      Resources/
        TenantResource.php + Pages/
        UserResource.php + Pages/
    Tenant/                         ← tenant panel (/manage) — EMPTY, build next session
      Resources/
      Pages/
      Widgets/
  Providers/Filament/
    AdminPanelProvider.php
    TenantPanelProvider.php
database/
  migrations/
    0000_00_00_000000_create_tenants_table.php
    0001_01_01_000000_create_users_table.php   ← replaced with BMMS schema
  seeders/
    RolesAndPermissionsSeeder.php
    SuperAdminSeeder.php
    DatabaseSeeder.php
```

---

## START HERE — Next Session

**Phase 1 COMPLETE. Phase 2 partially complete.**

### Phase 2 completed items:
- Departments, Committees CRUD (DepartmentResource, CommitteeResource)
- Meeting lifecycle (MeetingResource, 10-stage state machine, EditMeeting/ViewMeeting transition buttons)
- Agenda (AgendaRelationManager, reorderable)
- Attendees + Guests (AttendeesRelationManager, GuestsRelationManager)
- Documents (DocumentsRelationManager, Document/DocumentVersion models)
- Board Member Workbench (`/workbench` → WorkbenchController, workbench.blade.php)
- RSVP handler (RsvpController, POST `/rsvp/{attendee}`)
- BmsNotification model + BoardPack/BoardPackItem models
- Document management resource (DocumentResource, VersionsRelationManager, DocumentDownloadController)
- Board pack generator (BoardPackService → dompdf cover PDF + ZIP package, BoardPacksRelationManager, BoardPackController; auto-advances meeting status on publish)

### Phase 2 remaining work:
1. **Notification dispatch** — email on lifecycle transitions (scheduled → notice, rsvp_active → RSVP request, board_pack_generated → pack ready)
2. **Calendar view** — month/week calendar of meetings for board members

### Known issues to watch:
- `$navigationGroup` must be `string|\UnitEnum|null` (not `?string`) in all Filament resources
- Filament v5: all Actions in `Filament\Actions\*` namespace, never `Filament\Tables\Actions\*`
- `BelongsToTenant` global scope name is `'tenant'` — use `withoutGlobalScope('tenant')` for cross-tenant queries

---

## Session Startup Checklist

When starting a new Claude Code session:
1. `cd /var/www/html/bmms`
2. Read this `BMMS_CLAUDE.md`
3. Check `storage/logs/laravel.log` for errors
4. Confirm which phase/task we're working on
5. Run `php artisan route:list | grep <feature>` before adding routes
6. Run `php artisan migrate:status` to check migration state
