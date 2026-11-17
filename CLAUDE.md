# CLAUDE.md — LabLend

> Project context and Software Requirements Specification (SRS) for Claude Code.
> Place this file in the root of the Laravel project. Claude Code reads it automatically at the start of every session.

---

## 0. How to work with me (instructions for Claude Code)

- This is a **student final project** for a web development course (3 members, about **2 weeks**). Every member must be able to **understand and explain every part of the code** during the Q&A. So:
  - Build **one feature at a time**, following the Build Order in Section 12. Do not jump ahead or build several modules at once.
  - After each change, give a **short plain-language explanation** (2–5 sentences): what was built, which files changed, and *why* it was done that way (e.g., "validation is in a Form Request so the controller stays short").
  - When you use a Laravel concept for the first time (route model binding, policies, Form Requests, Eloquent scopes, notifications, the scheduler), add **one sentence explaining the concept** so we can repeat it in Q&A.
  - Prefer **simple, readable code** over clever code. Comment only where the logic is not obvious (e.g., the overlap check).
- **Ask before installing any Composer or npm package** that is not listed in Section 3.
- Follow MVC strictly (Section 9): no queries in Blade views, no business rules in Blade, no raw SQL with user input.
- After each finished feature: run `php artisan test` and `php artisan route:list` to confirm nothing broke, then tell us what to test manually in the browser.
- Suggest a **git commit message** after each feature (format in Section 9.6). Do not commit unless asked.
- When a build step is finished, tick its checkbox in Section 12.
- If something in this file is unclear or contradicts itself, **ask** instead of guessing.

---

## 1. Project Overview

**Name:** LabLend
**Tagline:** *A Web-Based Equipment and Room Reservation System with QR Asset Tracking for the IT Department.*
**One-liner:** Reserve lab equipment and rooms online, without double bookings, and always know who has which item.

### 1.1 Problem Statement
In our college/department, projectors, laptops, extension cords, HDMI cables, speakers, and rooms such as the computer labs and the AVR are lent out through **paper logbooks** and personal messages to the custodian. This causes:

1. **Double bookings.** Two classes or groups reserve the same projector or room for the same time because there is no shared, up-to-date schedule.
2. **No accountability.** When an item goes missing or is returned damaged, the logbook often cannot show who had it last or what condition it was in.
3. **Late and unreturned items.** Nobody follows up on overdue items until the next borrower needs them.
4. **No data for decisions.** The department cannot easily see which items are used most, which break often, or what needs replacing.

> Team note: support this with a short interview or survey of the lab custodian / faculty (e.g., "How do you track borrowed items now?"). One real quote makes the problem statement much stronger.

### 1.2 Solution
A Laravel web application where:
- The **custodian** manages the equipment inventory and rooms. Each item gets a **printable QR code**.
- **Students and faculty** reserve equipment and/or a room for a time slot. The system **blocks overlapping reservations**.
- The custodian **approves**, **releases** (hand-over), and **receives** (return) items, recording their condition each time.
- **Damage reports** are logged per item. A major damage report automatically puts the item under maintenance.
- A **scheduled task** emails borrowers with overdue items, and borrowers with overdue items cannot make new reservations.
- **Dashboards and reports** show usage, overdue items, and damage history, with PDF/CSV export.

### 1.3 Target Users
Lab custodian / laboratory in-charge, faculty members, and students of the IT department.

### 1.4 Why it is different (for the presentation / Q&A)
- Paper logbooks and Google Sheets **cannot stop double bookings**; LabLend checks every request against existing ones.
- **QR asset tags**: scan any item to see its status, current borrower, and full history.
- **Condition tracking** at release and return, plus damage reports, creates accountability.
- **Automated** overdue reminders and borrowing restrictions without manual follow-up.
- Supports **UN SDG 4 (Quality Education)** and **SDG 12 (Responsible Consumption)** — school property lasts longer when it is tracked.

---

## 2. User Roles

| Role | Who | Can do |
|---|---|---|
| `admin` | Lab custodian / lab in-charge | Everything: manage users, categories, equipment, rooms; approve/reject/release/receive reservations; manage damage reports; view all reports |
| `faculty` | Instructors | Reserve equipment and rooms (longer limits), view own reservations, report damage, view equipment pages |
| `student` | Students | Reserve equipment (and rooms, subject to approval), view own reservations, report damage, view equipment pages |

