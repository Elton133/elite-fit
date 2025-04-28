<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EliteFit - See You Next Workout!</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #ff3c00;
      --secondary: #ff9d00;
      --dark: #1a1a1a;
      --light: #ffffff;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Fredoka', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--dark);
      color: var(--light);
      overflow: hidden;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      perspective: 1000px;
    }
    
    .background {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      background: radial-gradient(circle at center, #300b4c, #000000);
    }
    
    /* Animated background elements */
    .gym-equipment {
      position: absolute;
      opacity: 0;
      filter: blur(1px);
      animation: float 15s infinite linear;
    }
    
    @keyframes float {
      0% {
        transform: translateY(100vh) rotate(0deg) scale(0.5);
        opacity: 0;
      }
      10% {
        opacity: 0.7;
      }
      90% {
        opacity: 0.7;
      }
      100% {
        transform: translateY(-100vh) rotate(360deg) scale(1.5);
        opacity: 0;
      }
    }
    
    /* Staggered equipment animations */
    .gym-equipment:nth-child(1) {
      left: 10%;
      animation-delay: 0s;
      animation-duration: 12s;
    }
    
    .gym-equipment:nth-child(2) {
      left: 30%;
      animation-delay: 2s;
      animation-duration: 15s;
    }
    
    .gym-equipment:nth-child(3) {
      left: 50%;
      animation-delay: 4s;
      animation-duration: 18s;
    }
    
    .gym-equipment:nth-child(4) {
      left: 70%;
      animation-delay: 6s;
      animation-duration: 14s;
    }
    
    .gym-equipment:nth-child(5) {
      left: 90%;
      animation-delay: 8s;
      animation-duration: 16s;
    }
    
    /* Main content */
    .container {
      position: relative;
      z-index: 10;
      text-align: center;
      animation: container-entrance 1.2s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards;
      transform: scale(0.5) rotateX(90deg);
      opacity: 0;
    }
    
    @keyframes container-entrance {
      0% {
        transform: scale(0.5) rotateX(90deg);
        opacity: 0;
      }
      70% {
        transform: scale(1.1) rotateX(0deg);
        opacity: 1;
      }
      100% {
        transform: scale(1) rotateX(0deg);
        opacity: 1;
      }
    }
    
    .logout-card {
      background: rgba(0, 0, 0, 0.8);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 0 30px rgba(255, 60, 0, 0.5);
      max-width: 400px;
      height: 500px;
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(10px);
      border: 2px solid rgba(255, 60, 0, 0.3);
      transform-style: preserve-3d;
    }
    
    /* Glow effect */
    .logout-card::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(
        45deg,
        transparent,
        rgba(255, 60, 0, 0.1),
        transparent
      );
      transform: rotate(45deg);
      animation: glowing 3s linear infinite;
    }
    
    @keyframes glowing {
      0% {
        transform: rotate(45deg) translateX(-100%);
      }
      100% {
        transform: rotate(45deg) translateX(100%);
      }
    }
    
    .logo {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 50%;
      margin: 0 auto 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 50px;
      position: relative;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(255, 60, 0, 0.7);
        transform: scale(1);
      }
      70% {
        box-shadow: 0 0 0 20px rgba(255, 60, 0, 0);
        transform: scale(1.05);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(255, 60, 0, 0);
        transform: scale(1);
      }
    }
    
    .logo i {
      animation: bicep-curl 2s ease-in-out infinite;
      transform-origin: center;
    }
    
    @keyframes bicep-curl {
      0%, 100% {
        transform: rotate(0deg);
      }
      50% {
        transform: rotate(30deg);
      }
    }
    
    h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      color: var(--light);
      text-transform: uppercase;
      letter-spacing: 2px;
      position: relative;
      display: inline-block;
    }
    
    h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 0;
      height: 4px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      animation: line-grow 1.5s 0.5s forwards;
    }
    
    @keyframes line-grow {
      to {
        width: 100%;
      }
    }
    
    .message {
      font-size: 1.2rem;
      margin-bottom: 30px;
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.6;
    }
    
    .motivation {
      font-family: "Skyrate";
      font-size: 1.4rem;
      font-weight: bold;
      margin: 30px 0;
      color: var(--primary);
      text-shadow: 0 0 10px rgba(255, 60, 0, 0.5);
      opacity: 0;
      animation: fade-in 1s 1.5s forwards;
    }
    
    @keyframes fade-in {
      to {
        opacity: 1;
      }
    }
    
    .countdown {
      font-size: 2rem;
      font-weight: bold;
      margin: 20px 0;
      color: var(--light);
    }
    
    .progress-container {
      width: 100%;
      height: 10px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 5px;
      overflow: hidden;
      margin: 20px 0;
    }
    
    .progress-bar {
      height: 100%;
      width: 0;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      animation: progress 3s linear forwards;
    }
    
    @keyframes progress {
      to {
        width: 100%;
      }
    }
    
    /* Sweat drops animation */
    .sweat-container {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      pointer-events: none;
    }
    
    .sweat-drop {
      position: absolute;
      width: 10px;
      height: 10px;
      background: rgba(255, 255, 255, 0.7);
      border-radius: 50%;
      top: -10px;
      animation: sweat-fall 3s linear infinite;
    }
    
    @keyframes sweat-fall {
      0% {
        transform: translateY(0) scale(0);
        opacity: 1;
      }
      70% {
        opacity: 1;
      }
      100% {
        transform: translateY(100vh) scale(1);
        opacity: 0;
      }
    }
    
    /* Explosion effect for redirect */
    .explosion {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--primary);
      z-index: 100;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .logout-card {
        padding: 30px;
        margin: 0 20px;
      }
      
      h1 {
        font-size: 2rem;
      }
      
      .logo {
        width: 100px;
        height: 100px;
        font-size: 40px;
      }
    }
    
    /* Heartbeat animation for the final countdown */
    .heartbeat {
      animation: heartbeat 1.5s ease-in-out infinite;
    }
    
    @keyframes heartbeat {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
    }
  </style>
