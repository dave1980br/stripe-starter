PRAGMA foreign_keys = ON;

-- Products shown in the demo shop
CREATE TABLE IF NOT EXISTS products (
  product_id            INTEGER PRIMARY KEY AUTOINCREMENT,
  sku                   TEXT NOT NULL UNIQUE,
  name                  TEXT NOT NULL,
  description           TEXT NOT NULL DEFAULT '',
  unit_price_cents      INTEGER NOT NULL CHECK (unit_price_cents >= 0),
  is_active             INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0,1)),
  created_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),
  updated_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now'))
);

-- Orders created server-side before payment confirmation
CREATE TABLE IF NOT EXISTS orders (
  order_id              INTEGER PRIMARY KEY AUTOINCREMENT,
  order_public_id       TEXT NOT NULL UNIQUE, -- safe to show in URLs / receipts
  status                TEXT NOT NULL,        -- pending_payment, processing, paid, failed, canceled, refunded
  currency              TEXT NOT NULL DEFAULT 'usd',

  subtotal_cents        INTEGER NOT NULL DEFAULT 0 CHECK (subtotal_cents >= 0),
  tax_cents             INTEGER NOT NULL DEFAULT 0 CHECK (tax_cents >= 0),
  shipping_cents        INTEGER NOT NULL DEFAULT 0 CHECK (shipping_cents >= 0),
  total_cents           INTEGER NOT NULL DEFAULT 0 CHECK (total_cents >= 0),

  customer_email        TEXT NULL,
  customer_name         TEXT NULL,

  stripe_payment_intent_id    TEXT NULL,
  stripe_client_secret_last4  TEXT NULL, -- optional: last 4 chars to help debug safely
  paid_at_utc                 TEXT NULL,

  created_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),
  updated_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now'))
);

-- Line items (snapshot prices at order time)
CREATE TABLE IF NOT EXISTS order_items (
  order_item_id         INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id              INTEGER NOT NULL,
  product_id            INTEGER NOT NULL,
  sku                   TEXT NOT NULL,
  name                  TEXT NOT NULL,
  quantity              INTEGER NOT NULL CHECK (quantity > 0),
  unit_price_cents      INTEGER NOT NULL CHECK (unit_price_cents >= 0),
  line_total_cents      INTEGER NOT NULL CHECK (line_total_cents >= 0),
  created_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),

  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);

-- Ledger/audit trail: money events + state transitions
CREATE TABLE IF NOT EXISTS ledger_entries (
  ledger_entry_id       INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id              INTEGER NOT NULL,
  event_type            TEXT NOT NULL,  -- order_created, totals_calculated, payment_intent_created, webhook_received, order_paid, etc.
  event_source          TEXT NOT NULL,  -- app, stripe_webhook, admin
  message               TEXT NOT NULL DEFAULT '',
  amount_delta_cents    INTEGER NULL,   -- optional for money movements
  data_json             TEXT NULL,      -- optional: store small JSON payloads (sanitized)
  created_at_utc        TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),

  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ledger_order_id ON ledger_entries(order_id);
CREATE INDEX IF NOT EXISTS idx_ledger_created_at ON ledger_entries(created_at_utc);

-- Webhook idempotency + raw payload storage
CREATE TABLE IF NOT EXISTS stripe_webhook_events (
  stripe_webhook_event_id  INTEGER PRIMARY KEY AUTOINCREMENT,
  stripe_event_id          TEXT NOT NULL UNIQUE,
  event_type               TEXT NOT NULL,
  livemode                 INTEGER NOT NULL DEFAULT 0 CHECK (livemode IN (0,1)),
  payload_json             TEXT NOT NULL,
  received_at_utc          TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),
  processed_at_utc         TEXT NULL,
  processing_error         TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_stripe_webhook_received_at ON stripe_webhook_events(received_at_utc);

-- Demo users for admin login (mirrors Demo 1 pattern)
CREATE TABLE IF NOT EXISTS users (
  user_id         INTEGER PRIMARY KEY AUTOINCREMENT,
  email           TEXT NOT NULL UNIQUE,
  display_name    TEXT NOT NULL DEFAULT '',
  role            TEXT NOT NULL DEFAULT 'admin',
  password_hash   TEXT NOT NULL,
  is_active       INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0,1)),
  created_at_utc  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now')),
  updated_at_utc  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ','now'))
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
