<?php
require_once __DIR__.'/includes/bootstrap.php';
$admin=require_admin();csrf_check();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=(string)($_POST['action']??'');
 try {
  if($action==='settings'){
   set_setting('provider_url',trim((string)($_POST['provider_url']??'')));
   $key=trim((string)($_POST['provider_key']??''));if($key!=='')set_setting('provider_key',$key);
   set_setting('markup_percent',(string)max(0,min(500,(float)($_POST['markup_percent']??20))));
   set_setting('bkash_number',trim((string)($_POST['bkash_number']??'')));
   set_setting('nagad_number',trim((string)($_POST['nagad_number']??'')));
   flash('Settings saved.','ok');
  } elseif($action==='import_services'){
   $items=provider_request(['action'=>'services']);$count=0;$markup=max(0,min(500,(float)setting('markup_percent','20')));
   $ins=db()->prepare('INSERT INTO services(provider_service_id,name,category,rate,min_qty,max_qty,description,active) VALUES(?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name),category=VALUES(category),rate=VALUES(rate),min_qty=VALUES(min_qty),max_qty=VALUES(max_qty),description=VALUES(description)');
   foreach($items as $item){if(!isset($item['service'],$item['name'],$item['rate']))continue;$rate=(float)$item['rate']*(1+$markup/100);$ins->execute([(string)$item['service'],substr((string)$item['name'],0,255),substr((string)($item['category']??'Imported'),0,120),$rate,max(1,(int)($item['min']??1)),max(1,(int)($item['max']??100000)),(string)($item['description']??'')]);$count++;}
   flash("Imported/updated {$count} services.",'ok');
  } elseif($action==='toggle_service') {
   $s=db()->prepare('UPDATE services SET active=IF(active=1,0,1) WHERE id=?');$s->execute([(int)$_POST['service_id']]);flash('Service status updated.','ok');
  } elseif($action==='review_payment') {
   $pid=(int)($_POST['payment_id']??0);$decision=(string)($_POST['decision']??'');$pdo=db();$pdo->beginTransaction();
   $s=$pdo->prepare('SELECT * FROM payment_requests WHERE id=? FOR UPDATE');$s->execute([$pid]);$p=$s->fetch();
   if(!$p||$p['status']!=='pending')throw new RuntimeException('Payment request already reviewed or not found.');
   if($decision==='approve'){$up=$pdo->prepare('UPDATE users SET balance=balance+? WHERE id=?');$up->execute([$p['amount'],$p['user_id']]);$st=$pdo->prepare("UPDATE payment_requests SET status='approved',reviewed_at=NOW() WHERE id=?");$st->execute([$pid]);}
   elseif($decision==='reject'){$st=$pdo->prepare("UPDATE payment_requests SET status='rejected',reviewed_at=NOW() WHERE id=?");$st->execute([$pid]);}
   else throw new RuntimeException('Invalid decision.');
   $pdo->commit();flash('Payment request reviewed.','ok');
  } elseif($action==='update_order') {
   $id=(int)($_POST['order_id']??0);$status=(string)($_POST['status']??'');
   if(!in_array($status,['Pending','Processing','Completed','Cancelled','Partial','In progress'],true))throw new RuntimeException('Invalid order status.');
   $s=db()->prepare('UPDATE orders SET status=? WHERE id=?');$s->execute([$status,$id]);flash('Order status updated.','ok');
  }
 } catch(Throwable $e){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();flash('Action failed: '.$e->getMessage(),'error');}
 header('Location: admin.php');exit;
}
$services=db()->query('SELECT id,provider_service_id,name,rate,active FROM services ORDER BY id DESC LIMIT 100')->fetchAll();
$payments=db()->query("SELECT p.*,u.email FROM payment_requests p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 100")->fetchAll();
$orders=db()->query('SELECT o.id,o.status,o.charge,o.quantity,u.email,s.name FROM orders o JOIN users u ON u.id=o.user_id JOIN services s ON s.id=o.service_id ORDER BY o.id DESC LIMIT 100')->fetchAll();
layout_start('Admin panel');
?><h1>Admin control panel</h1>
<div class="card"><h2>Provider & payment settings</h2><p class="muted">API key is stored in the server database. Do not paste it into GitHub or screenshots. Leave the key field blank to keep the existing key.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="settings"><label>Provider API URL</label><input name="provider_url" value="<?=e(setting('provider_url','https://my.smmgen.com/api/v2'))?>" required><label>Provider API key (blank = unchanged)</label><input type="password" name="provider_key" autocomplete="new-password"><label>Markup percentage</label><input type="number" name="markup_percent" min="0" max="500" step="0.1" value="<?=e(setting('markup_percent','20'))?>"><label>bKash receiving number</label><input name="bkash_number" value="<?=e(setting('bkash_number'))?>"><label>Nagad receiving number</label><input name="nagad_number" value="<?=e(setting('nagad_number'))?>"><button class="btn">Save settings</button></form>
<form method="post" style="margin-top:12px"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="import_services"><button class="btn">Import / sync provider services</button></form></div>
<div class="card tablewrap"><h2>Services (latest 100)</h2><table><thead><tr><th>ID</th><th>Provider ID</th><th>Name</th><th>Rate/1000</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($services as $s):?><tr><td><?=e($s['id'])?></td><td><?=e($s['provider_service_id'])?></td><td><?=e($s['name'])?></td><td>৳<?=number_format((float)$s['rate'],4)?></td><td><?=$s['active']?'Active':'Off'?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_service"><input type="hidden" name="service_id" value="<?=e($s['id'])?>"><button class="btn secondary">Toggle</button></form></td></tr><?php endforeach;?><?php if(!$services):?><tr><td colspan="6">No services imported yet.</td></tr><?php endif;?></tbody></table></div>
<div class="card tablewrap"><h2>Payment requests</h2><table><thead><tr><th>ID / User</th><th>Method</th><th>Amount</th><th>Sender / TxID</th><th>Status</th><th>Review</th></tr></thead><tbody><?php foreach($payments as $p):?><tr><td><?=e($p['id'])?><br><?=e($p['email'])?></td><td><?=e(strtoupper($p['method']))?></td><td>৳<?=number_format((float)$p['amount'],2)?></td><td><?=e($p['sender_number'])?><br><?=e($p['transaction_id'])?></td><td><?=e($p['status'])?></td><td><?php if($p['status']==='pending'):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="review_payment"><input type="hidden" name="payment_id" value="<?=e($p['id'])?>"><button class="btn" name="decision" value="approve">Approve</button> <button class="btn secondary" name="decision" value="reject">Reject</button></form><?php endif;?></td></tr><?php endforeach;?><?php if(!$payments):?><tr><td colspan="6">No payment requests.</td></tr><?php endif;?></tbody></table><p class="muted">Only approve after independently verifying the transaction in your official bKash/Nagad account.</p></div>
<div class="card tablewrap"><h2>Recent orders</h2><table><thead><tr><th>ID</th><th>User</th><th>Service</th><th>Qty / Charge</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach($orders as $o):?><tr><td><?=e($o['id'])?></td><td><?=e($o['email'])?></td><td><?=e($o['name'])?></td><td><?=e($o['quantity'])?> / ৳<?=number_format((float)$o['charge'],2)?></td><td><?=e($o['status'])?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update_order"><input type="hidden" name="order_id" value="<?=e($o['id'])?>"><select name="status"><option>Pending</option><option>Processing</option><option>In progress</option><option>Completed</option><option>Partial</option><option>Cancelled</option></select><button class="btn secondary">Save</button></form></td></tr><?php endforeach;?><?php if(!$orders):?><tr><td colspan="6">No orders.</td></tr><?php endif;?></tbody></table></div>
<?php layout_end(); ?>
