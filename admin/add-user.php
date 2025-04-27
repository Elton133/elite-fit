<?php
 include('../datacon.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User</title>
  <link rel="stylesheet" href="../welcome/welcome-styles.css"> 
  <link rel="stylesheet" href="add-user.css"> 
  <style>
    .role-card { cursor: pointer; border: 2px solid #ccc; padding: 15px; border-radius: 8px; display: inline-block; margin: 10px; }
    .role-card.selected { border-color: #007bff; background-color: #f0f8ff; }
    .hidden { display: none; }
  </style>
</head>
<body>
<div class="container">
    <div class="background"></div>
<h2>Add New User</h2>
<?php include 'admin-sidebar.php'; ?>

<div>
  <div class="role-card" onclick="selectRole('trainer')">👤 Trainer</div>
  <div class="role-card" onclick="selectRole('equipment_manager')">🛠 Equipment Manager</div>
</div>

<form action="add-user-process.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="role" id="role" value="">

  <label>First Name: <input type="text" name="first_name" required></label><br>
  <label>Last Name: <input type="text" name="last_name" required></label><br>
  <label>Contact Number: <input type="text" name="contact_number" required></label><br>
  <label>Email: <input type="email" name="email" required></label><br>
  <label>Password: <input type="password" name="user_password" required></label><br>
  <label>Location: <input type="text" name="location"></label><br>
  <label>Gender: 
    <select name="gender">
      <option value="Male">Male</option>
      <option value="Female">Female</option>
    </select>
  </label><br>
  <label>Date of Birth: <input type="date" name="dob"></label><br>
  <label>Profile Picture: <input type="file" name="profile_picture"></label><br>

  <!-- Trainer Specific Fields -->
  <div id="trainer-fields" class="hidden">
    <label>Specialization: <input type="text" name="specialization"></label><br>
    <label>Experience (Years): <input type="number" name="experience_years"></label><br>
    <label>Bio: <textarea name="bio"></textarea></label><br>
    <label>Availability: 
      <select name="availability_status">
        <option value="Available">Available</option>
        <option value="Busy">Busy</option>
      </select>
    </label><br>
  </div>

  <button type="submit" id="submitBtn" class="submit" name="add_user">Add User</button>
</form>
</div>

<script src="../scripts/background.js"></script>
<script>
//   function selectRole(role) {
//     document.getElementById('role').value = role;
//     document.querySelectorAll('.role-card').forEach(el => el.classList.remove('selected'));
//     document.querySelectorAll('.role-card').forEach(el => {
//       if (el.textContent.toLowerCase().includes(role)) el.classList.add('selected');
//     });

//     document.getElementById('trainer-fields').classList.toggle('hidden', role !== 'trainer');
//   }

  function selectRole(role) {
    // Set hidden input value
    document.getElementById("role").value = role;

    // Highlight selected card
    document.querySelectorAll('.role-card').forEach(card => {
      card.classList.remove('active');
    });
    const selectedCard = [...document.querySelectorAll('.role-card')].find(card => card.onclick.toString().includes(role));
    if (selectedCard) selectedCard.classList.add('active');

    // Show or hide trainer fields
    const trainerFields = document.getElementById("trainer-fields");
    if (role === "trainer") {
      trainerFields.classList.remove("hidden");
    } else {
      trainerFields.classList.add("hidden");
    }
  }
</script>

</body>
</html>
