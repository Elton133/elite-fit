<?php
    session_start();
    require_once('../datacon.php');

    // Redirect if session variables are not set
    if (!isset($_SESSION['email']) || !isset($_SESSION['table_id'])) {
        header("Location: ../login/index.php");
        exit();
    }

    $email = $_SESSION['email'];
    $table_id = $_SESSION['table_id'];
    $role = $_SESSION['role'] ?? 'user';

    // Fetch user data
    $sql_user = "SELECT table_id, first_name, last_name, profile_picture FROM user_register_details WHERE email = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();

    // Handle profile picture
    $profile_pic = "../register/uploads/default-avatar.jpg"; 
    if (!empty($user_data['profile_picture'])) {
        if (file_exists("../register/uploads/" . $user_data['profile_picture'])) {
            $profile_pic = "../register/uploads/" . $user_data['profile_picture'];
        } elseif (file_exists("../register/" . $user_data['profile_picture'])) {
            $profile_pic = "../register/" . $user_data['profile_picture'];
        }
    }

    // Define product categories
    $categories = [
        'all' => 'All Products',
        'muscle_building' => 'Muscle-building Foods',
        'fruits' => 'Fruits',
        'supplements' => 'Supplements',
        'lean_diet' => 'Lean Muscle Diet',
        'accessories' => 'Fitness Accessories'
    ];

    // Define category icons
    $category_icons = [
        'all' => 'fa-shopping-cart',
        'muscle_building' => 'fa-drumstick-bite',
        'fruits' => 'fa-apple-alt',
        'supplements' => 'fa-pills',
        'lean_diet' => 'fa-leaf',
        'accessories' => 'fa-dumbbell'
    ];

    // Define products (static data for v0)
    $products = [
        [
            'id' => 1,
            'name' => 'Premium Whey Protein',
            'description' => 'High-quality protein powder for muscle recovery and growth.',
            'price' => 49.99,
            'category' => 'supplements',
            'image' => 'whey_protein.png',
            'nutrition' => [
                'Protein' => '24g per serving',
                'Carbs' => '3g per serving',
                'Fat' => '1g per serving',
                'Calories' => '120 per serving'
            ],
            'recommended_use' => 'Best taken post-workout within 30 minutes',
            'suggested_for' => 'Muscle building and recovery'
        ],
        [
            'id' => 2,
            'name' => 'Organic Chicken Breast',
            'description' => 'Lean, high-protein chicken breast for muscle building.',
            'price' => 12.99,
            'category' => 'muscle_building',
            'image' => 'chicken.jpg',
            'nutrition' => [
                'Protein' => '31g per 100g',
                'Fat' => '3.6g per 100g',
                'Calories' => '165 per 100g'
            ],
            'recommended_use' => 'Consume as part of main meals',
            'suggested_for' => 'Lean muscle building and maintenance'
        ],
        [
            'id' => 3,
            'name' => 'Organic Bananas',
            'description' => 'Natural energy source rich in potassium and carbs.',
            'price' => 2.99,
            'category' => 'fruits',
            'image' => 'banana.jpeg',
            'nutrition' => [
                'Carbs' => '27g per 100g',
                'Fiber' => '3.1g per 100g',
                'Potassium' => '358mg per 100g',
                'Calories' => '89 per 100g'
            ],
            'recommended_use' => 'Great pre or post-workout snack',
            'suggested_for' => 'Natural energy boost'
        ],
        [
            'id' => 4,
            'name' => 'Creatine Monohydrate',
            'description' => 'Increases strength, power and muscle mass during high-intensity training.',
            'price' => 29.99,
            'category' => 'supplements',
            'image' => 'creatine.png',
            'nutrition' => [
                'Creatine' => '5g per serving',
                'Calories' => '0 per serving'
            ],
            'recommended_use' => 'Take 5g daily, timing not critical',
            'suggested_for' => 'Strength and power athletes'
        ],
        [
            'id' => 5,
            'name' => 'Avocados',
            'description' => 'Nutrient-dense fruit with healthy fats for recovery and hormone production.',
            'price' => 3.49,
            'category' => 'lean_diet',
            'image' => 'avocado.jpg',
            'nutrition' => [
                'Fat' => '15g per 100g (mostly monounsaturated)',
                'Fiber' => '7g per 100g',
                'Potassium' => '485mg per 100g',
                'Calories' => '160 per 100g'
            ],
            'recommended_use' => 'Add to meals for healthy fats',
            'suggested_for' => 'Overall health and recovery'
        ],
        [
            'id' => 6,
            'name' => 'Weightlifting Gloves',
            'description' => 'Premium gloves for better grip and hand protection during lifting.',
            'price' => 24.99,
            'category' => 'accessories',
            'image' => 'lifting_gloves.png',
            'features' => [
                'Padded palm for comfort',
                'Wrist support',
                'Breathable material',
                'Machine washable'
            ],
            'recommended_use' => 'For heavy lifting sessions',
            'suggested_for' => 'Preventing calluses and improving grip'
        ],
        [
            'id' => 7,
            'name' => 'BCAA Supplement',
            'description' => 'Branch Chain Amino Acids for muscle preservation and recovery.',
            'price' => 34.99,
            'category' => 'supplements',
            'image' => 'bcaa.jpg',
            'nutrition' => [
                'Leucine' => '2.5g per serving',
                'Isoleucine' => '1.25g per serving',
                'Valine' => '1.25g per serving',
                'Calories' => '5 per serving'
            ],
            'recommended_use' => 'During workouts or throughout the day',
            'suggested_for' => 'Preventing muscle breakdown during intense training'
        ],
        [
            'id' => 8,
            'name' => 'Salmon Fillets',
            'description' => 'Wild-caught salmon rich in protein and omega-3 fatty acids.',
            'price' => 15.99,
            'category' => 'muscle_building',
            'image' => 'salmon.jpeg',
            'nutrition' => [
                'Protein' => '25g per 100g',
                'Omega-3' => '2.3g per 100g',
                'Vitamin D' => '526 IU per 100g',
                'Calories' => '208 per 100g'
            ],
            'recommended_use' => 'Consume 2-3 times per week',
            'suggested_for' => 'Muscle recovery and reducing inflammation'
        ],
        [
            'id' => 9,
            'name' => 'Blueberries',
            'description' => 'Antioxidant-rich berries that help with recovery and inflammation.',
            'price' => 4.99,
            'category' => 'fruits',
            'image' => 'blueberries.jpg',
            'nutrition' => [
                'Antioxidants' => 'High',
                'Fiber' => '2.4g per 100g',
                'Vitamin C' => '9.7mg per 100g',
                'Calories' => '57 per 100g'
            ],
            'recommended_use' => 'Daily consumption',
            'suggested_for' => 'Recovery and immune support'
        ],
        [
            'id' => 10,
            'name' => 'Greek Yogurt',
            'description' => 'High-protein, probiotic-rich dairy for muscle building and gut health.',
            'price' => 5.49,
            'category' => 'lean_diet',
            'image' => 'greek_yogurt.png',
            'nutrition' => [
                'Protein' => '10g per 100g',
                'Calcium' => '115mg per 100g',
                'Probiotics' => 'Live cultures',
                'Calories' => '59 per 100g'
            ],
            'recommended_use' => 'Great as a snack or breakfast',
            'suggested_for' => 'Protein intake and digestive health'
        ],
        [
            'id' => 11,
            'name' => 'Shaker Bottle',
            'description' => 'BPA-free bottle with blender ball for smooth protein shakes.',
            'price' => 9.99,
            'category' => 'accessories',
            'image' => 'shaker_bottle.jpg',
            'features' => [
                '28oz capacity',
                'Leak-proof lid',
                'Measurement markings',
                'Dishwasher safe'
            ],
            'recommended_use' => 'For mixing supplements',
            'suggested_for' => 'Convenient supplement consumption'
        ],
        [
            'id' => 12,
            'name' => 'Sweet Potatoes',
            'description' => 'Complex carbs for sustained energy and muscle glycogen.',
            'price' => 2.49,
            'category' => 'lean_diet',
            'image' => 'sweet_potato.jpg',
            'nutrition' => [
                'Carbs' => '20g per 100g',
                'Fiber' => '3g per 100g',
                'Vitamin A' => '14187 IU per 100g',
                'Calories' => '86 per 100g'
            ],
            'recommended_use' => 'Great pre-workout or post-workout carb source',
            'suggested_for' => 'Energy and glycogen replenishment'
        ],
        [
            'id' => 13,
            'name' => 'Resistance Bands Set',
            'description' => 'Versatile bands for strength training and mobility work.',
            'price' => 19.99,
            'category' => 'accessories',
            'image' => 'resistance_bands.jpeg',
            'features' => [
                '5 different resistance levels',
                'Door anchor included',
                'Handles for comfort',
                'Carrying bag included'
            ],
            'recommended_use' => 'For home workouts or mobility work',
            'suggested_for' => 'All fitness levels'
        ],
        [
            'id' => 14,
            'name' => 'Vitamin D3 Supplement',
            'description' => 'Essential vitamin for bone health and immune function.',
            'price' => 14.99,
            'category' => 'supplements',
            'image' => 'vitamin_d.png',
            'nutrition' => [
                'Vitamin D3' => '5000 IU per serving',
                'Calories' => '0 per serving'
            ],
            'recommended_use' => 'Take daily with a meal containing fat',
            'suggested_for' => 'Overall health and performance'
        ],
        [
            'id' => 15,
            'name' => 'Quinoa',
            'description' => 'Complete protein grain alternative rich in nutrients.',
            'price' => 6.99,
            'category' => 'muscle_building',
            'image' => 'quinoa.jpg',
            'nutrition' => [
                'Protein' => '4.4g per 100g (cooked)',
                'Fiber' => '2.8g per 100g (cooked)',
                'Iron' => '1.5mg per 100g (cooked)',
                'Calories' => '120 per 100g (cooked)'
            ],
            'recommended_use' => 'As a base for meals',
            'suggested_for' => 'Vegetarians and those seeking complete proteins'
        ],
        [
            'id' => 16,
            'name' => 'Apples',
            'description' => 'Fiber-rich fruit for digestive health and natural energy.',
            'price' => 1.49,
            'category' => 'fruits',
            'image' => 'apples.jpeg',
            'nutrition' => [
                'Fiber' => '2.4g per 100g',
                'Vitamin C' => '4.6mg per 100g',
                'Antioxidants' => 'High',
                'Calories' => '52 per 100g'
            ],
            'recommended_use' => 'Great pre-workout snack',
            'suggested_for' => 'Sustained energy and digestive health'
        ]
    ];

    // Get selected category (default to 'all')
    $selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    // Get search query if any
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Filter products based on category and search query
    $filtered_products = [];
    foreach ($products as $product) {
        $category_match = ($selected_category === 'all' || $product['category'] === $selected_category);
        $search_match = empty($search_query) || 
                        stripos($product['name'], $search_query) !== false || 
                        stripos($product['description'], $search_query) !== false ||
                        stripos($categories[$product['category']], $search_query) !== false;
        
        if ($category_match && $search_match) {
            $filtered_products[] = $product;
        }
    }

    // Greeting logic
    $hour = date("H");
    if ($hour >= 5 && $hour < 12) {
        $greeting = "Good morning";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting = "Good afternoon";
    } elseif ($hour >= 17 && $hour < 21) {
        $greeting = "Good evening";
    } else {
        $greeting = "Good night";
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit Shop - Discover Fitness Products</title>
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Shop Specific Styles */
        .shop-container {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .shop-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .shop-title p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        .shop-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-input {
            padding: 12px 20px;
            border-radius: 50px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.08);
            color: white;
            min-width: 250px;
            font-family: var(--font-family);
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-family: var(--font-family);
            font-size: 14px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 30, 30, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Manager Note */
        .manager-note {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .manager-note-icon {
            font-size: 24px;
            color: #667eea;
            margin-top: 2px;
        }

        .manager-note-content h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: white;
        }

        .manager-note-content p {
            margin: 0;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        /* Category Filters */
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .category-filter {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 12px 24px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-family);
            text-decoration: none;
        }

        .category-filter:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .category-filter.active {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .product-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .product-image {
            height: 200px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.05);
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .product-category {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(5px);
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }

        .product-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Product Modal */
        .product-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: rgba(40, 40, 40, 0.95);
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h3 {
            font-size: 24px;
            margin: 0;
            color: white;
        }

        .close-modal {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: white;
        }

        .modal-body {
            padding: 25px;
        }

        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .product-detail-image {
            height: 300px;
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .product-detail-info h4 {
            font-size: 16px;
            margin: 0 0 10px 0;
            color: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }

        .product-detail-info ul {
            margin: 0 0 20px 0;
            padding-left: 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .product-detail-info li {
            margin-bottom: 5px;
        }

        .product-detail-info p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
        }

        .product-detail-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .product-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .product-meta-icon {
            color: #667eea;
            font-size: 16px;
            margin-top: 2px;
        }

        .product-meta-content {
            flex: 1;
        }

        .product-meta-content h5 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: white;
        }

        .product-meta-content p {
            margin: 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 50px 0;
            color: rgba(255, 255, 255, 0.7);
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            color: rgba(255, 255, 255, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .shop-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .shop-actions {
                width: 100%;
            }

            .search-form {
                width: 100%;
            }

            .search-input {
                flex: 1;
            }

            .product-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .shop-container {
                padding: 20px;
                margin: 20px 0;
            }

            .category-filters {
                overflow-x: auto;
                padding-bottom: 10px;
                flex-wrap: nowrap;
            }

            .category-filter {
                white-space: nowrap;
            }

            .search-form {
                flex-direction: column;
                gap: 15px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Placeholder image styling */
        .placeholder-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <!-- Include the appropriate sidebar based on user role -->
    <?php 
    if ($role === 'admin') {
        include '../admin/admin-sidebar.php';
    } elseif ($role === 'trainer') {
        include '../trainer/trainer-sidebar.php';
    } elseif ($role === 'equipment_manager') {
        include '../equipment-manager/equipment-manager-sidebar.php';
    } else {
        include '../welcome/sidebar.php';
    }
    ?>
    
    <div class="container">
        <header class="main-header">
            <div class="mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?></h3>
                        <p class="user-status"><?= ucfirst($role) ?></p>
                    </div>
                    <div class="dropdown-menu">
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-content">
                            <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                            <a href="#"><i class="fas fa-cog"></i> Settings</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="welcome-section">
            <h1><?php echo $greeting; ?>, <?= htmlspecialchars($user_data['first_name']) ?>!</h1>
            <p>Welcome to the EliteFit Shop. Discover products to enhance your fitness journey.</p>
        </div>
        
        <div class="shop-container">
            <div class="shop-header">
                <div class="shop-title">
                    <h2><i class="fas fa-store"></i> EliteFit Shop</h2>
                    <p>Discover premium fitness products recommended by our trainers</p>
                </div>
                
                <div class="shop-actions">
                    <form action="" method="GET" class="search-form">
                        <?php if ($selected_category !== 'all'): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="Search products..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Manager Note -->
            <div class="manager-note">
                <div class="manager-note-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="manager-note-content">
                    <h4>Trainer's Recommendations</h4>
                    <p>Here are some of the best picks for your fitness journey. These products are carefully selected by our trainers to help you achieve your goals. Ask our trainers if you're unsure what's best for you!</p>
                </div>
            </div>
            
            <!-- Category Filters -->
            <div class="category-filters">
                <?php foreach ($categories as $key => $name): ?>
                    <a href="?category=<?php echo $key; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                       class="category-filter <?php echo ($selected_category === $key) ? 'active' : ''; ?>">
                        <i class="fas <?php echo $category_icons[$key]; ?>"></i>
                        <?php echo htmlspecialchars($name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($filtered_products) > 0): ?>
                <!-- Product Grid -->
                <div class="product-grid">
                    <?php foreach ($filtered_products as $product): ?>
                        <div class="product-card" onclick="showProductDetails(<?php echo $product['id']; ?>)">
                            <div class="product-image <?php echo !file_exists('../images/products/' . $product['image']) ? 'placeholder-image' : ''; ?>" 
                                 style="background-image: url('<?php echo file_exists('../images/products/' . $product['image']) ? '../images/products/' . $product['image'] : ''; ?>')">
                                <?php if (!file_exists('../images/products/' . $product['image'])): ?>
                                    <i class="fas fa-image"></i>
                                <?php endif; ?>
                                <span class="product-category">
                                    <i class="fas <?php echo $category_icons[$product['category']]; ?>"></i>
                                    <?php echo htmlspecialchars($categories[$product['category']]); ?>
                                </span>
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-price">
                                    <i class="fas fa-tag"></i>
                                    $<?php echo number_format($product['price'], 2); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No products found matching your criteria.</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="main-footer">
            <p>&copy; 2025 EliteFit Gym. All rights reserved.</p>
        </footer>
    </div>
    
    <!-- Product Detail Modal -->
    <div class="product-modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalProductName">Product Details</h3>
                <button class="close-modal" onclick="closeProductModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-details" id="productDetails">
                    <!-- Product details will be loaded here via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeProductModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Product data for JavaScript
        const products = <?php echo json_encode($products); ?>;
        const categories = <?php echo json_encode($categories); ?>;
        
        // Show product details in modal
        function showProductDetails(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            
            const modal = document.getElementById('productModal');
            const modalTitle = document.getElementById('modalProductName');
            const productDetails = document.getElementById('productDetails');
            
            modalTitle.textContent = product.name;
            
            const imagePath = `../images/products/${product.image}`;

let detailsHTML = `
    <div class="product-detail-image" style="background-image: url('${imagePath}')"></div>
    <div class="product-detail-info">
        <h4>About this product</h4>
        <p>${product.description}</p>

        <h4>Category</h4>
        <p><i class="fas fa-tag"></i> ${categories[product.category]}</p>

        <h4>Price</h4>
        <p><strong>GHS ${product.price.toFixed(2)}</strong></p>
`;
            
            // Add nutrition facts if available
            if (product.nutrition) {
                detailsHTML += `
                    <h4>Nutrition Facts</h4>
                    <ul>
                `;
                
                for (const [key, value] of Object.entries(product.nutrition)) {
                    detailsHTML += `<li><strong>${key}:</strong> ${value}</li>`;
                }
                
                detailsHTML += `</ul>`;
            }
            
            // Add features if available (for accessories)
            if (product.features) {
                detailsHTML += `
                    <h4>Features</h4>
                    <ul>
                `;
                
                for (const feature of product.features) {
                    detailsHTML += `<li>${feature}</li>`;
                }
                
                detailsHTML += `</ul>`;
            }
            
            // Add recommended use and suggested for
            detailsHTML += `
                <div class="product-detail-meta">
                    <div class="product-meta-item">
                        <div class="product-meta-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="product-meta-content">
                            <h5>Recommended Use</h5>
                            <p>${product.recommended_use || 'No specific recommendations'}</p>
                        </div>
                    </div>
                    
                    <div class="product-meta-item">
                        <div class="product-meta-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="product-meta-content">
                            <h5>Suggested For</h5>
                            <p>${product.suggested_for || 'All fitness levels'}</p>
                        </div>
                    </div>
                </div>
            `;
            
            detailsHTML += `</div>`;
            
            productDetails.innerHTML = detailsHTML;
            modal.style.display = 'flex';
            
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }
        
        // Close product modal
        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.style.display = 'none';
            
            // Re-enable body scrolling
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }
    </script>
    
    <script src="../scripts/background.js"></script>
    <script src="../scripts/dropdown-menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
</body>
</html>