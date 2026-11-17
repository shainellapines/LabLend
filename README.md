# LabLend

*A Web-Based Equipment and Room Reservation System with QR Asset Tracking for the IT Department.*

Reserve lab equipment and rooms online, without double bookings, and always know who has which item.

Built with Laravel 13, Breeze (Blade), Tailwind CSS, Alpine.js and MySQL 8.
Extra packages: `barryvdh/laravel-dompdf` (PDFs), `simplesoftwareio/simple-qrcode` (QR codes), `chart.js` (dashboard charts).

## Features

- **Reservations without double bookings:** every request is checked against pending, approved, and borrowed reservations; the form shows taken items live before you submit.
- **Status flow:** pending → approved → released (hand-over, condition recorded) → returned (condition recorded); reject with a reason; cancel.
- **QR asset tags:** each item page has a QR code; print a label sheet (PDF) per category or for chosen items.
- **Damage reports:** major damage puts an item under maintenance, a lost item is retired, automatically.
- **Notifications:** email + in-app bell for approvals, rejections, overdue items, and new requests (admin).
- **Overdue reminders:** a daily scheduled command emails overdue borrowers; they cannot reserve until they return the items.
- **Dashboards & reports:** KPI tiles, charts, today's schedule; usage / overdue / damage reports in PDF and CSV; printable reservation slip.

---

## Requirements

| Tool | Version | Check with |
|---|---|---|
| PHP | 8.3 or newer (extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `intl`) | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 or newer | `node -v` |
| MySQL | 8.x | `mysql --version` |

We use **Laragon** (Full edition) on Windows, which includes PHP 8.3 and MySQL 8.4.

> **Windows tip:** if `php -v` still shows an old version (e.g. XAMPP's PHP 8.0), remove `C:\xampp\php` from the System `Path`, add Laragon's PHP folder (`C:\laragon\bin\php\php-8.3.x...`), then restart VS Code / the terminal. If Composer complains about `zip`, open Laragon → PHP → `php.ini` and remove the `;` in front of `extension=zip`.

---

## Setup (first time)

```bash
git clone <repo-url> lablend
cd lablend

composer install
npm install

cp .env.example .env          # Windows PowerShell: copy .env.example .env
php artisan key:generate
```

Create the database (Laragon: start MySQL first, then use HeidiSQL or the terminal):

```sql
CREATE DATABASE lablend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Check these values in `.env`:

```
APP_TIMEZONE=Asia/Manila
DB_CONNECTION=mysql
DB_DATABASE=lablend
DB_USERNAME=root
DB_PASSWORD=
MAIL_MAILER=log
```

Then build the database and link the storage folder:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

---

## Running the app

Open two terminals:

```bash
npm run dev          # terminal 1 – Vite (CSS/JS), keep it running
php artisan serve    # terminal 2 – http://127.0.0.1:8000
```

Optional:

```bash
php artisan serve --host=0.0.0.0 --port=8000   # let phones on the same Wi-Fi open QR links
php artisan schedule:work                      # run scheduled tasks (overdue reminders at 08:00)
php artisan lablend:send-overdue-reminders     # send overdue reminders now (for the demo)
php artisan test                               # run the automated tests (no MySQL needed)
```

Emails are written to `storage/logs/laravel.log` while `MAIL_MAILER=log`. For the demo, put Mailtrap or Gmail SMTP (app password) settings in `.env` to send real emails.

### Scanning QR codes with a phone

A QR code contains the address you are using when you open the item page or print the labels.
1. Start the server with `php artisan serve --host=0.0.0.0 --port=8000`.
2. Find your computer's network address (`ipconfig` → IPv4 Address, e.g. `192.168.1.10`).
3. Open LabLend at `http://192.168.1.10:8000` on the computer, then print the labels.
4. The phone must be on the same Wi-Fi. If it cannot connect, allow PHP through Windows Firewall.

---

## Demo accounts

Created by `php artisan migrate:fresh --seed`. **Every password is `password`.**

| Role | Name | Email | Notes |
|---|---|---|---|
| admin | Lab Custodian | `admin@lablend.test` | The custodian: approves, releases, receives |
| faculty | Maria Santos | `faculty@lablend.test` | Has a laptop (LAP-0001) out right now, due in 2 days |
| faculty | Jose Reyes | `faculty2@lablend.test` | Has PRJ-0001 booked **tomorrow 1:00–3:00 PM** (demo step 4) |
| student | Juan Dela Cruz | `student@lablend.test` | Use this one to make a new reservation in the demo |
| student | Angela Mendoza | `student2@lablend.test` | Returned a damaged speaker (AUD-0001) 5 days ago |
| student | Mark Villanueva | `student3@lablend.test` | |
| student | Kristine Garcia | `student4@lablend.test` | |
| student | Paolo Ramos | `student5@lablend.test` | **Overdue** projector (PRJ-0002): blocked from new reservations |

The seed data also has 6 categories, 26 items, 4 rooms, and about 100 reservations from 30 days ago to 7 days ahead (no double bookings). The random choices use a fixed seed, so everyone on the team gets the same data.

> **Demo day:** the seed data is relative to *today* ("overdue since yesterday", "booked tomorrow"). Run `php artisan migrate:fresh --seed` on the morning of the demo (this deletes everything else in the database).

---

## Demo script (CLAUDE.md section 14)

| # | Who | What to show |
|---|---|---|
| 1 | `admin@` | Dashboard: pending requests, items borrowed, **1 overdue** (click the tile) |
| 2 | `admin@` | Equipment → New Item (Projectors) → asset tag is suggested → save → item page with **QR code** → Print label |
| 3 | phone | Scan the label → item page (log in) → status and history |
| 4 | `student@` | New Reservation → tomorrow 1:00–3:00 PM → **PRJ-0001 shows "Reserved"** → pick PRJ-0003 → submit → **Pending** |
| 5 | `student2@` | Same item, same time → **conflict error** naming the reservation |
| 6 | `admin@` | Requests → Approve (student gets **email + bell**) → Release (condition Good) → **Print slip** |
| 7 | `admin@` | Receive → mark the item **Damaged** → damage report form opens → severity **Major** → item is **Under maintenance** |
| 8 | terminal | `php artisan lablend:send-overdue-reminders` → log in as `student5@` → bell shows the reminder → New Reservation is **blocked** |
| 9 | `admin@` | Dashboard → most-borrowed chart; Reports → usage table → export **PDF** and **CSV** |

Step 8 sends a reminder **at most once per day**. If you rehearse on demo day, run `php artisan migrate:fresh --seed` again before the real demo.

---

## Project documentation

The full requirements (SRS), database schema, conventions and build order are in [`CLAUDE.md`](CLAUDE.md).
