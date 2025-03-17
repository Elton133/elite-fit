<?php
    include_once "../datacon.php";

    session_start();
    $temp_password = $_SESSION["temp_password"] ?? "";
unset($_SESSION["temp_password"]);
    //check if form was submitted
    if($_SERVER["REQUEST_METHOD"]=="POST"){
        //get form data
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password =$_POST['password'];

        if(empty($email) || empty($password)){
            header("Location: login.php?error=Please fill in all fields");
            exit();
        }
        //check if user exists
        $query = "SELECT * FROM user_login_details WHERE username = '$email'";
        $result = mysqli_query($conn, $query);

        if(mysqli_num_rows($result) == 1){
            $row = mysqli_fetch_assoc($result);
            $hashed_password = $row['user_password'];

            if(password_verify($password, $hashed_password)){
                session_start();

               $_SESSION['loggedin'] = true;
               $_SESSION['email'] = $email;
               $_SESSION['user_id'] = $row['user_id'];

                // Set remember me cookie if requested
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $token_hash = password_hash($token, PASSWORD_DEFAULT);
                $update_query = "UPDATE user_login_details SET remember_token = '$token_hash', token_expiry = FROM_UNIXTIME($expiry) WHERE username = '$email'";
                mysqli_query($conn, $update_query);
                
                // Set cookie
                setcookie("remember_me", $token, $expiry, "/", "", true, true);
            }
                header("Location: ../welcome/welcome.php");
                exit();
            }
            else{
                //password does not exist
                header("Location: login.php?error=Incorrect password");
                exit();
            }
        }else{
            //user does not exist
            header("Location: login.php?error=User does not exist");
            exit();
        }
            } else{
                //not a post request
                header("Location: login.php");
                exit();
            }
?>


    