- New registrations get the `student` role by default. The admin can change a user's role or deactivate an account.
- Deactivated users (`is_active = false`) cannot log in.
- Roles are a simple `role` column on `users` (PHP backed enum), checked with **Policies** and a small `EnsureRole` middleware. No roles/permissions package (easier to explain).

---

## 3. Tech Stack

| Concern | Choice |
|---|---|
| Framework | **Laravel 13** (PHP **8.3+**) |
| Auth scaffolding | **Laravel Breeze — Blade stack** (`breeze:install blade`). Note: Breeze no longer gets new features, but v2.4+ supports Laravel 13 and is the simplest Blade-only option. |
| Views | **Blade** + Blade components, **Alpine.js** (comes with Breeze) for small interactions (modals, dropdowns) |
| CSS | **Tailwind CSS** (version that comes with Breeze) |
| Database | **MySQL 8** (via XAMPP, Laragon, or Herd) |
| PDF | `barryvdh/laravel-dompdf` |
| QR codes | `simplesoftwareio/simple-qrcode` (if it does not support Laravel 13, use `bacon/bacon-qr-code` directly to generate SVG) |
| Charts | `chart.js` via npm (dashboard only) |
| CSV export | Native Laravel `response()->streamDownload()` (no package) |
| Mail | `MAIL_MAILER=log` during development; Mailtrap or Gmail SMTP (app password) for the demo |
| Tests | PHPUnit (Laravel default) — feature tests for the important business rules |

**Environment check:** run `php -v` first. Laravel 13 needs PHP 8.3 or newer. If XAMPP has an older PHP, either update XAMPP, switch to Laragon/Herd, or (last resort) use Laravel 12 and tell the team.

### 3.1 Commands
```bash
composer create-project laravel/laravel lablend   # new project
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run dev                        # keep running while developing
php artisan migrate:fresh --seed                  # rebuild DB with demo data
php artisan serve                                 # http://127.0.0.1:8000
php artisan serve --host=0.0.0.0 --port=8000      # so phones on the same Wi-Fi can open QR links
php artisan schedule:work                         # run scheduled tasks locally (keep a terminal open)
php artisan lablend:send-overdue-reminders        # run the overdue command manually (for the demo)
php artisan test                                  # run tests
php artisan storage:link                          # make uploaded photos viewable
```

---

## 4. Modules Summary

| # | Module | Purpose | Main role |
|---|---|---|---|
| 1 | Users & Auth | Login, registration, roles, profile, admin user management | All / admin |
| 2 | Categories | Group equipment (Projectors, Laptops, Cables, Audio, ...) | admin |
| 3 | Equipment Inventory + QR | Individual items with asset tag, photo, condition, status, QR label | admin (CRUD), all (view) |
| 4 | Rooms | Computer labs, AVR, classrooms that can be reserved | admin (CRUD), all (view) |
| 5 | Reservations | Request → approve → release → return, with conflict checking | all |
| 6 | Damage Reports | Report damage/loss per item; repair status | all (create), admin (manage) |
| 7 | Notifications & Scheduler | Emails + in-app notifications; daily overdue reminders | system |
| 8 | Dashboard & Reports | Stats, charts, overdue list, PDF/CSV export | admin (full), others (own summary) |

---

## 5. Functional Requirements

Format: **FR-ID — requirement** → *acceptance criteria*.

### 5.1 Users & Auth (Module 1)
- **FR-U1** — Users can register, log in, log out, reset password, and edit their profile (Breeze).
  → Registration also asks for `id_number` (student/employee number, unique) and `department`. New users get the `student` role.
- **FR-U2** — Admin can list, search, view, edit, and deactivate/reactivate users, and change their role.
  → Admin cannot deactivate or demote their own account. Deactivated users are logged out and cannot log in (show a clear message).
- **FR-U3** — Role-based access.
  → Non-admins get a 403 page on admin-only routes. The navigation only shows links the user is allowed to use.

### 5.2 Categories (Module 2)
- **FR-C1** — Admin can create, view, edit, and delete categories (name unique, description).
  → A category that still has equipment cannot be deleted (show an error message instead).

### 5.3 Equipment Inventory + QR (Module 3)
- **FR-E1** — Admin can create, view, edit, and delete equipment items: asset tag, name, category, brand, model, serial number, location, condition, status, description, photo.
  → `asset_tag` is unique, auto-suggested from the category (e.g., `PRJ-0004`) but editable. Photo: jpg/png/webp, max 2 MB, stored on the `public` disk.
