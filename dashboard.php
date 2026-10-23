<?php
require_once __DIR__.'/includes/bootstrap.php';
$u=require_login();
$s=db()->prepare('SELECT o.*,s.name AS service_name FROM orders o JOIN services s ON s.id=o.service_id WHERE o.user_id=? ORDER BY o.id DESC LIMIT 100');$s->execute([$u['id']]);$orders=$s->fetchAll();
layout_start('Dashboard');
?><h1>My dashboard</h1><div class="grid"><div class="card"><div class="muted">Balance</div><div class="money">৳<?=number_format((float)$u['balance'],2)?></div><a class="btn" href="add-funds.php">Add funds</a></div><div class="card"><div class="muted">Orders</div><div class="money"><?=count($orders)?></div><a href="services.php">Place new order →</a></div></div>
<div class="card tablewrap"><h2>Recent orders</h2><table><thead><tr><th>ID</th><th>Service</th><th>Quantity</th><th>Charge</th><th>Status</th></tr></thead><tbody><?php foreach($orders as $o):?><tr><td><?=e($o['id'])?></td><td><?=e($o['service_name'])?></td><td><?=e($o['quantity'])?></td><td>৳<?=number_format((float)$o['charge'],2)?></td><td><?=e($o['status'])?></td></tr><?php endforeach;?><?php if(!$orders):?><tr><td colspan="5">No orders yet.</td></tr><?php endif;?></tbody></table></div><?php layout_end(); ?>
