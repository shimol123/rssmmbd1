<?php
require_once __DIR__.'/includes/bootstrap.php';
$services=db()->query('SELECT id,name,category,rate,min_qty,max_qty,description FROM services WHERE active=1 ORDER BY category,name')->fetchAll();
layout_start('Services');
?><h1>Services</h1><p class="muted">Prices are shown in BDT per 1,000 units. Availability and provider rules may vary.</p>
<div class="card"><form method="get"><label>Search services</label><input name="q" placeholder="Search by service name" value="<?=e($_GET['q']??'')?>"><button class="btn secondary">Search</button></form></div>
<div class="card tablewrap"><table><thead><tr><th>Service</th><th>Rate / 1,000</th><th>Min</th><th>Max</th><th></th></tr></thead><tbody>
<?php $q=strtolower(trim((string)($_GET['q']??'')));$shown=0;foreach($services as $s):if($q!==''&&!str_contains(strtolower($s['name'].' '.$s['category']),$q))continue;$shown++;?><tr><td><strong><?=e($s['name'])?></strong><br><span class="muted small"><?=e($s['category'])?></span></td><td>৳<?=number_format((float)$s['rate'],4)?></td><td><?=e($s['min_qty'])?></td><td><?=e($s['max_qty'])?></td><td><a class="btn" href="order.php?service=<?=e($s['id'])?>">Order</a></td></tr><?php endforeach;?>
<?php if(!$shown):?><tr><td colspan="5">No services yet. Admin must configure provider API and import services.</td></tr><?php endif;?></tbody></table></div><?php layout_end(); ?>
