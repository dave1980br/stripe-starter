<h1>Pay</h1>

<p>
  <strong>Order:</strong> <?= htmlspecialchars($order['order_public_id']) ?><br>
  <strong>Total:</strong> $<?= number_format(((int)$order['total_cents'])/100, 2) ?><br>
  <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?>
</p>

<div class="notice" style="max-width:720px;margin:0 0 14px;">
  <div style="font-weight:800;color:var(--text);margin-bottom:6px;">Test Payments (Stripe Sandbox)</div>
  <div style="margin-bottom:6px;">
    <strong>Successful payment:</strong> 4242 4242 4242 4242 (any future date, any CVC, any ZIP)
  </div>
  <div style="margin-bottom:6px;">
    <strong>Failed payment (insufficient funds):</strong> 4000 0000 0000 9995
  </div>
  <div style="margin-top:10px;">
    You can view this order in the <a href="<?= htmlspecialchars($basePath) ?>/admin">Admin portal</a> after payment.
  </div>
  <div style="margin-top:8px;">
    <strong>Admin login:</strong> admin@example.com / <strong>admin123</strong>
  </div>
</div>

<div id="payment-element" style="max-width:520px;"></div>

<button id="submit" class="btn btn-primary" style="margin-top:14px;">Pay</button>
<div id="error-message" style="margin-top:10px;color:#b00020;"></div>

<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
  const stripe = Stripe(<?= json_encode($publishableKey) ?>);
  const clientSecret = <?= json_encode($clientSecret) ?>;

  const elements = stripe.elements({ clientSecret });
  const paymentElement = elements.create("payment");
  paymentElement.mount("#payment-element");

  const btn = document.getElementById("submit");
  const err = document.getElementById("error-message");

  btn.addEventListener("click", async function() {
    btn.disabled = true;
    err.textContent = "";

    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: { return_url: <?= json_encode($returnUrl) ?> }
    });

    if (error) {
      err.textContent = error.message || "Payment failed.";
      btn.disabled = false;
    }
  });
})();
</script>
