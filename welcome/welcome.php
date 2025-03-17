<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to EliteFit Gym</title>
    <link rel="stylesheet" href="welcome-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background"></div>
    
    <div class="container">
        <header class="main-header">
            <div class="logo-container">
                <img src="../register/dumbbell.png" alt="EliteFit Logo" class="logo">
                <h1>EliteFit<span>Gym</span></h1>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="../register/uploads/1741609530_beaches.jpg" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <p class="user-name">John Doe</p>
                        <p class="user-status">Member</p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, John!</h2>
                <p>Ready to crush your fitness goals today?</p>
            </div>
            <div class="quick-actions">
                <button class="action-btn"><i class="fas fa-dumbbell"></i> Start Workout</button>
                <button class="action-btn secondary"><i class="fas fa-calendar-alt"></i> Book Class</button>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="dashboard-row">
                <div class="dashboard-card stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Your Fitness Stats</h3>
                    </div>
                    <div class="card-content">
                        <div class="stat-item">
                            <div class="stat-icon bmi-icon">
                                <i class="fas fa-weight"></i>
                            </div>
                            <div class="stat-info">
                                <h4>BMI</h4>
                                <p class="stat-value">22.5</p>
                                <p class="stat-label">Normal weight</p>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon weight-icon">
                                <i class="fas fa-weight-scale"></i>
                            </div>
                            <div class="stat-info">
                                <h4>Weight</h4>
                                <p class="stat-value">75 kg</p>
                                <p class="stat-label">Last updated: Today</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=1470&q=80")'
        ];
        
        let currentBg = 0;
        const bgElement = document.querySelector('.background');
        
        function changeBackground() {
            bgElement.style.backgroundImage = backgrounds[currentBg];
            currentBg = (currentBg + 1) % backgrounds.length;
        }
        
        changeBackground();
        setInterval(changeBackground, 8000);
    </script>
</body>
</html>
