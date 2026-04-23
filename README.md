# LoE Tracking Prototype

Prototype for a simplified daily LoE workflow built with Laravel 12, PHP 8.2, React, TypeScript, and MySQL.

## What it demonstrates

- Employee `Mauizah Nasir` as the main demo user
- Assigned project tabs for `BEE`, `BDC`, and `Ermassess`
- Jira tickets listed under each project with `0h` by default so the employee fills the effort manually
- `Meetings & Misc` and `Time Off` auto-populated from Google Calendar
- A strict `9-hour` daily total that the employee needs to mark down
- A simple form to add more projects for future assignment

## Demo rules

- Jira provides ticket rows only, not hours
- Google Calendar auto-fills meetings and misc activity hours
- Google Calendar auto-fills time off hours
- Whole-day time off should count as `9h`
- Partial time off should use the event duration only

## Run locally

Make sure a MySQL database named `loe_tracking` exists and your local credentials in `.env` are correct.

```powershell
php artisan migrate:fresh --seed
php artisan serve
```

In another terminal:

```powershell
& "C:\nvm4w\nodejs\npm.cmd" run dev
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Useful commands

```powershell
php artisan test
& "C:\nvm4w\nodejs\npm.cmd" run build
```
