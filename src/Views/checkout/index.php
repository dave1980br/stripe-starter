<h1 class="page-title">Checkout</h1>
<p class="page-subtitle">Review your order totals before continuing to payment.</p>

<?php if (empty($items)): ?>
  <div class="notice">
    Your cart is empty.
    <div style="margin-top:10px;">
      <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/shop">Go to Shop</a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body" style="padding:0;">
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
              <td>
                <?php $skuLower = strtolower((string)$it['sku']); ?>
                <div class="cart-item">
                  <div class="cart-thumb">
                    <img src="<?= htmlspecialchars($basePath) ?>/assets/img/products/<?= htmlspecialchars($skuLower) ?>.png" alt="<?= htmlspecialchars($it['name']) ?>">
                  </div>
                  <div><?= htmlspecialchars($it['name']) ?></div>
                </div>
              </td>
              <td class="right">$<?= number_format($it['unit_price_cents']/100, 2) ?></td>
              <td class="right"><?= (int)$it['qty'] ?></td>
              <td class="right">$<?= number_format($it['line_total_cents']/100, 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card-footer">
      <div>
        <div class="kpi">Subtotal: $<?= number_format($subtotal_cents/100, 2) ?></div>
        <div class="kpi" style="color:var(--muted);font-weight:700;">Tax: $<?= number_format($tax_cents/100, 2) ?></div>
        <div class="kpi" style="color:var(--muted);font-weight:700;">Shipping: $<?= number_format($shipping_cents/100, 2) ?></div>
      </div>

      <div style="text-align:right;">
        <div class="kpi" style="font-size:20px;">Total: $<?= number_format($total_cents/100, 2) ?></div>
			<div style="margin-top:12px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
			  <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/cart">← Back to cart</a>

			  <form method="post" action="<?= htmlspecialchars($basePath) ?>/checkout/create" style="margin:0;">
				<button class="btn btn-primary" type="submit">Continue to Payment</button>
			  </form>
			</div>
      </div>
    </div>
  </div>
<?php endif; ?>