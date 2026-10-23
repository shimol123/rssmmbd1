<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){header('Location: dashboard.php');exit;}
csrf_check();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));$pass=(string)($_POST['password']??'');
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($pass)<10){flash('Enter a name, valid email and password of at least 10 characters.','error');}
  else {try{$s=db()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');$s->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['user_id']=(int)db()->lastInsertId();flash('Account created.','ok');header('Location: dashboard.php');exit;}catch(PDOException $e){flash('Could not create account. This email may already be registered.','error');}}
}
layout_start('Register');
?><div class="card"><h1>Create account</h1><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Name</label><input name="name" required maxlength="100"><label>Email</label><input type="email" name="email" required><label>Password (10+ characters)</label><input type="password" name="password" minlength="10" required><button class="btn">Register</button></form><p class="muted">Already registered? <a href="login.php">Login</a></p></div><?php layout_end(); ?>
