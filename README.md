# DashboardAnalytics — Setup Guide

## Requirements

| Dependency | Minimum version |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| MySQL | 8.0+ (or MariaDB 10.6+) |

---

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env .env.local
```

Edit `.env.local` and set your database credentials:

> **Before running `doctrine:database:create`, check your exact MySQL version.**
> Open **phpMyAdmin** and run the following query:
>
> ```sql
> SELECT VERSION();
> ```
>
> Use the returned version string in your `DATABASE_URL`. For example, if the result is `8.4.7`:
>
> ```dotenv
> DATABASE_URL="mysql://root:@127.0.0.1:3306/dash-analytics?serverVersion=8.4.7&charset=utf8"
> ```
>
> Doctrine uses `serverVersion` to generate correct SQL — a mismatch can cause migration or schema errors.

```dotenv
# MySQL 8.4.7 — no password (replace version as needed from SELECT VERSION())
DATABASE_URL="mysql://root:@127.0.0.1:3306/dash-analytics?serverVersion=8.4.7&charset=utf8"

# MySQL 8.4.7 — with password
DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/dash-analytics?serverVersion=8.4.7&charset=utf8"

# MariaDB 10.6
# DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/dash-analytics?serverVersion=10.11.2-MariaDB&charset=utf8"

APP_SECRET=replace_with_a_random_32_char_string
```

### 3. Create the database

```bash
php bin/console doctrine:database:create
```

### 4. Run migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 5. Seed data

```bash
php bin/console app:dashboard:seed
```

> **Memory limit error?** If you hit a `PHP Fatal error: Allowed memory size exhausted` during seeding, raise the memory limit inline:
>
> ```bash
> php -d memory_limit=512M bin/console app:dashboard:seed
> ```

### 6. Start the server

```bash
php -S localhost:8000 -t public/
```

Open **http://localhost:8000/dashboard**

---

## Full setup — one command sequence

```bash
composer install
cp .env .env.local
# edit .env.local with your DATABASE_URL
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:dashboard:seed
php -S localhost:8000 -t public/
```

---

## Seed commands

```bash
# Default — 100,000 rows
php bin/console app:dashboard:seed

# Custom count
php bin/console app:dashboard:seed --count=500000
php bin/console app:dashboard:seed --count=1000000

# Quick test — 10,000 rows
php bin/console app:dashboard:seed --count=10000

# Truncate and re-seed
php bin/console doctrine:query:sql "TRUNCATE TABLE dashboard_read_entries"
php bin/console app:dashboard:seed
```

---

## Other useful commands

```bash
# Clear cache
php bin/console cache:clear

# Run unit tests
php bin/phpunit tests/Unit

# Validate database mapping
php bin/console doctrine:schema:validate

# Show migration status
php bin/console doctrine:migrations:status

# Run async event worker
php bin/console messenger:consume async --limit=100

# List all app commands
php bin/console list app
```
