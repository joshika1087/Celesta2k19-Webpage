<?php
	include("utility.php");	//Mail function and qr code function
	//Declaring variables
	$first_name='';
	$last_name='';
	$phone='';
	$college='';
	$email='';
	$password='';
	$confirm_password='';
/*******************Useful Functions*****************/

//Cleans the string from unwanted html symbols
function clean($string){
	return htmlentities($string);
}

//Redirect to a particular page after task is done
function redirect($location){
	return header("Location: {$location}");
}

//Function to store message
function set_message($message){
	if(!empty($message)){
		$_SESSION['message']=$message;
	}
	else{
		$message="";
	}
}

//DISPLAY MESSAGE
function display_message(){
	if(isset($_SESSION['message'])){
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}
}

//Token generator
function token_generator(){
	$token=$_SESSION['token'] =md5(uniqid(mt_rand(),true));
	return $token;
}

//Function to display validation error
function validation_errors($error_message){
$error = <<<DELIMITER
<div class="alert alert-warning alert-dismissible" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
			</button><strong>Warning!</strong> $$error_message
			</div>
DELIMITER;
return $error;			
}

//To check if the given email address already exists or not
function email_exists($email){
	$sql="SELECT id FROM users WHERE email='$email'";
	$result=query($sql);
	if(row_count($result)==1){
		return true;
	}
	else{
		return false;
	}
}

//Attaching the qr code generator
function generateQRCode($celestaid,$first_name,$last_name){
	include("qrCodeGenerator/qrlib.php");
	QRcode::png($celestaid."/".$first_name."/".$last_name,"assets/qrcodes/".$celestaid.".png","H","10","10");
}

/*************************Validating Functions**************************/

 function validate_user_registration(){

 	//Declaring the variables
	$first_name="";
	$last_name="";
	$phone='';
	$college='';
	$email='';
	$password='';
	$confirm_password='';

	$errors=[];
	$min=3;
	$max=20;

 	if($_SERVER['REQUEST_METHOD']=='POST'){
 		$first_name=clean($_POST['first_name']);
 		$last_name=clean($_POST['last_name']);
 		$phone=clean($_POST['phone']);
 		$college=clean($_POST['college']);
 		$email=clean($_POST['email']);
 		$password=clean($_POST['password']);
 		$confirm_password=clean($_POST['confirm_password']);
 		$gender=$_POST['gender'];
 	

	 	if(strlen($first_name)<$min){
	 		$errors[]="Your first name cannot be less than {$min}";
	 	}

	 	 if(strlen($last_name)<$min){
	 		$errors[]="Your last name cannot be less than {$min}";
	 	}

	 	if(strlen($phone)<10){
	 		$errors[]="Your phone number cannot be less than 10 digits.";
	 	}

	 	if(strlen($last_name)>$max){
	 		$errors[]="Your last name cannot be more than {$max}";
	 	}

	 	if(strlen($first_name)>$max){
	 		$errors[]="Your first name cannot be more than {$max}";
	 	}

	 	if(strlen($phone)>$max){
	 		$errors[]="Your phone number cannot have more than 10 digits.";
	 	}

	 	if(strlen($email)<$min){
	 		$errors[]="Your email cannot be less than {$min}";
	 	}

	 	if($password!==$confirm_password){
	 		$errors[]="Your password fields donot match";
	 	}

	 	if(email_exists($email)){
	 		$errors[]="Email already taken";
	 	}


	 	if(!empty($errors)){
	 		foreach($errors as $error){
	 			echo validation_errors($error);	
	 		}
	 		return json_encode(array_merge(array("201"),$errors));
	 	}else{
	 		if(register_user($first_name,$last_name,$phone,$college,$email,$password,$gender)){

	 			redirect("index.php");
	 			return json_encode("200");//Registration success
	 		}
	 		else{
	 			set_message("<p class='bg-danger text-center'>Sorry we couldn't register the user.</p>");
	 			echo "User registration failed";
	 			return json_encode("201");	//Registration failed
	 		}
	 		
	 	}
 	}
}



