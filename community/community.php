<?php
session_start();
include_once "../datacon.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT * FROM user_register_details WHERE table_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Get recent posts
$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.profile_picture, 
                        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
                        (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
                        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
                        FROM community_posts p 
                        JOIN user_register_details u ON p.user_id = u.table_id 
                        ORDER BY p.created_at DESC 
                        LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts = $stmt->get_result();

// Process new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    $image_path = null;
    
    // Handle image upload if present
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['post_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (in_array(strtolower($filetype), $allowed)) {
            // Create unique filename
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = "../uploads/community/" . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists("../uploads/community")) {
                mkdir("../uploads/community", 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_path)) {
                $image_path = "uploads/community/" . $new_filename;
            }
        }
    }
    
    if (!empty($content) || $image_path) {
        // Insert post
        $stmt = $conn->prepare("INSERT INTO community_posts (user_id, content, image_path, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $user_id, $content, $image_path);
        
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Post shared successfully!";
            header("Location: community.php");
            exit();
        }
    }
}

// Get active challenges
$stmt = $conn->prepare("SELECT c.*, 
                       (SELECT COUNT(*) FROM challenge_participants WHERE challenge_id = c.id) as participant_count,
                       (SELECT COUNT(*) FROM challenge_participants WHERE challenge_id = c.id AND user_id = ?) as user_joined
                       FROM community_challenges c 
                       WHERE c.end_date >= CURDATE() 
                       ORDER BY c.start_date ASC 
                       LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$challenges = $stmt->get_result();

// Get upcoming events
$stmt = $conn->prepare("SELECT e.*, 
                       (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendee_count,
                       (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id AND user_id = ?) as user_attending
                       FROM community_events e 
                       WHERE e.event_date >= CURDATE() 
                       ORDER BY e.event_date ASC 
                       LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Community - EliteFit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../welcome/welcome-styles.css">
    <link rel="stylesheet" href="../welcome/sidebar-styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .community-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        
        .feed-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .post-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .post-header {
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
        }
        
        .post-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .post-user-info {
            flex: 1;
        }
        
        .post-user-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .post-time {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .post-content {
            padding: 15px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .post-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .post-actions {
            padding: 15px;
            display: flex;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .post-action {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .post-action:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .post-action.liked {
            color: #e74c3c;
        }
        
        .create-post-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
        }
        
        .create-post-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .create-post-input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-family: var(--font-family);
            font-size: 16px;
            resize: none;
            transition: all 0.3s ease;
        }
        
        .create-post-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 2px rgba(30, 60, 114, 0.3);
        }
        
        .create-post-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .post-attachments {
            display: flex;
            gap: 15px;
        }
        
        .attachment-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.05);
            border: none;
            font-family: var(--font-family);
            font-size: 14px;
        }
        
        .attachment-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .post-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: var(--font-family);
            font-size: 14px;
            font-weight: 500;
        }
        
        .post-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .post-btn:disabled {
            background: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .sidebar-card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-card-header {
            background: rgba(30, 60, 114, 0.3);
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-card-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-card-body {
            padding: 15px;
        }
        
        .challenge-item {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .challenge-item:last-child {
            border-bottom: none;
        }
        
        .challenge-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .challenge-dates {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
        }
        
        .challenge-description {
            font-size: 14px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .challenge-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .challenge-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: var(--font-family);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .challenge-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .challenge-btn.joined {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .event-item {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .event-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .event-date {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .event-location {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .event-description {
            font-size: 14px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .event-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .event-btn {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: var(--font-family);
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        
        .event-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .event-btn.attending {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .view-all-link {
            display: block;
            text-align: center;
            padding: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .view-all-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .image-preview {
            margin-top: 10px;
            position: relative;
            display: none;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        @media (max-width: 992px) {
            .community-container {
                grid-template-columns: 1fr;
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
        
        <div class="community-container">
            <div class="feed-column">
                <!-- Create Post Card -->
                <div class="create-post-card">
                    <div class="create-post-header">
                        <div class="post-avatar">
                            <img src="<?= !empty($user_data['profile_picture']) ? '../register/uploads/' . htmlspecialchars($user_data['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="Your Profile">
                        </div>
                        <div class="post-user-info">
                            <div class="post-user-name"><?= htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) ?></div>
                        </div>
                    </div>
                    
                    <form method="POST" action="community.php" enctype="multipart/form-data">
                        <textarea class="create-post-input" name="post_content" placeholder="Share your fitness journey..." rows="3"></textarea>
                        
                        <div class="image-preview" id="imagePreview">
                            <img src="#" alt="Preview" class="preview-image" id="previewImage">
                            <button type="button" class="remove-image" id="removeImage">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="create-post-actions">
                            <div class="post-attachments">
                                <label for="postImage" class="attachment-btn">
                                    <i class="fas fa-image"></i> Photo
                                </label>
                                <input type="file" id="postImage" name="post_image" accept="image/*" style="display: none;">
                            </div>
                            
                            <button type="submit" class="post-btn" id="postButton" disabled>Post</button>
                        </div>
                    </form>
                </div>
                
                <!-- Posts Feed -->
                <?php if ($posts->num_rows > 0): ?>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-avatar">
                                    <img src="<?= !empty($post['profile_picture']) ? '../register/uploads/' . htmlspecialchars($post['profile_picture']) : '../register/uploads/default-avatar.jpg' ?>" alt="User Profile">
                                </div>
                                <div class="post-user-info">
                                    <div class="post-user-name"><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></div>
                                    <div class="post-time"><?= time_elapsed_string($post['created_at']) ?></div>
                                </div>
                            </div>
                            
                            <?php if (!empty($post['content'])): ?>
                                <div class="post-content">
                                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['image_path'])): ?>
                                <img src="../<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" class="post-image">
                            <?php endif; ?>
                            
                            <div class="post-actions">
                                <div class="post-action <?= $post['user_liked'] ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>" onclick="likePost(<?= $post['id'] ?>)">
                                    <i class="fas fa-heart"></i> <span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?></span>
                                </div>
                                <div class="post-action" onclick="location.href='post.php?id=<?= $post['id'] ?>'">
                                    <i class="fas fa-comment"></i> <span><?= $post['comment_count'] ?></span>
                                </div>
                                <div class="post-action">
                                    <i class="fas fa-share"></i> Share
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="post-card">
                        <div class="post-content" style="text-align: center; padding: 30px;">
                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; color: rgba(255, 255, 255, 0.5);"></i>
                            <p>No posts yet. Be the first to share your fitness journey!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-column">
                <!-- Challenges Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h3 class="sidebar-card-title">
                            <i class="fas fa-trophy"></i> Active Challenges
                        </h3>
                    </div>
                    <div class="sidebar-card-body">
                        <?php if ($challenges->num_rows > 0): ?>
                            <?php while ($challenge = $challenges->fetch_assoc()): ?>
                                <div class="challenge-item">
                                    <div class="challenge-title"><?= htmlspecialchars($challenge['title']) ?></div>
                                    <div class="challenge-dates">
                                        <i class="fas fa-calendar"></i> <?= date('M d', strtotime($challenge['start_date'])) ?> - <?= date('M d, Y', strtotime($challenge['end_date'])) ?>
                                    </div>
                                    <div class="challenge-description">
                                        <?= htmlspecialchars(substr($challenge['description'], 0, 100)) . (strlen($challenge['description']) > 100 ? '...' : '') ?>
                                    </div>
                                    <div class="challenge-meta">
                                        <div><?= $challenge['participant_count'] ?> participants</div>
                                        <?php if ($challenge['user_joined']): ?>
                                            <a href="challenge.php?id=<?= $challenge['id'] ?>" class="challenge-btn joined">
                                                <i class="fas fa-check"></i> Joined
                                            </a>
                                        <?php else: ?>
                                            <a href="join-challenge.php?id=<?= $challenge['id'] ?>" class="challenge-btn">
                                                Join Challenge
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <a href="challenges.php" class="view-all-link">View All Challenges</a>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px;">
                                <p>No active challenges at the moment.</p>
                                <a href="challenges.php" class="challenge-btn" style="margin-top: 10px;">
                                    Browse Challenges
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Events Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h3 class="sidebar-card-title">
                            <i class="fas fa-calendar-alt"></i> Upcoming Events
                        </h3>
                    </div>
                    <div class="sidebar-card-body">
                        <?php if ($events->num_rows > 0): ?>
                            <?php while ($event = $events->fetch_assoc()): ?>
                                <div class="event-item">
                                    <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
                                    <div class="event-date">
                                        <i class="fas fa-calendar"></i> <?= date('l, M d, Y', strtotime($event['event_date'])) ?>
                                    </div>
                                    <div class="event-location">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?>
                                    </div>
                                    <div class="event-description">
                                        <?= htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : '') ?>
                                    </div>
                                    <div class="event-meta">
                                        <div><?= $event['attendee_count'] ?> attending</div>
                                        <?php if ($event['user_attending']): ?>
                                            <a href="event.php?id=<?= $event['id'] ?>" class="event-btn attending">
                                                <i class="fas fa-check"></i> Attending
                                            </a>
                                        <?php else: ?>
                                            <a href="attend-event.php?id=<?= $event['id'] ?>" class="event-btn">
                                                Attend
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <a href="events.php" class="view-all-link">View All Events</a>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px;">
                                <p>No upcoming events at the moment.</p>
                                <a href="events.php" class="event-btn" style="margin-top: 10px;">
                                    Browse Events
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../welcome/sidebar-script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const postContent = document.querySelector('textarea[name="post_content"]');
            const postButton = document.getElementById('postButton');
            const postImage = document.getElementById('postImage');
            const imagePreview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            const removeImage = document.getElementById('removeImage');
            
            // Enable/disable post button based on content
            postContent.addEventListener('input', function() {
                postButton.disabled = this.value.trim() === '' && !postImage.files.length;
            });
            
            // Handle image preview
            postImage.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        imagePreview.style.display = 'block';
                        postButton.disabled = false;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Remove image
            removeImage.addEventListener('click', function() {
                postImage.value = '';
                imagePreview.style.display = 'none';
                postButton.disabled = postContent.value.trim() === '';
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
        
        // Like post function
        function likePost(postId) {
            fetch('like-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeButton = document.querySelector(`.post-action[data-post-id="${postId}"]`);
                    const likeCount = document.getElementById(`like-count-${postId}`);
                    
                    if (data.liked) {
                        likeButton.classList.add('liked');
                    } else {
                        likeButton.classList.remove('liked');
                    }
                    
                    likeCount.textContent = data.count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>

<?php
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    $diffArray = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $diff->d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    foreach ($string as $k => &$v) {
        if ($diffArray[$k]) {
            $v = $diffArray[$k] . ' ' . $v . ($diffArray[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
