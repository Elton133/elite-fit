<?php
include_once "../datacon.php";

// Fetch workout plans
// slect the table_id and workout_name from the workout_plan table
$workout_query = "SELECT table_id, workout_name FROM workout_plan";
// mysqli_query is a function in php that takes two parameters
// the first parameter is the connection to the database
// the second parameter is the query that you want to run
$result = mysqli_query($conn, $workout_query);
// outcome is stored in the result variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit Registration</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="background"></div>
    <div class="form-container">
        <div class="form-header">
            <div class="logo-container">
                <img class="logo-image" src="dumbbell.png" alt="dumbbell">
            </div>
            <h2>EliteFit Gym</h2>
            <p class="form-subtitle">Join our fitness community today</p>
        </div>

        <!-- every form must have an action attribute -->
        <!-- and register_process.php is where my backend code is located -->
        <!-- method attribute is set to POST, tells what data is being sent -->
        <!-- enctype attribute is set to multipart/form-data, which means there's some form of media -->
        <form action="register_process.php" method="POST" enctype="multipart/form-data">
            <!-- Progress indicator -->
            <div class="progress-container">
                <div class="step-indicator active" data-step="1"></div>
                <div class="progress-line"></div>
                <div class="step-indicator" data-step="2"></div>
            </div>
            
            <!-- User Registration Details -->
            <div id="section1" class="form-section">
                <h3>Personal Information</h3>
                
                <div class="form-group">
                    <label>First Name:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="first_name" placeholder="Enter your first name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Last Name:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="last_name" placeholder="Enter your last name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Contact Number:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="contact_number" placeholder="Enter your phone number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Location:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="location" placeholder="Enter your location" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Gender:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" required>
                            <option value="">Choose an option</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date of Birth:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" name="date_of_birth" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Profile Picture:</label>
                    <div class="file-input-container">
                        <i class="fas fa-image"></i>
                        <span class="file-input-label">Choose a file</span>
                        <input type="file" name="profile_picture" id="profile_picture" required>
                    </div>
                </div>
            </div>

            <!-- User Fitness Details -->
            <div id="section2" class="form-section hidden">
                <h3>Fitness Details</h3>
                
                <div class="form-group">
                    <label>Weight (kg):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-weight"></i>
                        <input type="number" name="user_weight" placeholder="Enter your weight in kg" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Height (cm):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-ruler-vertical"></i>
                        <input type="number" name="user_height" placeholder="Enter your height in cm" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Body Type:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-child"></i>
                        <input type="text" name="user_bodytype" placeholder="e.g., Ectomorph, Mesomorph, Endomorph" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Experience level (1 - 10):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-star"></i>
                        <input type="text" name="experience_level" placeholder="Rate your experience from 1 to 10" required>
                    </div>
                </div>
                
                <div class="form-group checkbox-group">
                    <div class="checkbox-container">
                        <input type="checkbox" name="health_condition" id="health_condition" value="1">
                        <label for="health_condition">I have a health condition</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="health_condition_desc">Describe your health condition</label>
                    <div class="input-with-icon textarea-container">
                        <i class="fas fa-notes-medical"></i>
                        <textarea name="health_condition_desc" rows="4" placeholder="Please describe any health conditions or concerns"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Fitness Goal (1):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-bullseye"></i>
                        <input type="text" name="fitness_goal_1" placeholder="Enter your primary fitness goal" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Fitness Goal (2):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-bullseye"></i>
                        <input type="text" name="fitness_goal_2" placeholder="Enter your secondary fitness goal" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Fitness Goal (3):</label>
                    <div class="input-with-icon">
                        <i class="fas fa-bullseye"></i>
                        <input type="text" name="fitness_goal_3" placeholder="Enter your tertiary fitness goal" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <!-- fetching data from database, result variable holds the value of the query. it has an associative row -->
                    <label>Preferred Workout Plan 1:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-dumbbell"></i>
                        <select name="preferred_workout_routine_1" required>
                            <option value="">Choose an option</option>
                            <!-- we want to display the various workout plans in our database table -->
                            <?php mysqli_data_seek($result, 0); while ($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?php echo $row['table_id']; ?>"><?php echo $row['workout_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Preferred Workout Plan 2:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-dumbbell"></i>
                        <select name="preferred_workout_routine_2" required>
                            <option value="">Choose an option</option>
                            <?php mysqli_data_seek($result, 0); while ($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?php echo $row['table_id']; ?>"><?php echo $row['workout_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Preferred Workout Plan 3:</label>
                    <div class="input-with-icon">
                        <i class="fas fa-dumbbell"></i>
                        <select name="preferred_workout_routine_3" required>
                            <option value="">Choose an option</option>
                            <?php mysqli_data_seek($result, 0); while ($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?php echo $row['table_id']; ?>"><?php echo $row['workout_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="btn-container">
                <button type="button" id="prevBtn" class="hidden" onclick="showSection(1)">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" id="nextBtn" onclick="showSection(2)">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" id="submitBtn" class="hidden">
                    <i class="fas fa-check"></i> Submit
                </button>
            </div>
        </form>
    </div>

    <script>
        function showSection(sectionNum) {
            if (sectionNum === 1) {
                document.getElementById('section1').classList.remove('hidden');
                document.getElementById('section2').classList.add('hidden');
                document.getElementById('prevBtn').classList.add('hidden');
                document.getElementById('nextBtn').classList.remove('hidden');
                document.getElementById('submitBtn').classList.add('hidden');
                document.querySelector('.step-indicator[data-step="1"]').classList.add('active');
                document.querySelector('.step-indicator[data-step="2"]').classList.remove('active');
            } else {
                document.getElementById('section1').classList.add('hidden');
                document.getElementById('section2').classList.remove('hidden');
                document.getElementById('prevBtn').classList.remove('hidden');
                document.getElementById('nextBtn').classList.add('hidden');
                document.getElementById('submitBtn').classList.remove('hidden');
                document.querySelector('.step-indicator[data-step="1"]').classList.add('completed');
                document.querySelector('.step-indicator[data-step="2"]').classList.add('active');
            }
        }

        // File input label update
        document.getElementById('profile_picture').addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Choose a file';
            document.querySelector('.file-input-label').textContent = fileName;
        });

        // Background image rotation
        const backgrounds = [
            'url("https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1517836357463-d25dfeac3438?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")',
            'url("https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80")'
        ];
        
        let currentBg = 0;
        const bgElement = document.querySelector('.background');
        
        function changeBackground() {
            bgElement.style.backgroundImage = backgrounds[currentBg];
            currentBg = (currentBg + 1) % backgrounds.length;
        }
        
        changeBackground(); // Set initial background
        setInterval(changeBackground, 5000); // Change every 5 seconds
    </script>
</body>
</html>