- **FR-E2** — Equipment list with search (name, asset tag, serial), filters (category, status, condition), and pagination (15 per page).
- **FR-E3** — Each item has a detail page at `/equipment/{asset_tag}` showing: photo, details, current availability (Available / Reserved / Borrowed by *name* until *time* / Under maintenance / Retired), upcoming reservations, borrowing history, and damage reports.
  → Students/faculty see the same page without edit buttons and without other borrowers' contact details.
- **FR-E4** — **QR code** on each item's detail page that encodes the item's URL. Admin can print a **QR label sheet** (PDF) for selected items or a whole category.
  → Scanning a label with a phone camera opens the item page (login required; redirects back after login).
- **FR-E5** — Deleting equipment uses **soft delete** so old reservations and reports still show the item. Items with active reservations (pending/approved/released) cannot be deleted.
- **FR-E6** — Equipment `status`: `available`, `under_maintenance`, `retired`. Only `available` items can be reserved. "Borrowed/Reserved" is **computed** from reservations, not stored.

### 5.4 Rooms (Module 4)
- **FR-RM1** — Admin can create, view, edit, and delete rooms: name, code (unique), type (`computer_lab`, `avr`, `classroom`, `other`), capacity, location, description, `is_active`.
- **FR-RM2** — Room detail page shows upcoming reservations for the next 7 days.
  → Inactive rooms cannot be reserved. Rooms with active reservations cannot be deleted.

### 5.5 Reservations (Module 5) — core module
- **FR-R1** — Any logged-in user can create a reservation: purpose, start date/time, end date/time, **one or more equipment items and/or one room**.
  → At least one item or a room is required. A unique `reference_no` is generated (e.g., `RSV-2026-0001`). Status starts as `pending`.
- **FR-R2 — Conflict check (overlap rule).** A reservation is rejected if any selected item or room already has a reservation with status `pending`, `approved`, or `released` whose time overlaps.
  → Overlap rule: `existing.start_at < new.end_at AND existing.end_at > new.start_at` (touching end-to-start is allowed). The error message names the item/room and the conflicting time. The same check runs again on **approve** and on **edit**.
- **FR-R3 — Borrowing rules** (values in `config/lablend.php`, Section 7):
  - start must be at least `min_lead_minutes` in the future and at most `max_days_ahead` days ahead;
  - end must be after start, and duration ≤ `max_hours` for the user's role;
  - a user with any **overdue** reservation cannot create a new one;
  - a student can have at most `student_max_active` active reservations (pending/approved/released).
  → Each broken rule shows a clear inline error.
- **FR-R4 — Status flow** (enforced in one place: `ReservationStatus` enum + `ReservationService`):
  ```
  pending  → approved | rejected | cancelled
  approved → released | cancelled
  released → returned
  (rejected, cancelled, returned are final)
  ```
  → Any other transition is refused with an error. Borrowers can cancel only their own `pending` or `approved` reservations. Only admin can approve, reject (reason required), release, and receive.
- **FR-R5 — Release (hand-over).** Admin marks a reservation as released and records `condition_out` for each item. `released_at` is saved.
- **FR-R6 — Return.** Admin marks it as returned and records `condition_in` for each item, plus remarks. If any item comes back `damaged`, the system opens the "Create damage report" form pre-filled for that item.
- **FR-R7 — Overdue** (computed): status `released` and `end_at < now()`. Shown as a red "Overdue" badge everywhere.
- **FR-R8 — Lists.**
  → Borrowers: "My Reservations" with tabs Upcoming / Active / History. Admin: all reservations with filters (status, date range, borrower, item/room), search by reference no., and quick actions (Approve / Reject / Release / Receive).
- **FR-R9** — Borrowers can edit their own reservation only while it is `pending` (same validation and conflict check).
- **FR-R10** — Printable **reservation slip** (PDF): reference no., borrower, items with asset tags, room, schedule, status, and signature lines for borrower and custodian.

### 5.6 Damage Reports (Module 6)
- **FR-D1** — Any logged-in user can report damage for an item: description, severity (`minor`, `major`, `lost`), optional photo, optional linked reservation.
- **FR-D2** — Admin can view, edit, and delete reports and update their status: `open` → `under_repair` → `resolved`, with resolution notes and `resolved_at`.
- **FR-D3** — When a `major` or `lost` report is created, the equipment status automatically becomes `under_maintenance` (major) or `retired` (lost). When resolved, admin may set the equipment back to `available`.
  → Any future reservations for that item get a warning on the admin reservation list.

