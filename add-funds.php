<?php
require_once __DIR__.'/includes/bootstrap.php';
$u=require_login();csrf_check();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $method=(string)($_POST['method']??'');$amount=filter_var($_POST['amount']??null,FILTER_VALIDATE_FLOAT);$sender=trim((string)($_POST['sender_number']??''));$tx=trim((string)($_POST['transaction_id']??''));
 if(!in_array($method,['bkash','nagad'],true)||!$amount||$amount<10||$amount>100000||!preg_match('/^[0-9+ -]{8,20}$/',$sender)||!preg_match('/^[A-Za-z0-9-]{6,100}$/',$tx))flash('Check method, amount (৳10–৳100,000), sender number and transaction ID.','error');
 else {try{$s=db()->prepare('INSERT INTO payment_requests(user_id,method,amount,sender_number,transaction_id) VALUES(?,?,?,?,?)');$s->execute([$u['id'],$method,$amount,$sender,$tx]);flash('Payment request submitted. Balance will be added after admin verification.','ok');header('Location: dashboard.php');exit;}catch(PDOException $e){flash('Could not submit request. The transaction ID may already be used.','error');}}
}
layout_start('Add funds');
?><div class="card"><h1>Add funds</h1><p class="muted">Send money only to the official number shown below. Then submit your transaction ID. Requests are manually reviewed.</p>
<div class="grid"><div class="card"><h3>bKash</h3><div class="money"><?=e(setting('bkash_number','Not configured'))?></div></div><div class="card"><h3>Nagad</h3><div class="money"><?=e(setting('nagad_number','Not configured'))?></div></div></div>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Payment method</label><select name="method" required><option value="bkash">bKash</option><option value="nagad">Nagad</option></select><label>Amount (BDT)</label><input type="number" name="amount" min="10" max="100000" step="0.01" required><label>Your sender number</label><input name="sender_number" required><label>Transaction ID</label><input name="transaction_id" required><button class="btn">Submit payment request</button></form></div><?php layout_end(); ?>
