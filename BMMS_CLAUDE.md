# BMMS — Claude Code Project Memory

**Board Meeting Management System**
Multi-Tenant SaaS Platform | Laravel 13 | Filament v5 | PHP 8.4 | MariaDB
**Last updated:** 2026-06-29 (end of session)
**GitHub:** https://github.com/umesh2512/bmms

---

## Project Overview

BMMS is a secure, multi-tenant SaaS board governance platform that replaces paper-based board packs,
fragmented email, manual minutes, disconnected voting, and spreadsheet action tracking with one digital
platform. Designed to serve education (colleges/universities), corporate (companies), NGOs, and
government/public sector organisations with configurable terminology and field sets per sector.

**SRS Version:** 2.1 (04 May 2026)
**Project folder:** `/var/www/html/bmms`
**Database:** `bmms_dev` (MariaDB / MySQL)

---

## Tech Stack

| Layer        | Technology                                         |
|--------------|----------------------------------------------------|
| Framework    | Laravel 13 (installed as 13, spec said 12 — API-compatible) |
| PHP          | 8.4+                                               |
| Database     | MariaDB 10.11+ / MySQL 8.0+                        |
| Server       | Ubuntu 24.04 LTS + Apache, served at `/bmms/`      |
| Admin panel  | Filament v5 (superadmin + tenant admin panels)     |
| Frontend     | Blade + Tailwind CSS + Alpine.js / Livewire         |
| Charts       | Filament ChartWidget (Chart.js underneath)         |
| RBAC         | spatie/laravel-permission v8                       |
| Audit logs   | spatie/laravel-activitylog v5                      |
| PDF export   | barryvdh/laravel-dompdf                            |
| Excel export | maatwebsite/excel                                  |
| Payments     | Razorpay (Phase 5 — not yet built)                 |
| Queue        | Laravel queues (database driver)                   |

---

## Credentials & URLs

| Panel       | URL                                    | Email                        | Password    |
|-------------|----------------------------------------|------------------------------|-------------|
| Superadmin  | `http://10.1.20.252/bmms/admin`        | `admin@bmms.in`              | `Admin@1234` |
| Tenant (BNCA) | `http://10.1.20.252/bmms/manage`     | `umesh.chavan@bnca.ac.in`    | `Bnca@1234`  |

**Test Data:**
- Tenant: BNCA (id=3, slug=bnca, sector=education)
- Test meeting: "Board Meeting - Q1 2026" (id=2), status=scheduled, 5 agenda items
- CSV-imported users in tenant 2 (test): John Doe, Jane Smith, Alice Johnson, Bob Williams
- Sample CSV: `http://10.1.20.252/bmms/samples/users-import-sample.csv`

---

## Architecture — Critical Rules

### Multi-Tenancy
- Every tenant-data table has `tenant_id` as a foreign key
- Global scope `'tenant'` filters queries automatically via `BelongsToTenant` trait
- `SetTenantContext` middleware sets `app('current_tenant_id')` on every request
- Use `->withoutGlobalScope('tenant')` for cross-tenant queries (superadmin, login)
- **Superadmin sees cross-tenant usage summaries ONLY — never meeting content**

### Roles (6 total)
```
superadmin        — SaaS platform owner, manages all tenants
tenant_admin      — Company Secretary / org administrator (can access /manage)
board_secretary   — Prepares meetings, agenda, board packs, minutes (can access /manage)
board_member      — Director/trustee, views packs, votes
department_head   — Department/committee level access
guest             — External observer, assigned meeting only
```

### Meeting Lifecycle (10 stages)
```
draft → scheduled → agenda_prepared → board_pack_generated → rsvp_active
→ in_progress → minutes_drafted → minutes_under_approval → closed → archived
```

---

## Database — Migrations (in order)

```
0000_00_00_000000_create_tenants_table
0001_01_01_000000_create_users_table
0001_01_01_000001_create_cache_table
0001_01_01_000002_create_jobs_table
2026_06_18_104559_create_activity_log_table
2026_06_18_104559_create_permission_tables
2026_06_29_000001_create_departments_table
2026_06_29_000002_create_committees_table
2026_06_29_000003_create_meetings_table
2026_06_29_000004_create_meeting_attendees_table
2026_06_29_000005_create_meeting_guests_table
2026_06_29_000006_create_meeting_status_logs_table
2026_06_29_000007_create_agenda_items_table
2026_06_29_000008_create_documents_table
2026_06_29_000009_create_meeting_documents_table
2026_06_29_000010_create_board_packs_table
2026_06_29_000011_create_bms_notifications_table
2026_06_29_000012_create_resolutions_table
2026_06_29_000013_create_action_items_table
2026_06_29_000014_create_minutes_table
2026_06_29_000015_add_plan_to_tenants_table         ← plan enum: free/sponsored/premium/enterprise
2026_06_29_000016_add_minutes_fields_to_agenda_items ← NAAC, HOD, points_discussed, decisions, action_by/date
```

