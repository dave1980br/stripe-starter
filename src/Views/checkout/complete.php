<h1 class="page-title">Payment Submitted</h1>
<p class="page-subtitle">Stripe redirected back successfully. Final status is confirmed by the webhook.</p>

<div class="card" style="max-width:820px;margin:0 auto;">
  <div class="card-body">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
      <div>
        <div style="color:var(--muted);font-weight:800;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Order</div>
        <div style="font-size:22px;font-weight:900;margin-top:4px;">
          <?= htmlspecialchars($order['order_public_id']) ?>
        </div>
      </div>

      <div style="text-align:right;">
        <div style="color:var(--muted);font-weight:800;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Current Status</div>
        <div style="font-size:18px;font-weight:900;margin-top:4px;">
          <?= htmlspecialchars($order['status']) ?>
        </div>
      </div>
    </div>

    <div style="margin-top:14px;border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
      <div class="kpi" style="font-size:20px;">
        Total: $<?= number_format(((int)$order['total_cents'])/100, 2) ?>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/shop">Back to Shop</a>
        <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/admin">Open Admin Portal</a>
      </div>
    </div>

    <div class="notice" style="margin-top:14px;">
      If the status still shows <strong>pending_payment</strong>, wait a moment and refresh this page or the admin order view.<br>
      The webhook updates the final status to <strong>paid</strong> / <strong>failed</strong> / <strong>refunded</strong>.
    </div>
  </div>
</div>