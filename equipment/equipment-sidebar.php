<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
        <div class="logo-container">
                <img class="logo-image" src="../register/dumbbell.png" alt="dumbbell">
            </div>
            <h2>EliteFit<span>Gym</span></h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <!-- <i class="fas fa-bars"></i> -->
        </button>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture">
        </div>
        <div class="sidebar-user-info">
        <h3><?= htmlspecialchars($manager_data['first_name'] . ' ' . $manager_data['last_name']) ?></h3>
        
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="active">
                <a href="../../elitefit/welcome/welcome.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li >
                <a href="../../elitefit/equipment/add-equipment.php">
                    <i class="fas fa-home"></i>
                    <span>Add Equipment</span>
                </a>
            </li>
            <li >
                <a href="../../elitefit/equipment/schedule-maintenance.php">
                    <i class="fas fa-home"></i>
                    <span>Schedule Maintenance</span>
                </a>
            </li>
            <li >
                <a href="../../elitefit/equipment/equipment-inventory.php">
                    <i class="fas fa-home"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li>
                <a href="../settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>


</body>
</html>