### agenda_items columns (full — as of 2026-06-29)
```sql
id, meeting_id, parent_id, order_column, title,
naac_criteria_no,        -- education: NAAC criteria reference
hod_resolution_no,       -- education: HOD resolution number
hod_resolution_date,     -- education: date of HOD resolution
points_discussed,        -- longtext: body of discussion (replaces 'description')
decisions,               -- text: formal decisions taken
action_by,               -- string: responsible person name
action_date,             -- date: deadline for action
description,             -- original generic field (kept)
presenter_id,            -- FK users
time_allocated,          -- minutes
resolution_required,     -- boolean
notes,
timestamps
```

---

## Completed Work (as of 2026-06-29)

### ✅ Phase 1 — Foundation
- Laravel 13 + MariaDB setup
- Tenant model + TenantSetting (getSetting/setSetting, key-value store)
- BelongsToTenant trait — global scope on all tenant models
- SetTenantContext middleware + EnsureTenantIsActive middleware
- Authentication (Breeze Blade stack)
- RBAC — spatie/laravel-permission v8, 6 roles seeded
- User model — tenant_id, status, invitation_token, last_login_at, soft deletes
- Superadmin Filament panel at `/admin`
- Tenant Filament panel at `/manage`
- Audit logs — spatie/laravel-activitylog v5 on User model
- Tenant isolation tests — `tests/Feature/TenantIsolationTest.php`

### ✅ Phase 2 — Core BMMS
- Departments + Committees CRUD (DepartmentResource, CommitteeResource)
- Meeting lifecycle — MeetingResource, 10-stage state machine, ViewMeeting with transition buttons
- Agenda builder — AgendaRelationManager, reorderable (order_column)
- Attendees + RSVP — AttendeesRelationManager, GuestsRelationManager
- Documents — DocumentResource, DocumentsRelationManager, VersionsRelationManager, DocumentDownloadController
- Board member workbench — WorkbenchController, workbench.blade.php
- RSVP handler — RsvpController
- Board pack generator — BoardPackService (dompdf cover + ZIP), BoardPacksRelationManager, BoardPackController
- Meeting notifications — MeetingNotificationService hooked into transitionTo(), 6 triggers, email + BmsNotification

### ✅ Phase 3 — Governance
- Resolutions (ordinary/special/circular) + voting — Resolution, ResolutionVote, ResolutionsRelationManager
- Decision Register — DecisionRegisterResource (Governance group), circular resolution creation
- Action Items — ActionItem model, ActionItemsRelationManager, ActionItemResource
- Minutes drafting + approval + locking — Minutes model, MinutesRelationManager, MinutesService
- Circular resolutions (Flying Minutes) — signed-URL email, vote without login, CircularResolutionController
- Minutes PDF — pdf/minutes.blade.php, MinutesController@download
- Governance PDF export — pdf/governance-report.blade.php (4-section: meetings, attendance, resolutions, action items)

### ✅ Phase 4 — Analytics
- AnalyticsService — all query methods (tenant + platform scope)
- Tenant widgets: TenantMeetingStatsWidget, MeetingsByMonthChart, AttendanceRateChart, ResolutionOutcomeChart, ActionItemStatusChart
- QuickStartWidget — welcome card with quick-action links for new orgs
- TenantDashboard page — explicitly lists widgets (prevents widget bleed from Filament::getWidgets())
- AnalyticsDashboard page — /manage/analytics, Reports group, 3 export header actions
- Admin widgets: PlatformGrowthChart, PlatformMeetingsChart
- AnalyticsExportController — /analytics/export/meetings.csv, /analytics/export/actions.csv, /analytics/export/governance-report.pdf

### ✅ Superadmin Panel Enhancements (2026-06-29)
- TenantResource rewritten:
  - Auto-slug from org name (`->live(onBlur:true)->afterStateUpdated(fn(Set $set,...))`)
  - Plan field (free/sponsored/premium/enterprise badge column)
  - First Admin User section (name, email, password) — visible on create only
  - `use Filament\Schemas\Components\Utilities\Set` (NOT `Filament\Forms\Set`)
