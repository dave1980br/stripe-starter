<h1 class="page-title">Demo Shop</h1>
<p class="page-subtitle">Demo products for testing purposes.</p>

<div class="notice">
  This demo showcases <strong>server-calculated totals</strong>, Stripe <strong>Payment Element</strong>, <strong>webhook finalization</strong>, and an <strong>Admin page that lists orders, with Order Details including an order ledger</strong>.
</div>

<?php if (empty($products)): ?>
  <p>No products found.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($products as $p): ?>
      <?php
        $skuLower = strtolower((string)$p['sku']);
        $imgUrl = htmlspecialchars($basePath) . "/assets/img/products/{$skuLower}.png";
      ?>
      <div class="card">
        <div class="product-img">
          <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        </div>
        <div class="card-body">
          <div class="product-title"><?= htmlspecialchars($p['name']) ?></div>
          <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
          <div class="price">$<?= number_format(((int)$p['unit_price_cents'])/100, 2) ?></div>

          <form method="post" action="<?= htmlspecialchars($basePath) ?>/cart/add" class="controls">
            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
            <input class="qty" type="number" name="qty" value="1" min="1" max="99">
            <button class="btn btn-primary" type="submit">Add to Cart</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>