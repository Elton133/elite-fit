<?php
// EMAIL CONFIGURATION FILE
// File: login/config/email-config.php

define('EMAIL_METHOD', 'phpmailer');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'eltonmorden029@gmail.com'); 
define('SMTP_PASSWORD', 'kuvgzbgicognutho'); 
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'noreply@elitefit.com');
define('FROM_NAME', 'EliteFit Gym');
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);
?>