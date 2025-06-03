<?php
// EMAIL CONFIGURATION FILE
// File: login/config/email-config.php

define('EMAIL_METHOD', 'phpmailer');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'eltonmorden029@gmail.com'); 
define('SMTP_PASSWORD', 'qbmx havj kmwx wcug'); 
define('SMTP_ENCRYPTION', 'ssl');
define('FROM_EMAIL', 'noreply@elitefit.com');
define('FROM_NAME', 'EliteFit Gym');
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);
?>