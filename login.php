<?php
require_once __DIR__.'/includes/bootstrap.php';
if(current_user()){header('Location: dashboard.php');exit;}
csrf_check();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $email=strtolower(trim((string)($_POST['email']??'')));$pass=(string)($_POST['password']??'');
 $s=db()->prepare('SELECT id,password_hash FROM users WHERE email=?');$s->execute([$email]);$row=$s->fetch();
 if($row&&password_verify($pass,$row['password_hash'])){session_regenerate_id(true);$_SESSION['user_id']=(int)$row['id'];header('Location: dashboard.php');exit;}
 flash('Invalid email or password.','error');
}
layout_start('Login');
?><div class="card"><h1>Login</h1><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required><button class="btn">Login</button></form><p class="muted">New here? <a href="register.php">Create an account</a></p></div><?php layout_end(); ?>