- CreateTenant page: `mutateFormDataBeforeCreate` extracts admin fields, `afterCreate` creates User + assigns tenant_admin role
- UserResource rewritten: full CRUD, password (required create / optional edit), role select (dehydrated:false), org assignment modal, suspend/activate, Reset Password modal
- CreateUser page: `afterCreate` → `syncRoles([$data['role']])`
- EditUser page: `mutateFormDataBeforeFill` pre-fills role, `afterSave` syncs role
- `->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope('tenant'))` on UserResource table

### ✅ Tenant Panel Enhancements (2026-06-29)
- TenantPanelProvider: `->readOnlyRelationManagersOnResourceViewPagesByDefault(false)` — agenda/attendee/document tabs are now editable on ViewMeeting
- Tenant UserResource — direct CRUD (no email-invite-only flow), password fields, role select, Reset Password/Change Role/Suspend/Activate/Delete row actions
- Tenant CreateUser page: sets `tenant_id = auth()->user()->tenant_id`, syncRoles
- Tenant EditUser page: pre-fills role, syncs on save
- CSV user import on ListUsers page — "Import CSV" header action, FileUpload modal, duplicate-email guard, auto-generated password if blank, syncRoles, success/error notification
- Sample CSV at `public/samples/users-import-sample.csv` — columns: name, email, designation, phone, role, password
- Decision Register fix: `->using()` callback instead of `->mutateFormDataBeforeCreate()` (which doesn't exist on CreateAction)
- ResolutionService: fixed route name `circular-resolution.show` (GET) for email voting links

### ✅ Settings System (2026-06-29)
- **SettingsService** (`app/Services/SettingsService.php`):
  - Central key-value registry with typed defaults
  - `get($key)`, `bool($key)`, `int($key)`, `all()`, `saveAll($data)`, `applySectorPreset($sector)`
  - Static `for(Tenant $tenant)` and `current()` constructors
  - Sector presets: education / corporate / ngo / government / other
  - Education preset → "Board of Management", NAAC + HOD fields on
  - Corporate preset → "Board of Directors", "Director", "Company Secretary"
  - NGO preset → "Executive Committee", "President", "Secretary"
  - Government preset → "Governing Council", "Member Secretary"
- **TenantSettingsPage** (`app/Filament/Tenant/Pages/TenantSettingsPage.php`):
  - 6 tabs: Organisation, Terminology, Meetings, Agenda & Minutes, Features, Notifications
  - Sector dropdown applies label presets via `->afterStateUpdated()`
  - Save button → `saveAll()` + `applySectorPreset()`
  - Route: `/manage/tenant-settings-page`
  - View: `resources/views/filament/tenant/pages/tenant-settings.blade.php`
- **AgendaRelationManager** updated:
  - Reads SettingsService on form() and table() — shows/hides NAAC Criteria, HOD Resolution, Decisions, Action By/Date based on tenant settings
  - Custom field labels 1 & 2 supported
  - "More Details" section (Presenter, Time) collapsed by default

### ✅ Agenda / Minutes Format (2026-06-29)
The agenda item form now matches BNCA board meeting minutes format:
- **Item No.** — auto-incremented
- **Title** — required
- **NAAC Criteria No.** — education only (toggle in settings)
- **HOD Resolution No. + Date** — education only (toggle in settings)
- **Points Noted and Discussed** — longtext, always visible
- **Decisions** — textarea, toggleable
- **Action By / Action Date** — responsible person + deadline, toggleable
- **Custom Fields 1 & 2** — user-defined labels in settings
- **More Details** (collapsed): Presenter, Time Allocated

---

## Key Files Reference

### Services
```
app/Services/AnalyticsService.php      — all analytics query methods
app/Services/SettingsService.php       — tenant settings registry + sector presets
app/Services/ResolutionService.php     — voting open/close + circular resolution emails
app/Services/MinutesService.php        — minutes PDF generation
app/Services/BoardPackService.php      — board pack PDF + ZIP
app/Services/MeetingNotificationService.php — lifecycle transition emails
```

### Superadmin Panel (app/Filament/Admin/)
```
Resources/TenantResource.php           — auto-slug, plan field, first admin user on create
Resources/TenantResource/Pages/CreateTenant.php — mutateFormDataBeforeCreate + afterCreate (creates admin user)
Resources/UserResource.php             — full CRUD, role select, org assign, suspend/activate
Resources/UserResource/Pages/CreateUser.php — afterCreate syncRoles
Resources/UserResource/Pages/EditUser.php   — mutateFormDataBeforeFill (pre-fill role), afterSave syncRoles
Widgets/PlatformStatsOverview.php
Widgets/PlatformGrowthChart.php
Widgets/PlatformMeetingsChart.php
```

### Tenant Panel (app/Filament/Tenant/)
```
Pages/TenantDashboard.php              — custom dashboard, explicit widget list
Pages/AnalyticsDashboard.php           — /manage/analytics, 3 export actions
Pages/TenantSettingsPage.php           — 6-tab settings page
Resources/UserResource.php             — direct CRUD, no email-invite
Resources/UserResource/Pages/CreateUser.php — sets tenant_id, syncRoles
Resources/UserResource/Pages/EditUser.php   — pre-fills role, syncs on save
Resources/UserResource/Pages/ListUsers.php  — CSV import header action + sample download
Resources/MeetingResource.php
Resources/MeetingResource/Pages/ViewMeeting.php
Resources/MeetingResource/RelationManagers/AgendaRelationManager.php   — settings-driven form/table
Resources/MeetingResource/RelationManagers/AttendeesRelationManager.php
Resources/MeetingResource/RelationManagers/GuestsRelationManager.php
Resources/MeetingResource/RelationManagers/DocumentsRelationManager.php
Resources/MeetingResource/RelationManagers/BoardPacksRelationManager.php
Resources/MeetingResource/RelationManagers/ResolutionsRelationManager.php
Resources/MeetingResource/RelationManagers/ActionItemsRelationManager.php
Resources/MeetingResource/RelationManagers/MinutesRelationManager.php
Resources/DecisionRegisterResource.php — uses ->using() for CreateAction (not mutateFormDataBeforeCreate)
Resources/ActionItemResource.php
Resources/DocumentResource.php
Resources/CommitteeResource.php
Resources/DepartmentResource.php
Widgets/TenantMeetingStatsWidget.php
Widgets/MeetingsByMonthChart.php
Widgets/AttendanceRateChart.php
Widgets/ResolutionOutcomeChart.php
Widgets/ActionItemStatusChart.php
Widgets/QuickStartWidget.php
```

### Providers
```
app/Providers/Filament/AdminPanelProvider.php
app/Providers/Filament/TenantPanelProvider.php  — includes readOnlyRelationManagersOnResourceViewPagesByDefault(false)
```

### Views
```
resources/views/filament/tenant/widgets/quick-start.blade.php
resources/views/filament/tenant/pages/tenant-settings.blade.php
resources/views/pdf/minutes.blade.php
resources/views/pdf/governance-report.blade.php
resources/views/pdf/board-pack-cover.blade.php
resources/views/circular-resolution/vote.blade.php
resources/views/workbench.blade.php
```

### Public Assets
```
public/samples/users-import-sample.csv    — columns: name,email,designation,phone,role,password
```

---

## Filament v5 Critical Rules (will cause fatal errors if wrong)

```php
// 1. $view, $heading, $maxHeight on widgets/pages — NON-static
protected string $view = 'filament.tenant.pages.foo';        // NOT static
protected ?string $heading = 'My Chart';                     // NOT static
protected ?string $maxHeight = '280px';                      // NOT static

// 2. $sort on Widget — IS static
protected static ?int $sort = 1;                             // static is correct

// 3. Navigation types
protected static string|\BackedEnum|null $navigationIcon  = Heroicon::OutlinedUsers;
protected static string|\UnitEnum|null   $navigationGroup = 'Governance';

// 4. Form schema
use Filament\Schemas\Schema;
public static function form(Schema $schema): Schema { return $schema->components([...]); }
// NOT Form $form

// 5. Set import (auto-slug, afterStateUpdated)
use Filament\Schemas\Components\Utilities\Set;              // NOT Filament\Forms\Set

// 6. Section import
use Filament\Schemas\Components\Section;                    // NOT Filament\Forms\Components\Section

// 7. CreateAction in headerActions() does NOT have mutateFormDataBeforeCreate
// Use ->using() instead:
Actions\CreateAction::make()->using(function (array $data): Model {
    $data['tenant_id'] = auth()->user()->tenant_id;
    return Model::create($data);
})

// 8. ViewRecord pages — relation managers are read-only by default
// Fix in PanelProvider:
->readOnlyRelationManagersOnResourceViewPagesByDefault(false)

// 9. Password field wiped by Livewire re-render on ->live() fields
// Fix:
TextInput::make('password')->dehydrated(true)

// 10. Filament Settings page (HasForms)
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;           // Pages use Form, not Schema
// form(Form $form): Form { return $form->schema([...])->statePath('data'); }
```

### Spatie activitylog v5 namespace changes
```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;   // NOT Traits\LogsActivity
use Spatie\Activitylog\Support\LogOptions;
// dontLogEmptyChanges() — NOT dontSubmitEmptyLogs()
```

---

## Settings System Quick Reference

```php
// Get a setting
$s = SettingsService::current();           // uses auth()->user()->tenant
$s = SettingsService::for($tenant);        // explicit tenant
$s->get('agenda_show_naac');               // string value
$s->bool('feature_committees');            // boolean
$s->int('meeting_quorum_percent');         // integer
$s->all();                                 // merged array (defaults + DB)

// Save settings (from form data array)
$s->saveAll($data);

// Apply sector preset
$s->applySectorPreset('education');        // education/corporate/ngo/government/other

// Available setting keys (with defaults):
// org_sector (education), org_website, org_established_year, org_address
// meeting_quorum_percent (51), meeting_notice_days (7), meeting_default_duration (120)
// meeting_allow_virtual (1), meeting_rsvp_required (1), meeting_max_guests (5)
// label_board (Board), label_chairperson (Chairperson), label_secretary (Board Secretary)
// label_member (Board Member), label_meeting (Meeting)
// agenda_show_naac (0), agenda_show_hod_resolution (0)
// agenda_show_action (1), agenda_show_decisions (1)
// agenda_custom_label_1, agenda_custom_label_2
// feature_committees (1), feature_departments (1), feature_circular_resolution (1)
// feature_action_items (1), feature_board_packs (1), feature_minutes (1), feature_analytics (1)
// notify_meeting_reminder (1), notify_reminder_days (3)
// notify_action_item_reminder (1), notify_action_due_days (2)
// notify_minutes_on_approval (1), notify_circular_resolution (1)
```

---

## CSV User Import

Format: `name,email,designation,phone,role,password`
- role defaults to `board_member` if blank or invalid
- password auto-generated (`Str::random(12).'!1'`) if blank
- duplicate emails skipped (no overwrite)
- Valid roles: `tenant_admin`, `board_secretary`, `board_member`, `guest`
- Sample: `public/samples/users-import-sample.csv`
- Import modal: Users list → "Import CSV" button → file upload + sample download link

---

## Pending / Next Session

### Phase 2 remaining
- [ ] Calendar view for meetings (month/week view for board members)
- [ ] Departmental drop folders + staged document submission

### Phase 3 remaining
- [ ] Action Taken Report (ATR) PDF/Excel export
- [ ] Conflict-of-interest declarations + register
- [ ] Document annotations (private)

### Settings system — future enhancements
- [ ] Feature toggles actually hide nav items (currently only affects agenda form)
  - Committees, Departments, Action Items, Analytics nav hidden when feature disabled
- [ ] Custom terminology used in navigation labels and page headings
- [ ] Logo upload in org settings (currently Tenant.logo_path exists but no UI)

### Phase 5 — SaaS Commercial
- [ ] Subscription plans + billing
- [ ] Razorpay integration
- [ ] Tenant usage limits enforcement

### Phase 6 — Production Hardening
- [ ] Security review
- [ ] Performance (indexes, caching, queued exports)
- [ ] Backup automation, queue workers, scheduler setup
- [ ] SSL + HTTPS, monitoring

---

## Common Commands

```bash
# Clear all caches
php artisan optimize:clear

# Check routes
php artisan route:list --path=manage

# Migration status
php artisan migrate:status

# Tail logs
tail -f storage/logs/laravel.log

# Tinker (no interactive mode for scripting)
php artisan tinker --no-interaction 2>&1 <<'EOF'
\App\Models\Tenant::all(['id','name','slug','status']);
EOF

# Fix permissions
chown -R www-data:www-data /var/www/html/bmms/storage
chmod -R 775 /var/www/html/bmms/storage /var/www/html/bmms/bootstrap/cache

# Git push (token required each time — store safely, do not commit)
git remote set-url origin https://umesh2512:<TOKEN>@github.com/umesh2512/bmms.git
git push
git remote set-url origin https://github.com/umesh2512/bmms.git
```

---

## Session Startup Checklist

1. Read this file fully
2. `php artisan migrate:status` — check for pending migrations
3. `tail -20 storage/logs/laravel.log` — check for recent errors
4. `git log --oneline -5` — see last 5 commits
5. Confirm which phase/feature we're working on
6. Run `php artisan route:list | grep <feature>` before adding new routes
