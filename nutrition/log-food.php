<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get common foods for quick selection
$stmt = $conn->prepare("SELECT * FROM food_items ORDER BY name ASC");
$stmt->execute();
$food_items = $stmt->get_result();

// Process form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_date = $_POST['log_date'] ?? date('Y-m-d');
    $meal_time = $_POST['meal_time'] ?? '';
    $food_name = $_POST['food_name'] ?? '';
    $serving_size = $_POST['serving_size'] ?? '';
    $calories = $_POST['calories'] ?? 0;
    $protein = $_POST['protein'] ?? 0;
    $carbs = $_POST['carbs'] ?? 0;
    $fat = $_POST['fat'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    // Validate inputs
    if (empty($log_date) || empty($meal_time) || empty($food_name)) {
        $error = "Please fill in all required fields.";
    } else {
        // Insert food log entry
        $stmt = $conn->prepare("INSERT INTO food_logs (user_id, log_date, meal_time, food_name, serving_size, calories, protein, carbs, fat, notes) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdddds", $user_id, $log_date, $meal_time, $food_name, $serving_size, $calories, $protein, $carbs, $fat, $notes);
        
        if ($stmt->execute()) {
            // Set success message
            $_SESSION['toast_message'] = "Food logged successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: nutrition.php");
            exit();
        } else {
            $error = "Failed to log food. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Log Food - EliteFit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .log-food-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .log-food-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }
        
        .log-food-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .log-food-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .log-food-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: var(--font-family);
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.3);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .form-row-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .action-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            color: #fff;
        }
        
        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            color: #fff;
        }
        
        .quick-add-section {
            margin-bottom: 30px;
        }
        
        .quick-add-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .food-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .food-item-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .food-item-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }
        
        .food-item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .food-item-info {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-input {
            padding-left: 40px;
        }
        
        @media (max-width: 768px) {
            .form-row, .form-row-3, .form-row-4 {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the sidebar -->
    <?php include '../welcome/sidebar.php'; ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="../register/uploads/default-avatar.jpg" alt="User Profile">
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="log-food-container">
            <a href="nutrition.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Nutrition
            </a>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="quick-add-section">
                <h3 class="quick-add-title">Quick Add Common Foods</h3>
                
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="foodSearch" class="form-control search-input" placeholder="Search foods...">
                </div>
                
                <div class="food-items-grid" id="foodItemsGrid">
                    <?php if ($food_items->num_rows > 0): ?>
                        <?php while ($item = $food_items->fetch_assoc()): ?>
                            <div class="food-item-card" data-food-name="<?= htmlspecialchars($item['name']) ?>" data-serving="<?= htmlspecialchars($item['serving_size']) ?>" data-calories="<?= $item['calories'] ?>" data-protein="<?= $item['protein'] ?>" data-carbs="<?= $item['carbs'] ?>" data-fat="<?= $item['fat'] ?>">
                                <div class="food-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="food-item-info">
                                    <?= $item['calories'] ?> cal | <?= $item['serving_size'] ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 20px;">
                            No common foods found. You can add your own below.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="log-food-card">
                <div class="log-food-header">
                    <h2 class="log-food-title">
                        <i class="fas fa-utensils"></i> Log Food
                    </h2>
                </div>
                <div class="log-food-body">
                    <form method="POST" action="log-food.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="log_date">Date</label>
                                <input type="date" id="log_date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="meal_time">Meal</label>
                                <select id="meal_time" name="meal_time" class="form-control" required>
                                    <option value="">Select Meal</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Snack">Snack</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="food_name">Food Name</label>
                            <input type="text" id="food_name" name="food_name" class="form-control" placeholder="e.g. Grilled Chicken Breast" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="serving_size">Serving Size</label>
                            <input type="text" id="serving_size" name="serving_size" class="form-control" placeholder="e.g. 100g or 1 cup" required>
                        </div>
                        
                        <div class="form-row-4">
                            <div class="form-group">
                                <label for="calories">Calories</label>
                                <input type="number" id="calories" name="calories" class="form-control" min="0" step="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="protein">Protein (g)</label>
                                <input type="number" id="protein" name="protein" class="form-control" min="0" step="0.1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="carbs">Carbs (g)</label>
                                <input type="number" id="carbs" name="carbs" class="form-control" min="0" step="0.1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="fat">Fat (g)</label>
                                <input type="number" id="fat" name="fat" class="form-control" min="0" step="0.1" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Any additional notes about this food"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <a href="nutrition.php" class="action-btn secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="action-btn">
                                <i class="fas fa-save"></i> Log Food
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quick add food items
            const foodItems = document.querySelectorAll('.food-item-card');
            const foodNameInput = document.getElementById('food_name');
            const servingInput = document.getElementById('serving_size');
            const caloriesInput = document.getElementById('calories');
            const proteinInput = document.getElementById('protein');
            const carbsInput = document.getElementById('carbs');
            const fatInput = document.getElementById('fat');
            
            foodItems.forEach(item => {
                item.addEventListener('click', function() {
                    const foodName = this.getAttribute('data-food-name');
                    const serving = this.getAttribute('data-serving');
                    const calories = this.getAttribute('data-calories');
                    const protein = this.getAttribute('data-protein');
                    const carbs = this.getAttribute('data-carbs');
                    const fat = this.getAttribute('data-fat');
                    
                    foodNameInput.value = foodName;
                    servingInput.value = serving;
                    caloriesInput.value = calories;
                    proteinInput.value = protein;
                    carbsInput.value = carbs;
                    fatInput.value = fat;
                    
                    // Scroll to form
                    document.querySelector('.log-food-card').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Food search functionality
            const searchInput = document.getElementById('foodSearch');
            const foodItemsGrid = document.getElementById('foodItemsGrid');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                foodItems.forEach(item => {
                    const foodName = item.getAttribute('data-food-name').toLowerCase();
                    
                    if (foodName.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
            
            // Show toast message if exists
            const msg = localStorage.getItem('toastMessage');
            if (msg) {
                Toastify({
                    text: msg,
                    duration: 5000,
                    gravity: "top",
                    position: "center",
                    backgroundColor: "#28a745",
                    close: true
                }).showToast();
                localStorage.removeItem('toastMessage');
            }
        });
    </script>
</body>
</html>
