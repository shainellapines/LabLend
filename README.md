# LabLend

*A Web-Based Equipment and Room Reservation System with QR Asset Tracking for the IT Department.*

Reserve lab equipment and rooms online, without double bookings, and always know who has which item.

Built with Laravel 13, Breeze (Blade), Tailwind CSS, Alpine.js and MySQL 8.

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
php artisan schedule:work                      # run scheduled tasks (overdue reminders)
php artisan test                               # run the automated tests
```

---

## Demo accounts

*(Added when the seeders are built — build step 1.5.)*

| Role | Email | Password |
|---|---|---|
| admin | – | – |
| faculty | – | – |
| student | – | – |

---

## Project documentation

The full requirements (SRS), database schema, conventions and build order are in [`CLAUDE.md`](CLAUDE.md).