function register_user($first_name,$last_name,$phone,$college,$email,$password,$gender){

	$first_name=escape($first_name);
	$last_name=escape($last_name);
	$phone=escape($phone);
	$college=escape($college);
	$email=escape($email);
	$password=escape($password);

	if(email_exists($email)==true){
		return false;
	}else{
		$password=md5($password);
		$celestaid=getCelestaId();
		$validation_code=md5($celestaid+microtime());
		generateQRCode($celestaid,$first_name,$last_name);
		$qrcode="http://192.168.0.100:8888/login/assets/qrcodes/".$celestaid.".png";
		echo"<img src='assets/qrcodes/".$celestaid.".png'/>";

		//CONTENTS OF EMAIL
		$subject="Activate Celesta Account";
		$msg="<p>
			Your Celesta Id is ".$celestaid.". <br/>
			You qr code is <img src='$qrcode'/> <a href='$qrcode'>click here</a><br/>
		Please click the link below to activate your Account and login.<br/>
			http://localhost:8888/login/activate.php?email=$email&code=$validation_code
			</p>
		";
		$header="From: noreply@yourwebsite.com";
		//Added to database if mail is sent successfully
		if(send_email($email,$subject,$msg,$header)){
			$sql="INSERT INTO users(first_name,last_name,phone,college,email,password,validation_code,active,celestaid,qrcode,gender) ";
			$sql.=" VALUES('$first_name','$last_name','$phone','$college','$email','$password','$validation_code','0','$celestaid','".$qrcode."','$gender')";
			$result=query($sql);
			confirm($result);

			set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link.<br><br><br>Your Celesta id is $celestaid<br><br> <img src='$qrcode' alt='QR Code cannot be displayed.'/> </p>");
			return true;
		}else{
			return false;
		}
		
	}
}

//Activate User functions
function activate_user(){
	if($_SERVER['REQUEST_METHOD']=="GET"){
		if (isset($_GET['email'])) {
			echo $email=clean($_GET['email']);
			echo $validation_code=clean($_GET['code']);

			$sql="SELECT id FROM users WHERE email='".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."' ";
			$result=query($sql);
			confirm($result);

			if(row_count($result)==1){
				$sql2="UPDATE users SET active = 1, validation_code = 0 WHERE email='".escape($email)."' AND validation_code='".escape($validation_code)."' ";
				$result2=query($sql2);
				confirm($result2);
				set_message("<p class='bg-success'> Your account has been activated.</p>");
				redirect("login.php");
				return json_encode("400");//Siuccess
			}
			else{
				set_message("<p class='bg-danger'> Your account could not be activated.</p>");
				return json_encode("404");//Failed
			}
			
		}

	}
}

//Validate user Login
function validate_user_login(){
	$errors=[];
	if($_SERVER['REQUEST_METHOD']=="POST"){
		$celestaid=clean($_POST['celestaid']);
		$password=clean($_POST['password']);
		$remember=isset($_POST['remember']);

		//Listing down possible errors
		if(empty($celestaid)){
			$errors[]="Celesta ID field cannot be ampty.";
		}

		if(empty($password)){
			$errors[]="Password field cannot be empty.";
		}

		//Error printing or performing further operations
		if(!empty($errors)){
			foreach ($errors as $error) {
				echo validation_errors($error);
			}
			return json_encode(array_merge(array("404"),$errors));
		}else{
			if(login_user($celestaid,$password,$remember)){
				redirect("profile.php");
				return json_encode(array("400"));//User logged in
			}else{
				//echo "Inside credential wrong";
				echo validation_errors("Your credentials are not correct");
				return json_encode("404");//User login failed
			}

		}

	}
}

//Log in the user
function login_user($celestaid, $password, $remember){

	$sql = "SELECT password, id, qrcode FROM users WHERE celestaid ='".escape($celestaid)."' AND active=1";

	$result=query($sql);
	if(row_count($result)==1){

		$row=fetch_array($result);
		$db_password=$row['password'];
		$qrcode=$row['qrcode'];

		echo $db_password;
		echo md5($password);
		if(md5($password)==$db_password){
			$_SESSION['celestaid']=$celestaid;	//Storing the cdlesta id in a session
			$_SESSION['qrcode']=$qrcode;
			if($remember=="on"){
				 setcookie('celestaid',$celestaid, time() + 86400);
				 setcookie('qrcode',$qrcode,time()+86400);
			}
			return true;
		}else{
			return false;
		}

		return true;
	}
	else{
		return false;
	}

}

//Logged in functions
function logged_in(){
	if(isset($_SESSION['celestaid']) || isset($_COOKIE['celestaid'])){
		return true;
	}
	else{
		return false;
	}
}

// Recover password

function recover_password(){
	if($_SERVER['REQUEST_METHOD']=="POST"){
		if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){
			$email=clean($_POST['email']);
			if(email_exists($email)){

				$sql="SELECT celestaid FROM users WHERE email='".$email."' ";
				$result=query($sql);
				
				confirm($result);
				$row=fetch_array($result);
				$celestaid=$row['celestaid'];
				
				$validation_code=md5($email+$celestaid+microtime());
				setcookie('temp_access_code',$validation_code,time()+ 600 );

				$sql1="UPDATE users SET validation_code='".$validation_code."' WHERE email='".escape($email)."' ";
				$result1= query($sql1);
				confirm($result1); 

				$subject = "Please reset your Celesta ID password.";
				$message = "<p>Your celesta id is: {$celestaid}.<br/>
					Your password reset code is {$validation_code} <br/>
					Click here to reset your password http://localhost:8888/login/code.php?email=$email&code=$validation_code </p>";
				$header="From: noreply@yourwebsite.com";
				if (send_email($email,$subject,$message,$header)){
					echo "Email sent";
					set_message("<p class='bg-success text-center'>Please check your email or spam folder for password resetting link.</p>");
					redirect("index.php");
				}else{
					echo validation_errors("Email could not be sent. Please try after sometime.");
				}
			}else{
				echo validation_errors("This email doesnot exist");
			}

			//echo "It works";
		}else{
			redirect("index.php");
		}
		
	}
}

