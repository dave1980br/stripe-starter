<h1>Cart</h1>

<?php if (empty($items)): ?>
  <p>Your cart is empty.</p>
  <p><a href="<?= htmlspecialchars($basePath) ?>/shop">Go shop</a></p>
<?php else: ?>
  <table cellpadding="8" cellspacing="0" border="0" style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="border-bottom:1px solid #ddd;">
        <th align="left">Item</th>
        <th align="right">Unit</th>
        <th align="right">Qty</th>
        <th align="right">Total</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr style="border-bottom:1px solid #eee;">
		  <td>
		    <?php $skuLower = strtolower((string)$it['sku']); ?>
		    <div class="cart-item">
			  <div class="cart-thumb">
			    <img src="<?= htmlspecialchars($basePath) ?>/assets/img/products/<?= htmlspecialchars($skuLower) ?>.png" alt="<?= htmlspecialchars($it['name']) ?>">
			  </div>
			  <div><?= htmlspecialchars($it['name']) ?></div>
		    </div>
		  </td>
          <td align="right">$<?= number_format($it['unit_price_cents']/100, 2) ?></td>
          <td align="right"><?= (int)$it['qty'] ?></td>
          <td align="right">$<?= number_format($it['line_total_cents']/100, 2) ?></td>
          <td align="right">
            <form method="post" action="<?= htmlspecialchars($basePath) ?>/cart/remove">
              <input type="hidden" name="product_id" value="<?= (int)$it['product_id'] ?>">
              <button class="btn btn-danger" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top:12px;text-align:right;">
    <div><strong>Subtotal:</strong> $<?= number_format($subtotal_cents/100, 2) ?></div>
	<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;">
	  <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/shop">Continue Shopping</a>
	  <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/checkout">Proceed to Checkout</a>
	</div>
  </div>
<?php endif; ?>