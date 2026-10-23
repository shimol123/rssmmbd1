<?php
require_once __DIR__.'/includes/bootstrap.php';
$u=require_login();csrf_check();
$id=(int)($_GET['service']??$_POST['service_id']??0);$s=db()->prepare('SELECT * FROM services WHERE id=? AND active=1');$s->execute([$id]);$service=$s->fetch();
if(!$service){flash('Service not found or inactive.','error');header('Location: services.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 $link=trim((string)($_POST['link']??''));$qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT);$charge=$qty?round(((float)$service['rate']*$qty)/1000,2):0;
 if($link===''||strlen($link)>2000||!$qty||$qty<(int)$service['min_qty']||$qty>(int)$service['max_qty'])flash('Enter a valid target link and quantity within the service limits.','error');
 elseif($charge<=0)flash('Calculated charge is invalid.','error');
 else {try{$pdo=db();$pdo->beginTransaction();$lock=$pdo->prepare('SELECT balance FROM users WHERE id=? FOR UPDATE');$lock->execute([$u['id']]);$bal=(float)$lock->fetchColumn();if($bal<$charge)throw new RuntimeException('Insufficient balance. Add funds first.');
   // Provider order is submitted before local commit; check provider response carefully.
   $res=provider_request(['action'=>'add','service'=>$service['provider_service_id'],'link'=>$link,'quantity'=>$qty]);
   if(empty($res['order']))throw new RuntimeException('Provider did not return an order ID.');
   $up=$pdo->prepare('UPDATE users SET balance=balance-? WHERE id=?');$up->execute([$charge,$u['id']]);
   $ins=$pdo->prepare('INSERT INTO orders(user_id,service_id,provider_order_id,link,quantity,charge,status) VALUES(?,?,?,?,?,?,?)');$ins->execute([$u['id'],$service['id'],(string)$res['order'],$link,$qty,$charge,'Pending']);
   $pdo->commit();flash('Order submitted successfully.','ok');header('Location: dashboard.php');exit;
  }catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();flash($e->getMessage(),'error');}}
}
layout_start('Place order');
?><div class="card"><h1>Place order</h1><h3><?=e($service['name'])?></h3><p class="muted">Rate: ৳<?=number_format((float)$service['rate'],4)?> per 1,000 · Minimum <?=e($service['min_qty'])?> · Maximum <?=e($service['max_qty'])?></p>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="service_id" value="<?=e($service['id'])?>"><label>Target link</label><input type="url" name="link" required maxlength="2000" placeholder="https://..."><label>Quantity</label><input type="number" name="quantity" required min="<?=e($service['min_qty'])?>" max="<?=e($service['max_qty'])?>"><button class="btn">Submit order</button></form></div><?php layout_end(); ?>