### 5.7 Notifications & Scheduler (Module 7)
- **FR-N1** — Laravel Notifications (channels: `mail` + `database`) are sent when a reservation is **approved**, **rejected** (with reason), and when it becomes **overdue**. Admin gets a database notification for each **new** reservation request.
- **FR-N2** — A bell icon in the navbar shows unread notification count and a dropdown list; clicking marks it as read.
- **FR-N3 — Scheduled task.** Command `lablend:send-overdue-reminders` runs **daily at 08:00** (registered in `routes/console.php`). It finds overdue reservations and sends a reminder to each borrower, at most once per day (uses `overdue_notified_at`).
- **FR-N4** *(Phase 3, optional)* — Command `lablend:expire-pending` auto-cancels `pending` reservations whose start time has already passed.

### 5.8 Dashboard & Reports (Module 8)
- **FR-H1 — Admin dashboard:** cards for pending requests, items currently borrowed, overdue count, open damage reports, items under maintenance; a list of today's releases and returns; a bar chart of the top 5 most-borrowed items (last 30 days); a line chart of reservations per day (last 14 days).
- **FR-H2 — Borrower dashboard:** my upcoming reservations, my active borrowed items (with overdue warning), quick action "New Reservation".
- **FR-H3 — Reports page (admin):** usage report (borrow count per item/category for a date range), overdue report, damage report summary.
  → Each report exports to **PDF** (dompdf) and **CSV**.

---

## 6. Database Schema (MySQL)

All tables use `$table->id()` and `$table->timestamps()`. Date/times are `datetime` stored in the app timezone `Asia/Manila` (set `APP_TIMEZONE=Asia/Manila` / `config/app.php`).

### 6.1 Tables

