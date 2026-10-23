<?php
require_once __DIR__.'/includes/bootstrap.php';
layout_start('Dashboard');
$u=current_user();
?>
<section class="card"><div class="muted">SOCIAL MEDIA MARKETING PANEL</div><h1>Grow your social presence.<br><span style="color:#f9a8d4">One simple panel.</span></h1><p class="muted">Browse services, place orders and track progress from one dashboard.</p><a class="btn" href="services.php">Explore services</a></section>
<div class="grid">
<div class="card"><h3>⚡ Service catalogue</h3><p class="muted">Import services from your configured provider API.</p><a href="services.php">View services →</a></div>
<div class="card"><h3>📦 Order tracking</h3><p class="muted">See your orders and their current status.</p><a href="dashboard.php">My dashboard →</a></div>
<div class="card"><h3>💳 bKash & Nagad</h3><p class="muted">Submit payment requests with transaction ID for admin review.</p><a href="add-funds.php">Add funds →</a></div>
</div>
<?php if($u):?><div class="card"><h3>Welcome, <?=e($u['name'])?></h3><div class="muted">Available balance</div><div class="money">৳<?=number_format((float)$u['balance'],2)?></div></div><?php endif;?>
<div class="notice">Payments are credited only after an administrator verifies the transaction in the official account. Never share your PIN or OTP.</div>
<?php layout_end(); ?>
