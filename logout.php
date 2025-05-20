<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logging You Out...</title>
  <style>
    body {
      background: linear-gradient(to right, #00c6ff, #0072ff);
      color: white;
      font-family: 'Segoe UI', sans-serif;
      text-align: center;
      padding-top: 100px;
      animation: fadeOut 3s forwards;
    }

    .popup {
      background: rgba(0,0,0,0.3);
      border: 2px solid white;
      display: inline-block;
      padding: 30px;
      border-radius: 20px;
      animation: pop 0.8s ease-out;
    }

    h1 {
      margin-bottom: 20px;
    }

    @keyframes pop {
      0% { transform: scale(0.5); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }

    @keyframes fadeOut {
      0% { opacity: 1; }
      90% { opacity: 1; }
      100% { opacity: 0; }
    }
  </style>
</head>
<body>
  <div class="popup">
    <h1>👋 See you soon!</h1>
    <p>You’ve been logged out successfully.</p>
  </div>

  <script>
    // Redirect after 3 seconds
    setTimeout(() => {
      window.location.href = "/elitefit/login/index.php";
    }, 1000);
  </script>
</body>
</html>