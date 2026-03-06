<h1 class="page-title">Order Details</h1>
<p class="page-subtitle">Admin view: order totals, Stripe identifiers, ledger timeline, and a one-click demo refund (test mode).</p>

<div class="card" style="max-width:980px;margin:0 auto;">
  <div class="card-body">
    <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start;">
      <div>
        <div style="color:var(--muted);font-weight:800;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Order Ref</div>
        <div style="font-size:22px;font-weight:900;margin-top:4px;">
          <?= htmlspecialchars($order['order_public_id']) ?>
        </div>
        <div style="margin-top:10px;color:var(--muted);">
          <strong>Created:</strong> <?= htmlspecialchars($order['created_at_utc']) ?>
        </div>
      </div>

      <div style="text-align:right;">
        <div style="color:var(--muted);font-weight:800;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Status</div>
        <div style="font-size:18px;font-weight:900;margin-top:4px;">
          <?= htmlspecialchars($order['status']) ?>
        </div>

        <?php if (!empty($order['stripe_payment_intent_id'])): ?>
          <div style="margin-top:10px;color:var(--muted);">
            <strong>Stripe PaymentIntent:</strong><br>
            <span style="font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
              <?= htmlspecialchars($order['stripe_payment_intent_id']) ?>
            </span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:14px;border-top:1px solid var(--line);padding-top:14px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
      <div>
        <div class="kpi">Subtotal: $<?= number_format(((int)$order['subtotal_cents'])/100, 2) ?></div>
        <div style="color:var(--muted);font-weight:700;">Tax: $<?= number_format(((int)$order['tax_cents'])/100, 2) ?></div>
        <div style="color:var(--muted);font-weight:700;">Shipping: $<?= number_format(((int)$order['shipping_cents'])/100, 2) ?></div>
      </div>

      <div style="text-align:right;">
        <div class="kpi" style="font-size:22px;">Total: $<?= number_format(((int)$order['total_cents'])/100, 2) ?></div>

        <div style="margin-top:10px;display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
          <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/admin/orders">Back to Orders</a>

          <form method="post" action="<?= htmlspecialchars($basePath) ?>/logout" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <button class="btn btn-danger" type="submit">Logout</button>
          </form>
        </div>

        <?php if ($order['status'] === 'paid' && !empty($order['stripe_payment_intent_id'])): ?>
          <form method="post" action="<?= htmlspecialchars($basePath) ?>/admin/orders/refund" onsubmit="return confirm('Refund this order (demo)?');" style="margin-top:12px;display:flex;justify-content:flex-end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($order['order_public_id']) ?>">
            <button class="btn btn-danger" type="submit">Refund (Demo)</button>
          </form>
        <?php elseif ($order['status'] === 'refunding'): ?>
          <div style="margin-top:12px;color:var(--muted);text-align:right;">
            <em>Refund requested — awaiting webhook confirmation.</em>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div style="max-width:980px;margin:18px auto 0;">
  <div class="card">
    <div class="card-body" style="padding:0;">
      <div style="padding:14px 16px;border-bottom:1px solid var(--line);font-weight:900;">Items</div>
      <table class="table">
        <thead>
          <tr>
            <th>Item</th>
            <th class="right">Unit</th>
            <th class="right">Qty</th>
            <th class="right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['name']) ?></td>
              <td class="right">$<?= number_format(((int)$it['unit_price_cents'])/100, 2) ?></td>
              <td class="right"><?= (int)$it['quantity'] ?></td>
              <td class="right">$<?= number_format(((int)$it['line_total_cents'])/100, 2) ?></td>
            </tr>
          <?php endforeach; ?>
		  <?php if ((int)$order['tax_cents'] > 0): ?>
		    <tr>
			  <td><em>Tax</em></td>
			  <td class="right">-</td>
			  <td class="right">-</td>
			  <td class="right">$<?= number_format(((int)$order['tax_cents'])/100, 2) ?></td>
		    </tr>
		  <?php endif; ?>

		  <?php if ((int)$order['shipping_cents'] > 0): ?>
		    <tr>
			  <td><em>Shipping</em></td>
			  <td class="right">-</td>
			  <td class="right">-</td>
			  <td class="right">$<?= number_format(((int)$order['shipping_cents'])/100, 2) ?></td>
		    </tr>
		  <?php endif; ?>
        </tbody>
      </table>
	</div>
  </div>

  <div class="card" style="margin-top:18px;">
    <div class="card-body" style="padding:0;">
      <div style="padding:14px 16px;border-bottom:1px solid var(--line);font-weight:900;">Ledger</div>

      <?php if (empty($ledger)): ?>
        <div style="padding:16px;color:var(--muted);">No ledger entries.</div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Time</th>
              <th>Type</th>
              <th>Source</th>
              <th>Message</th>
              <th class="right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ledger as $l): ?>
              <tr>
                <td><?= htmlspecialchars($l['created_at_utc']) ?></td>
                <td><?= htmlspecialchars($l['event_type']) ?></td>
                <td><?= htmlspecialchars($l['event_source']) ?></td>
                <td><?= htmlspecialchars($l['message']) ?></td>
                <td class="right">
                  <?php if ($l['amount_delta_cents'] !== null): ?>
                    $<?= number_format(((int)$l['amount_delta_cents'])/100, 2) ?>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>