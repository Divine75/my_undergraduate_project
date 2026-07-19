<?php
// login.php - Secure Portal Login

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';


// Redirect if already logged in
if (is_logged_in()) {
    header("Location: admin/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Log audit trail
                log_audit('login', 'User logged in successfully');
                
                header("Location: admin/index.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
                // Log failed attempt
                log_audit('failed_login', "Failed login attempt for username: $username");
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATAMIS - Staff Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(rgba(8, 22, 39, 0.9), rgba(8, 22, 39, 0.95)), 
                        url('assets/images/hero_pattern.png') repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #fff;
        }
        .login-card {
            background-color: #0F3057;
            border: 2px solid #D4AF37;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            max-width: 420px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .login-logo {
            width: 60px;
            height: 60px;
            border: 2px solid #D4AF37;
            background-color: #D4AF37;
            color: #0F3057;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px auto;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .login-card h2 {
            font-family: 'Playfair Display', serif;
            color: #D4AF37;
            margin-bottom: 5px;
        }
        .login-card p {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        .form-control {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: rgba(255,255,255,0.08);
            border-color: #D4AF37;
            box-shadow: none;
            color: #fff;
        }
        .btn-login {
            background-color: #D4AF37;
            color: #0F3057;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-login:hover {
            background-color: #B38F1E;
            color: #fff;
        }
        .back-link {
            display: block;
            margin-top: 25px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link:hover {
            color: #D4AF37;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 25px 20px;
                max-width: 90%;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-logo">A</div>
    <h2>ATAMIS Login</h2>
    <p>Atsiame Area Management Info System</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 text-start" style="font-size: 0.85rem;" role="alert">
            <i class="fas fa-triangle-exclamation me-2"></i> <?php echo sanitize($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3 text-start">
            <label class="form-label text-warning small" for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
        </div>
        <div class="mb-4 text-start">
            <label class="form-label text-warning small" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login"><i class="fas fa-right-to-bracket me-2"></i> Access Dashboard</button>
    </form>
    
    <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Back to Public Site</a>
</div>

</body>
</html>
