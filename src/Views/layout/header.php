<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stripe + Ledger Demo</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/app.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="bar">
		<a class="brand" href="https://www.blueoaksoftware.com">
          <div class="brand-badge">BO</div>
          <div>Blue Oak Software</div>
        </a>
        <nav class="nav">
          <a href="<?= htmlspecialchars($basePath) ?>/shop">Shop</a>
          <a href="<?= htmlspecialchars($basePath) ?>/cart">Cart (<?= (int)$cartCount ?>)</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
    <div class="container">