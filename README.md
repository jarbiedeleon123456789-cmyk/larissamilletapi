# Marrows API (LavaLust)

JSON API for the Marrows restaurant site. Built on LavaLust-dev-v4, MySQL on Aiven, deployed to Render with Docker.

## What it does

| Method | Route | Auth | Purpose |
|---|---|---|---|
| GET | `/health` | none | Health check |
| GET | `/api/menu` | none | Public menu for the website |
| POST | `/api/reservations` | none | Guest requests a table |
| POST | `/api/auth/login` | none | Staff sign in, returns access and refresh tokens |
| POST | `/api/auth/refresh` | refresh token | New token pair |
| POST | `/api/auth/logout` | none | Revokes the refresh token |
| GET | `/api/auth/me` | Bearer | Current user |
| GET / POST | `/api/products` | Bearer | List / add dishes |
| GET / PUT / PATCH / DELETE | `/api/products/{id}` | Bearer | Read / update / delete a dish |
| GET | `/api/reservations` | Bearer | List reservations |
| PATCH / DELETE | `/api/reservations/{id}` | Bearer | Confirm or cancel / delete |

Product CRUD is protected by the LavaLust `Api` library (JWT, refresh tokens, per-request user check, rate limiting).
`products` follows Laboratory Exercise No. 6 (`id, product_name, description, price, quantity, created_at`) plus a `category` column for menu courses.

## Migrations

Tables are created by the migration system from the "How to use Migration Class" activity.

```
php lava migration status
php lava migration run
php lava migration rollback
php lava migration rollback-all
php lava migration refresh
php lava migration create-migration create_something_table
```

Migrations: `000` migrations table, `001` users, `002` refresh_tokens, `003` products, `004` reservations, `005` seeds the staff account and 12 opening dishes.
Migration routes (`/migrate`, `/status`, ...) are registered as in the activity but return 403 over HTTP unless `?key=` matches `MIGRATION_KEY`. The CLI always works.
Migrations only run when `MIGRATION_ENABLED=true` (the Docker entrypoint sets it for its own run).

## Environment variables

See `.env.example`. Never commit a real `.env` (it is git-ignored).

| Variable | Notes |
|---|---|
| `APP_ENV` | `production` on Render |
| `APP_KEY` | random string |
| `DB_DRIVER` `DB_HOST` `DB_PORT` `DB_USER` `DB_PASSWORD` `DB_NAME` `DB_CHARSET` | From the Aiven service page. `DB_DRIVER=mysql`, `DB_CHARSET=utf8mb4` |
| `DB_SSL` | `true` for Aiven |
| `DB_SSL_VERIFY` | `false` works without a certificate. For full verification set `true` and paste the Aiven CA into `DB_SSL_CA` |
| `JWT_SECRET`, `REFRESH_TOKEN_KEY` | 32+ random characters, different from each other |
| `CORS_ORIGIN` | Your frontend URL, no trailing slash (comma separated for several) |
| `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | First staff login created by migration 005. Nothing is created if `ADMIN_PASSWORD` is empty |

## Deploy to Render

1. Push this folder to its own GitHub repository.
2. Render: New > Blueprint (uses `render.yaml`) or New > Web Service > Docker.
3. Fill the variables above. `JWT_SECRET`, `REFRESH_TOKEN_KEY` and `APP_KEY` are generated for you by the blueprint.
4. Deploy. On every start the container runs `php lava migration run`, then starts Apache on Render's `PORT`.
5. Open `https://<your-api>.onrender.com/health`, then `/api/menu`.
6. In Navicat, connect to the Aiven database (SSL on, use the CA file from Aiven) and confirm `migrations`, `users`, `refresh_tokens`, `products`, `reservations`.

## Run locally

```
cp .env.example .env     # fill in DB_*, JWT_SECRET, REFRESH_TOKEN_KEY, ADMIN_PASSWORD
php lava migration run
php lava serve 10000
```

Requires PHP 8.1+ with `pdo_mysql`.