**users** (extend Breeze's migration)
| Column | Type | Notes |
|---|---|---|
| name | string | |
| email | string unique | |
| password | string | |
| id_number | string(30) unique | student/employee no. |
| department | string nullable | e.g., "BSIT" |
| phone | string(20) nullable | |
| role | string(20) default `student` | enum: admin, faculty, student |
| is_active | boolean default true | |
| email_verified_at, remember_token | | Breeze default |

**categories**
| name | string unique |
| code | string(10) unique | prefix for asset tags, e.g., PRJ |
| description | text nullable |

**equipment**
| category_id | foreignId → categories, `restrictOnDelete` |
| asset_tag | string unique | e.g., PRJ-0004 |
| name | string | |
| brand | string nullable | |
| model | string nullable | |
| serial_number | string nullable | |
| location | string nullable | e.g., "Lab 2 cabinet" |
| condition | string(20) default `good` | good, fair, poor |
| status | string(20) default `available` | available, under_maintenance, retired |
| description | text nullable | |
| photo_path | string nullable | |
| deleted_at | softDeletes | |

**rooms**
| name | string |
| code | string unique |
| type | string(20) | computer_lab, avr, classroom, other |
| capacity | unsignedSmallInteger nullable |
| location | string nullable |
| description | text nullable |
| is_active | boolean default true |

**reservations**
| reference_no | string unique | RSV-YYYY-#### |
| user_id | foreignId → users, `restrictOnDelete` | borrower |
| room_id | foreignId nullable → rooms, `nullOnDelete` | |
| purpose | string | |
| start_at | datetime | index |
| end_at | datetime | index |
| status | string(20) default `pending` | see FR-R4 |
| approved_by | foreignId nullable → users | |
| approved_at | datetime nullable | |
| rejection_reason | string nullable | |
| released_at | datetime nullable | |
| returned_at | datetime nullable | |
| remarks | text nullable | |
| overdue_notified_at | datetime nullable | |
Composite index: `(status, start_at, end_at)`.

**equipment_reservation** (pivot, many-to-many)
| reservation_id | foreignId → reservations, `cascadeOnDelete` |
| equipment_id | foreignId → equipment, `restrictOnDelete` |
| condition_out | string(20) nullable | good, fair, poor |
| condition_in | string(20) nullable | good, fair, poor, damaged |
Unique: `(reservation_id, equipment_id)`.

**damage_reports**
| equipment_id | foreignId → equipment, `restrictOnDelete` |
| reservation_id | foreignId nullable → reservations, `nullOnDelete` |
| reported_by | foreignId → users |
| description | text |
| severity | string(20) | minor, major, lost |
| photo_path | string nullable |
| status | string(20) default `open` | open, under_repair, resolved |
| resolution_notes | text nullable |
| resolved_at | datetime nullable |

**notifications** — Laravel's table (`php artisan make:notifications-table`).

### 6.2 Eloquent relationships
- `User` hasMany `Reservation`; hasMany `DamageReport` (as `reported_by`)
- `Category` hasMany `Equipment`
- `Equipment` belongsTo `Category`; belongsToMany `Reservation` (pivot `equipment_reservation`, `withPivot('condition_out','condition_in')`, `withTimestamps()`); hasMany `DamageReport`
- `Room` hasMany `Reservation`
- `Reservation` belongsTo `User` (borrower), belongsTo `User` as `approver` (`approved_by`), belongsTo `Room`; belongsToMany `Equipment`; hasMany `DamageReport`
- `DamageReport` belongsTo `Equipment`, `Reservation`, `User` as `reporter`

### 6.3 Enums and casts
PHP backed enums in `app/Enums/`: `UserRole`, `ReservationStatus`, `EquipmentStatus`, `EquipmentCondition`, `RoomType`, `DamageSeverity`, `DamageStatus`. Each has a `label()` and `color()` method used by the `<x-status-badge>` component. Models cast columns to these enums and `start_at`/`end_at`/etc. to `datetime`.

### 6.4 Useful scopes
- `Reservation::scopeActive()` → status in pending, approved, released
- `Reservation::scopeOverdue()` → status released and `end_at < now()`
- `Reservation::scopeOverlapping($start, $end)` → the overlap rule from FR-R2
- `Equipment::scopeReservable()` → status available

### 6.5 Seeders (demo data)
`DatabaseSeeder` calls: `UserSeeder` (1 admin, 2 faculty, 5 students — known passwords for the demo, listed in README), `CategorySeeder` (Projectors, Laptops, Cables & Adapters, Audio, Extension Cords, Networking), `EquipmentSeeder` (~25 items), `RoomSeeder` (Lab 1, Lab 2, AVR, Room 301), `ReservationSeeder` (mix of statuses including **one overdue** and **one with a damage report**, spread across the last 30 days so charts have data). Use factories.

---

## 7. Business Rules / Config (`config/lablend.php`)

```php
return [
    'min_lead_minutes'   => 60,   // reserve at least 1 hour ahead
    'max_days_ahead'     => 14,
    'max_hours' => [
        'student' => 8,
        'faculty' => 72,
        'admin'   => 168,
    ],
    'student_max_active' => 3,
    'overdue_reminder_time' => '08:00',
    'reference_prefix'   => 'RSV',
];
```
No magic numbers in controllers or services — always read from this config.

---

## 8. Routes & Controllers

All routes in `routes/web.php` (auth routes stay in `routes/auth.php` from Breeze). Use **resource controllers** and **route model binding**.

| Middleware | Route(s) | Controller |
|---|---|---|
| `guest` | Breeze login/register/password | Breeze controllers |
| `auth`, `active` | `GET /dashboard` | `DashboardController` (shows admin or borrower view) |
| `auth`, `active` | `GET /equipment`, `GET /equipment/{equipment:asset_tag}` | `EquipmentController@index, show` |
| `auth`, `active` | `GET /rooms`, `GET /rooms/{room}` | `RoomController@index, show` |
| `auth`, `active` | `resource /reservations` (index, create, store, show, edit, update) | `ReservationController` |
| `auth`, `active` | `PATCH /reservations/{reservation}/cancel` | `ReservationController@cancel` |
| `auth`, `active` | `GET /reservations/{reservation}/slip` | `ReservationController@slip` (PDF) |
| `auth`, `active` | `GET /reservations/availability` (JSON) | `AvailabilityController` — used by the form to show conflicts before submit |
| `auth`, `active` | `resource /damage-reports` (index, create, store, show) | `DamageReportController` |
| `auth`, `active` | `GET /notifications`, `PATCH /notifications/{id}/read` | `NotificationController` |
| `auth`, `active`, `role:admin` | prefix `/admin`, name `admin.` | |
| | `resource categories` | `Admin\CategoryController` |
| | `resource equipment` (create, store, edit, update, destroy) | `Admin\EquipmentController` |
| | `GET equipment/qr-labels` | `Admin\QrLabelController` (PDF) |
| | `resource rooms` (create, store, edit, update, destroy) | `Admin\RoomController` |
| | `resource users` (index, show, edit, update) + `PATCH users/{user}/toggle-active` | `Admin\UserController` |
| | `GET reservations` (all), `PATCH reservations/{reservation}/approve`, `/reject`, `/release`, `/receive` | `Admin\ReservationActionController` |
| | `PATCH damage-reports/{damageReport}` (status update), `DELETE` | `Admin\DamageReportController` |
| | `GET reports`, `GET reports/{type}/export?format=pdf\|csv` | `Admin\ReportController` |

**Middleware**
- `active` → `EnsureUserIsActive` (logs out deactivated users)
- `role:admin` → `EnsureRole` (403 if role does not match)

**Policies** (`app/Policies/`): `ReservationPolicy` (view/update/cancel own; admin all), `DamageReportPolicy`, `EquipmentPolicy`, `RoomPolicy`, `CategoryPolicy`, `UserPolicy`. Use `$this->authorize()` / `Gate` in controllers and `@can` in Blade.

---

## 9. Architecture & Coding Conventions

### 9.1 MVC + small services
- **Models** (`app/Models`): relationships, casts, scopes, small accessors. No HTTP logic.
- **Controllers**: thin. Receive a **Form Request**, call a model or service, return a view or redirect with a flash message. Target ≤ ~15 lines per method.
- **Form Requests** (`app/Http/Requests`): all validation rules + custom messages (e.g., `StoreReservationRequest`, `UpdateEquipmentRequest`).
- **Services** (`app/Services`): business logic used in more than one place:
  - `ReservationService` — `create()`, `update()`, `transition()` (status flow), `findConflicts()`, reference number generation. Uses `DB::transaction()` when saving a reservation and its pivot rows.
  - `AssetTagService` — next asset tag per category.
  - `ReportService` — queries for the reports/dashboard.
- **Views** (`resources/views`): display only. Use Blade components for repeated UI.
- **Notifications** (`app/Notifications`): `ReservationApproved`, `ReservationRejected`, `ReservationOverdue`, `NewReservationRequest`.
- **Console** (`app/Console/Commands`): `SendOverdueReminders`, (optional) `ExpirePendingReservations`; scheduled in `routes/console.php`.

### 9.2 Database & queries
- Eloquent everywhere; **eager load** relationships in lists (`with('user', 'equipment', 'room')`) to avoid N+1 queries.
- Never build SQL by concatenating user input.
- Use `paginate(15)->withQueryString()` for all lists.
- Every schema change = a **new migration**. Never edit a migration that teammates already ran (unless we all agree to `migrate:fresh`).

### 9.3 Files
- Uploads on the `public` disk under `equipment/` and `damage-reports/`. Delete the old file when a photo is replaced or the record is force-deleted.

### 9.4 Naming
- Controllers `PascalCaseController`, models singular (`Equipment`, `Reservation`), tables plural snake_case, pivot `equipment_reservation`.
- Route names: `reservations.index`, `admin.equipment.edit`, etc. Always use `route()` in Blade, never hard-coded URLs.
- Views: `resources/views/{module}/{action}.blade.php`, admin views under `resources/views/admin/...`.

### 9.5 UX feedback & errors
- Every create/update/delete redirects with a flash message (`session('success')` / `session('error')`) shown by `<x-flash />`.
- Delete always uses a confirmation modal (`<x-confirm-delete>` with Alpine).
- Wrap file and PDF operations in `try/catch`; show a friendly message; `report($e)` the details.

### 9.6 Git
- Branch per member/module: `feature/inventory`, `feature/reservations`, `feature/reports`. Merge to `main` through the leader.
- Commit format: `feat(reservations): add overlap conflict check`, `fix(equipment): delete old photo on update`.
- Never commit `.env`. Keep `.env.example` updated.

### 9.7 Tests (shows "advanced" understanding)
Feature tests in `tests/Feature/` for the rules most likely to be asked about in Q&A:
- overlapping reservation is rejected; touching times are allowed;
- user with an overdue reservation cannot create a new one;
- student cannot exceed `student_max_active`;
- invalid status transition is refused (e.g., `pending → returned`);
- non-admin gets 403 on `/admin/*`;
- overdue command sends one notification per overdue reservation (use `Notification::fake()`).

---

## 10. UI / Design Guidelines

Goal (rubric): **intuitive, visually appealing, consistent**. All screens use the same layout, components, and colors.

### 10.1 Layout
- Logged-in layout: **left sidebar** (collapsible on mobile) + top bar with page title, notification bell, and user menu.
- Sidebar items by role:
  - All: Dashboard, Equipment, Rooms, My Reservations, Damage Reports
  - Admin adds: Requests (with pending count badge), Categories, Users, Reports
- Primary action button at the top-right of each list page (e.g., "+ New Reservation").
- Responsive: usable on a phone (important for QR scanning and borrowers).

### 10.2 Colors (`tailwind.config.js` theme extend)
Style: **Clean SaaS / modern operational UI**. Dark sidebar, light content area, white cards, one brand gradient for hero areas. Dashboards are an **operational + analytical hybrid** (KPI tiles and today's work, plus charts).
- **Primary (`primary-*`):** Dark Azure `#00638E` = `primary-600` (buttons, links, active nav); Light Azure `#BFD8E3` = `primary-200`; full scale 50–950 in the config
- **Sidebar:** Onyx `onyx` `#141414`; hover/borders Charcoal `charcoal` `#2B2B2B`
- **Branding gradient (`bg-brand`):** `#000000 → #004A6B → #00638E → #8CB9CC` (dashboard banner, login panel, logo)
- **Background `canvas`:** `#F4F7F9`; **Card:** Paper White `#FFFFFF`; **Text `ink`:** `#141414`; **Muted:** `#64748B`; **Border `line`:** `#E3E9ED`
- **Status badges:**
  - Reservation: Pending amber, Approved blue, Released indigo, Returned green, Rejected red, Cancelled gray, **Overdue red (solid)**
  - Roles: Admin azure, Faculty blue, Student gray
  - Equipment: Available green, Borrowed/Reserved indigo, Under maintenance amber, Retired gray
  - Damage: Open red, Under repair amber, Resolved green
- Radius: `rounded-xl` cards, `rounded-lg` inputs/buttons. Spacing: Tailwind scale only (no inline styles).
- Font: Breeze default (Figtree) — keep it.

### 10.3 Blade components (`resources/views/components/`)
Reuse Breeze's (`text-input`, `input-label`, `input-error`, `primary-button`, `secondary-button`, `danger-button`, `modal`, `dropdown`) and add:
`card`, `page-header`, `stat-card`, `status-badge`, `flash`, `empty-state`, `confirm-delete`, `data-table` (header slot + rows slot), `filter-bar`, `select`, `textarea`, `datetime-input`, `photo-upload` (with preview), `qr-code`.

### 10.4 UX rules
- Every list: search/filter bar, pagination, empty state with a call-to-action, row click or "View" link.
- Forms: labels on every input, required fields marked with `*`, inline errors, old input kept after validation errors.
- Reservation form: after picking date/time, the item and room pickers show which ones are **unavailable** for that slot (via the `availability` JSON endpoint), so users see conflicts *before* submitting.
- Date display format: `M d, Y g:i A` (e.g., "Nov 20, 2026 1:30 PM").
- Show loading state on PDF export buttons.

---

## 11. Non-Functional Requirements

| ID | Requirement |
|---|---|
| NFR-1 | Runs locally with PHP 8.3+, MySQL 8, Node 20+; setup steps documented in `README.md`. |
| NFR-2 | Passwords hashed (Laravel default); CSRF on all forms; authorization via policies/middleware on every route. |
| NFR-3 | All input validated server-side with Form Requests. |
| NFR-4 | List pages load in under 1 second with the seeded data (~25 items, ~100 reservations); no N+1 queries. |
| NFR-5 | Responsive layout from 360 px (phone) to desktop. |
| NFR-6 | Consistent UI using the shared components and colors (Section 10). |
| NFR-7 | Code follows Section 9; `php artisan test` passes before every merge to `main`. |

### Out of scope
Online payments/penalty fees, consumable supplies (bond paper, ink), multi-campus support, mobile app, Google Calendar sync, SMS.

### Future enhancements (for the presentation)
In-app QR scanner for faster release/return, SMS reminders, penalty points for repeated late returns, maintenance schedule per item, multi-department support, calendar view of all reservations.

---

## 12. Build Order (about 14 days)

Work top to bottom. Tick each item when done.

### Phase 1 — Foundation (Days 1–3)
- [x] 1.1 Project setup: Laravel 13 + Breeze (Blade), MySQL connection, `APP_TIMEZONE=Asia/Manila`, Git repo, `README.md` with setup steps
- [x] 1.2 Base layout: sidebar + top bar, colors in Tailwind config, shared Blade components (`card`, `page-header`, `status-badge`, `flash`, `empty-state`, `confirm-delete`)
- [x] 1.3 Users: add `id_number`, `department`, `phone`, `role`, `is_active`; `UserRole` enum; update registration; `EnsureRole` + `EnsureUserIsActive` middleware; role-based sidebar
- [x] 1.4 Categories CRUD (admin) — **this becomes the CRUD pattern** for the rest (Form Request, policy, flash messages, confirm delete)
- [x] 1.5 All migrations + models + relationships + enums (Section 6), factories and seeders — also add the FR-C1 "category with equipment cannot be deleted" check in `Admin\CategoryController@destroy` + test

### Phase 2 — Core modules (Days 4–9)
- [x] 2.1 Equipment CRUD + photo upload + search/filters + soft delete (FR-E1, E2, E5, E6)
- [x] 2.2 Equipment detail page with computed availability + history (FR-E3)
- [x] 2.3 Rooms CRUD + detail page (FR-RM1, RM2)
- [x] 2.4 Reservation create/edit with validation rules and **conflict check** in `ReservationService` (FR-R1–R3, R9) + feature tests
- [x] 2.5 Status flow: cancel, approve/reject, release, receive with item conditions (FR-R4–R7) + feature tests
- [x] 2.6 Reservation lists: My Reservations tabs + admin requests list with filters and quick actions (FR-R8)
- [x] 2.7 Damage reports CRUD + automatic equipment status change (FR-D1–D3) — also change `Admin\ReservationActionController@receive` to redirect to the pre-filled damage report form when an item comes back `damaged` (FR-R6)
- [x] 2.8 Admin user management (FR-U2, U3)

### Phase 3 — Stand-out features (Days 10–12)
- [ ] 3.1 Notifications: mail + database, bell dropdown (FR-N1, N2)
- [ ] 3.2 Overdue reminders command + scheduler + borrowing block for overdue users (FR-N3, FR-R3) + test
- [ ] 3.3 QR code on item page + printable QR label sheet PDF (FR-E4)
- [ ] 3.4 Availability JSON endpoint + live "unavailable" hints in the reservation form
- [ ] 3.5 Dashboards with stat cards and Chart.js charts (FR-H1, H2)
- [ ] 3.6 Reports page with PDF + CSV export; reservation slip PDF (FR-H3, FR-R10)

### Phase 4 — Polish & presentation (Days 13–14)
- [ ] 4.1 UI consistency pass on every page (spacing, badges, empty states, mobile view)
- [ ] 4.2 Final demo seed data; known demo accounts in `README.md`
- [ ] 4.3 Full manual test of the demo scenario (Section 14); `php artisan test` all green
- [ ] 4.4 (Optional) `lablend:expire-pending` command (FR-N4)
- [ ] 4.5 Record presentation video; finalize PPT

> If time runs short, cut in this order: 4.4 → 3.4 → charts in 3.5 (keep stat cards) → CSV export. **Never cut** the conflict check, status flow, damage reports, or overdue reminders — they are the core story.

---

## 13. Team Roles (3 members — edit names)

| Member | Module ownership | Also responsible for |
|---|---|---|
| Member 1 (Leader) | Setup, layout & components, Users & roles, Categories, Equipment + QR, Rooms | Code review, merging, README |
| Member 2 | Reservations: form, conflict check, status flow, lists, availability endpoint, reservation slip PDF, related tests | Demo script |
| Member 3 | Damage reports, Notifications, Scheduler/overdue command, Dashboards, Reports & exports | Seed/demo data |
| All | UI consistency, testing, documentation, PPT, video | Reflections |

Each member must be able to explain: the screens in their module, the tables and relationships they use, the controller → Form Request → service/model flow, one Laravel concept they learned (e.g., policies, scheduler, notifications, eager loading), and one challenge they solved.

---

## 14. Demo Scenario (System Demonstration)

1. **Admin (custodian)** logs in → dashboard shows pending requests, borrowed items, and **1 overdue item**.
2. Admin adds a new projector with a photo → asset tag auto-suggested → opens its page → **QR code** shown → prints the QR label sheet.
3. **Phone scans the QR label** → item page opens showing status and history.
4. **Student** logs in → New Reservation → picks tomorrow 1:00–3:00 PM → the form shows the projector already reserved → picks another one → submits → status **Pending**.
5. Second student tries to reserve the **same item and time** → **conflict error** names the clashing reservation.
6. Admin approves → student gets an **email + bell notification** → admin **releases** the item (condition: good) → prints the reservation slip.
7. Admin **receives** the return with condition **damaged** → damage report form opens pre-filled → severity **major** → item becomes **Under maintenance** automatically.
8. Run `php artisan lablend:send-overdue-reminders` → overdue borrower receives a reminder email → that borrower tries to reserve → **blocked**.
9. Reports page → most-borrowed chart → export usage report to **PDF** and **CSV**.
10. Close: explain conflict check, status flow, and accountability (who had what, in what condition).
