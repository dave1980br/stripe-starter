# Stripe Checkout + Order Ledger Demo (PHP + SQLite)

A small e-commerce demo that showcases a production-style Stripe integration:

- Stripe Payment Element (PaymentIntents)
- Server-calculated totals (subtotal + tax + shipping)
- Webhook-driven finalization (paid/failed/refunded)
- Idempotent webhook processing (stored by Stripe event id)
- Order ledger / audit trail
- Admin portal with login + one-click demo refund

## Tech
- PHP 8.3+
- SQLite (single-file DB)
- Stripe PHP SDK (via Composer)

## Local setup

### 1) Install dependencies
Run:
    composer install

### 2) Create config
Copy the example config and fill in your Stripe sandbox keys:
    cp src/Config/config.example.php src/Config/config.local.php

Update values in src/Config/config.local.php:
- publishable_key
- secret_key
- webhook_secret

NOTE: config.local.php is gitignored and must not be committed.

### 3) Initialize SQLite database
Run:
    php bin/init_db.php

DB file is created at:
- var/app.sqlite

### 4) Serve the app
Point your web server docroot at public/ (or use a /demos/... symlink approach).

## Stripe webhook

Create a webhook endpoint in your Stripe sandbox:
- POST https://<your-domain>/webhooks/stripe

Subscribe to:
- payment_intent.succeeded
- payment_intent.payment_failed
- charge.refunded

Paste the signing secret (whsec_...) into config.local.php.

## Test payments

Use Stripe test cards:
- Success: 4242 4242 4242 4242
- Fail (insufficient funds): 4000 0000 0000 9995

Any future expiry date, any CVC, any ZIP.

## Admin portal

Open:
- /admin

The demo admin user is seeded in SQLite in database/seed.sql (email/password hash).
Update the seeded user to match your preferred credentials.

## License
MIT (see LICENSE).
