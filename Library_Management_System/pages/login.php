<?php
require_once '../templates/config.php'; 

$successMessage = '';
$errorMessage = '';

session_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = htmlspecialchars($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; 

    
    if (empty($email) || empty($password)) {
        $errorMessage = "Both email and password are required!";
    } else {
        try {
            
            $stmt = $pdo->prepare("SELECT * FROM user WHERE Email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            
            if (!$user) {
                $errorMessage = "User with this email does not exist.";
            } elseif (password_verify($password, $user['Password'])) {
                
                $_SESSION['user_id'] = $user['User_ID']; 
                $_SESSION['username'] = $user['Username']; 
                $_SESSION['email'] = $user['Email']; 

           
                header("Location: dashboard.php");
                exit();
            } else {
                $errorMessage = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Login failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | Library Management System</title>
 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="../css/style.css" rel="stylesheet">
    <style>
      
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
          
        }
        
      
        .bg-decoration {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .bg-circle:nth-child(1) {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -100px;
        }
        
        .bg-circle:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -100px;
        }
        
        .bg-circle:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 50%;
            right: 10%;
        }
        
        
        .login-container {
            width: 420px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .login-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .login-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .login-icon {
            font-size: 60px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 1px solid #e1e1e1;
            border-radius: 10px;
            font-size: 15px;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
            background-color: #fff;
            outline: none;
        }
        
        .form-icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #777;
            font-size: 18px;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #224abe 0%, #4e73df 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .links-container {
            margin-top: 20px;
            text-align: center;
        }
        
        .links-container p {
            margin-bottom: 10px;
            color: #555;
            font-size: 14px;
        }
        
        .links-container a {
            color: #4e73df;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .links-container a:hover {
            color: #224abe;
            text-decoration: underline;
        }
        
        .admin-btn {
            display: inline-block;
            margin-top: 5px;
            padding: 8px 15px;
            background-color: #f8f9fa;
            color: #4e73df;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .admin-btn:hover {
            background-color: #e9ecef;
            color: #224abe;
            text-decoration: none;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 18px;
        }
        
  
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        
        @media (max-width: 480px) {
            .login-container {
                width: 90%;
                margin: 0 20px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-form {
                padding: 20px;
            }
            
            .form-control {
                padding: 12px 12px 12px 40px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-decoration">
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-book-reader"></i>
            </div>
            <h2>User Login</h2>
            <p>Library Management System</p>
        </div>
        
        <div class="login-form">
            <?php if ($successMessage): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="links-container">
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <p>Are you an admin? <a href="admin_login.php" class="admin-btn"><i class="fas fa-user-shield"></i> Admin Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