</head>
<body>
  <div class="background"></div>
  
  <!-- Floating gym equipment -->
  <div class="gym-equipment"><i class="fas fa-dumbbell" style="font-size: 4rem; color: rgba(255, 60, 0, 0.7);"></i></div>
  <div class="gym-equipment"><i class="fas fa-running" style="font-size: 4rem; color: rgba(255, 156, 0, 0.7);"></i></div>
  <div class="gym-equipment"><i class="fas fa-heartbeat" style="font-size: 4rem; color: rgba(255, 60, 0, 0.7);"></i></div>
  <div class="gym-equipment"><i class="fas fa-biking" style="font-size: 4rem; color: rgba(255, 156, 0, 0.7);"></i></div>
  <div class="gym-equipment"><i class="fas fa-stopwatch" style="font-size: 4rem; color: rgba(255, 60, 0, 0.7);"></i></div>
  
  <div class="container">
    <div class="logout-card">
      <div class="logo">
        <i class="fas fa-dumbbell"></i>
      </div>
      <h1>Workout Complete!</h1>
      <p class="message">You've successfully logged out from EliteFit. Your fitness journey continues outside the gym!</p>
      
      <div class="motivation">NO PAIN, NO GAIN! 💪</div>
      
      <div class="countdown">Redirecting in <span id="timer" class="heartbeat">3</span></div>
      
      <div class="progress-container">
        <div class="progress-bar"></div>
      </div>
      
      <!-- Sweat drops container -->
      <div class="sweat-container" id="sweatContainer"></div>
    </div>
  </div>
  
  <div class="explosion" id="explosion"></div>

  <script>
    // Create sweat drops
    const sweatContainer = document.getElementById('sweatContainer');
    for (let i = 0; i < 20; i++) {
      const drop = document.createElement('div');
      drop.className = 'sweat-drop';
      drop.style.left = `${Math.random() * 100}%`;
      drop.style.animationDelay = `${Math.random() * 3}s`;
      sweatContainer.appendChild(drop);
    }
    
    // Countdown timer
    const timerElement = document.getElementById('timer');
    let timeLeft = 3;
    
    const countdownInterval = setInterval(() => {
      timeLeft--;
      timerElement.textContent = timeLeft;
      
      if (timeLeft <= 0) {
        clearInterval(countdownInterval);
        
        // Explosion effect
        const explosion = document.getElementById('explosion');
        explosion.style.opacity = '1';
        
        // Add shake effect before redirect
        document.body.style.animation = 'shake 0.5s linear';
        
        setTimeout(() => {
          window.location.href = "/elitefit/login/index.php";
        }, 500);
      }
    }, 1000);
    
    // Random motivational quotes
    const motivationalQuotes = [
      "NO PAIN, NO GAIN! 💪",
      "THE ONLY BAD WORKOUT IS THE ONE THAT DIDN'T HAPPEN! 🔥",
      "SWEAT NOW, SHINE LATER! ✨",
      "PUSH YOURSELF BECAUSE NO ONE ELSE WILL! 💯",
      "YOUR BODY CAN STAND ALMOST ANYTHING. IT'S YOUR MIND YOU HAVE TO CONVINCE! 🧠"
    ];
    
    const motivationElement = document.querySelector('.motivation');
    let quoteIndex = 0;
    
    setInterval(() => {
      motivationElement.style.opacity = '0';
      
      setTimeout(() => {
        quoteIndex = (quoteIndex + 1) % motivationalQuotes.length;
        motivationElement.textContent = motivationalQuotes[quoteIndex];
        motivationElement.style.opacity = '1';
      }, 500);
    },1000);
    
    // Add 3D tilt effect on mouse move
    const card = document.querySelector('.logout-card');
    
    document.addEventListener('mousemove', (e) => {
      const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
      const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
      card.style.transform = `rotateY(${xAxis}deg) rotateX(${yAxis}deg)`;
    });
    
    // Reset transform when mouse leaves
    document.addEventListener('mouseleave', () => {
      card.style.transform = 'rotateY(0deg) rotateX(0deg)';
    });
    
    // Add shake animation
    document.head.insertAdjacentHTML('beforeend', `
      <style>
        @keyframes shake {
          0%, 100% { transform: translateX(0); }
          10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
          20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
      </style>
    `);
  </script>
</body>
</html>