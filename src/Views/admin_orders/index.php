<h1 class="page-title">Admin Orders</h1>
<p class="page-subtitle">View all demo orders and open the ledger for each transaction.</p>

<div style="max-width:980px;margin:0 auto 14px;display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;">
  <form method="post" action="<?= htmlspecialchars($basePath) ?>/logout" style="margin:0;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <button class="btn btn-danger" type="submit">Logout</button>
  </form>
</div>

<div class="card" style="max-width:980px;margin:0 auto;">
  <div class="card-body" style="padding:0;">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line);font-weight:900;">
      Recent Orders
    </div>

    <?php if (empty($orders)): ?>
      <div style="padding:16px;color:var(--muted);">No orders yet.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Created</th>
            <th>Order Ref</th>
            <th>Status</th>
            <th class="right">Total</th>
            <th class="right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['created_at_utc']) ?></td>
              <td style="font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                <?= htmlspecialchars($o['order_public_id']) ?>
              </td>
              <td><?= htmlspecialchars($o['status']) ?></td>
              <td class="right">$<?= number_format(((int)$o['total_cents'])/100, 2) ?></td>
              <td class="right">
                <a class="btn btn-primary" href="<?= htmlspecialchars($basePath) ?>/admin/orders/view?ref=<?= urlencode($o['order_public_id']) ?>">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>