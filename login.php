<?php
if (!isset($_SESSION)) {
	session_start();
}
if (isset($_GET['logout']) && $_GET['logout']) {
	session_destroy();
	unset($_SESSION);
	echo "<script language='JavaScript'>location='./login.php'</script>"; 
	exit;
}
include_once('/usr/local/lib/jyctel/tools/mtg/clases/Login.php');
include_once( './config.php');

if (isset($_POST)) {
	includeBridgePs();
	
	$login = new Login();
	foreach ($_POST as $name => $value) {
		if ($name == 'inputUser') {
			$login->setUser($value);
		}
		if ($name == 'inputPassword') {
			$login->setPassword($value);
		}
	}
	if (!$login->is_empty()) {

		$res = [];
		$ps = new PsBridge();
		$customer = new Customer();
		$user = $customer->getByEmail($login->user);
		if (!$user) {
			$sql = sprintf("select email, id_customer from cbn_mtg.mtg_resellers_panel where email = '%s' and password = '%s'", $login->user, md5($login->password));
			$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
			if (!empty($result)) {
				$customer = new Customer($result[0]['id_customer']);
				$user = $customer->getByEmail($customer->email);
			}
		}
		$isReseller = ($user !== false ? $user->isReseller() : false);
		
		if ($isReseller) {
			$_SESSION['reseller']['user'] = $login->user;
			$_SESSION['reseller']['id'] = $user->id;
		}
		if (!empty($isReseller)) {
			session_start();
	 		echo "<script language='JavaScript'>location='./index.php'</script>"; exit;	
		} 
	}
}

?>
<! DOCTYPE html>  
<html lang="en" >  
<head>  
  <meta charset="UTF-8">  
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resellers cbn - Ordenes - LOGIN </title>  
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">  
</head>  
<style>  
html {   
    height: 100%;   
}  
body {   
    height: 100%;   
}  
.global-container {  
    height: 100%;  
    display: flex;  
    align-items: center;  
    justify-content: center;  
    background-color: #f5f5f5;  
}  
form {  
    padding-top: 10px;  
    font-size: 14px;  
    margin-top: 30px;  
}  
.card-title {   
font-weight: 300;  
 }  
.btn {  
    font-size: 14px;  
    margin-top: 20px;  
}  
.login-form {   
    width: 330px;  
    margin: 20px;  
}  
.sign-up {  
    text-align: center;  
    padding: 20px 0 0;  
}  
.alert {  
    margin-bottom: -30px;  
    font-size: 13px;  
    margin-top: 20px;  
}  
</style>  
<body>  
<div class="">  
  <div class="global-container">  
    <div class="card login-form">  
    <div class="card-body">  
        <h3 class="card-title text-center"> Panel CBN </h3>  
        <div class="card-text">  
            <form method="POST" action="">  
                <div class="form-group">  
                    <label for="inputUser"> Usuario </label>  
                    <input type="text" class="form-control form-control-sm" id="inputUser" name="inputUser" required>  
                </div>  
                <div class="form-group">  
                    <label for="inputPassword"> Password </label>  
                    <input type="password" class="form-control form-control-sm" id="inputPassword" name="inputPassword" required>  
                </div>  
                <button type="submit" class="btn btn-primary btn-block"> Sign in </button>  
                  
            </form>  
        </div>  
    </div>  
</div>  
</div>  
</body>  
</html> 