/*******Handling Password resetting **************/
function validate_code(){
	if(isset($_COOKIE['temp_access_code'])){

		if($_SERVER['REQUEST_METHOD']=="GET"){
			if(!isset($_GET['email']) && !isset($_GET['code'])){
				redirect("index.php");
			}else if(empty($_GET['email']) || empty($_GET['code'])){
				redirect("index.php");
			}else{
				if(isset($_GET['code'])){
					$email=clean($_GET['email']);
					$validation_code=clean($_GET['code']);
					
					$sql2="SELECT id FROM users WHERE validation_code='".$validation_code."' AND email='".$email."' ";
					$result2=query($sql2); 
					print_r($result2);
					confirm($result2);
					if(row_count($result2) == 1){
						redirect("reset.php?email=$email&code=$validation_code");
					}else{
						echo validation_errors("Sorry incorrect validation code. You might have clicked on wrong link. Try by typing the code manually or click on Forgot Password Again to get the link.");
					}
			}
			
		}
	}

	//Manually entering the code
	if($_SERVER['REQUEST_METHOD']=="POST"){
		if(isset($_POST['code'])){
			$validation_code= clean($_POST['code']);
			$email =clean($_GET['email']);

			$sql="SELECT id FROM users WHERE validation_code='".$validation_code."' AND email='".$email."' ";
			$result=query($sql); 

			if(row_count($result) == 1){
				redirect("reset.php?email=$email&code=$validation_code");
			}else{
				echo validation_errors("Sorry incorrect validation code for given email id.");
			}		

		}
	}
	}else{
		set_message("<p class='bg-danger text-center'>Sorry your validation cookie has expired.</p>");
		redirect("recover.php");
	}
}

//Resetting the password
function reset_password(){

	if(isset($_COOKIE['temp_access_code'])){
		if($_SERVER['REQUEST_METHOD']=="GET"){
			if(!isset($_GET['email']) && !isset($_GET['code'])){
				redirect("index.php");
			}else if(empty($_GET['email']) || empty($_GET['code'])){
				redirect("index.php");
			}else{
				if(isset($_GET['code'])){
					$email=clean($_GET['email']);
					$validation_code=clean($_GET['code']);
					$sql2="SELECT id FROM users WHERE validation_code='".escape($validation_code)."' AND email='".escape($email)."' ";
					$result2=query($sql2); 

					if(row_count($result2) == 1){
						setcookie("temp_password_reset",1,time()+180);
					}else{
						unset($_COOKIE['temp_password_reset']);
						setcookie("temp_password_reset",'',time()-180);
						redirect("index.php");
					}
				}
		
			}
		}else if($_SERVER['REQUEST_METHOD']=="POST"){
			if($_COOKIE['temp_password_reset']==1){
				
				if(isset($_POST['password']) && isset($_POST['confirm_password'])){
					$password=clean($_POST['password']);
					$email=clean($_GET['email']);
					$confirm_password=clean($_POST['confirm_password']);
					if($password!=$confirm_password){
						echo validation_errors("Password and confirm password did not match.");
					}else{
						$password=md5($password);
						$sql="SELECT id FROM users WHERE email='".escape($email)."' ";
						$result=query($sql);

						if(row_count($result)==1){
							$sql1="UPDATE users SET password='".$password."' WHERE email='".escape($email)."' ";
							$result1=query($sql1);

							//Updating in present database also, if the email exists
							$sql2="SELECT id FROM present_users WHERE email='".escape($email)."' ";
							$result2=query($sql2);
							if(row_count($result2)==1){
								$sql3="UPDATE present_users SET password='".$password."' WHERE email='".escape($email)."' ";
								$result3=query($sql3);
							}
							
							set_message("<p class='bg-success text-center'> Your apassword has been resetted.</p>");
							redirect("login.php");
						}else{
							echo validation_errors("Failed to reset password. Try again later.");
						}

						
					}
				}
			}else{
				echo "Password failed to reset. Try again later";
				redirect("recover.php");
			}
			unset($_COOKIE['temp_password_reset']);
			setcookie("temp_password_reset",'',time()-180);
		}
	}
}

