<?php
require_once '../templates/config.php'; 

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $student_ID = isset($_POST['student_ID']) ? htmlspecialchars($_POST['student_ID']) : '';
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : '';

    
    if (empty($student_ID) || empty($username) || empty($email) || empty($password)) {
        $errorMessage = "All fields are required!";
    } else {
        try {
            
            $stmt = $pdo->prepare("INSERT INTO user (User_ID, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$student_ID, $username, $email, $password]);
            $successMessage = "Registration successful! Redirecting to dashboard...";
            
            
            header("Refresh: 2; URL=dashboard.php"); 
            exit();
        } catch (PDOException $e) {
           
            if ($e->getCode() == 23000) { 
                $errorMessage = "Student ID or Email already exists. Please use different values.";
            } else {
                $errorMessage = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration | Library Management System</title>
   
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
    <link rel="stylesheet" href="../css/style.css">
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
        
        
        .register-container {
            width: 450px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: fadeIn 0.8s ease-in-out;
        }
        
        .register-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .register-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .register-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .register-icon {
            font-size: 60px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .register-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
            margin-left: 10px;
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
            margin-left: 10px;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
            background-color: #fff;
            outline: none;
        }
        
        .form-icon {
            position: absolute;
            left: -10px;
            top: 42px;
            color: #777;
            font-size: 18px;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            background: linear-gradient(135deg, #224abe 0%, #4e73df 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-register:active {
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
            .register-container {
                width: 90%;
                margin: 0 20px;
            }
            
            .register-header {
                padding: 20px;
            }
            
            .register-form {
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
    
    <div class="register-container">
        <div class="register-header">
            <div class="register-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Create Account</h2>
            <p>Library Management System</p>
        </div>
        
        <div class="register-form">
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
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="student_ID">Student ID</label>
                    <i class="fas fa-id-card form-icon"></i>
                    <input type="text" id="student_ID" name="student_ID" class="form-control" placeholder="Enter your student ID" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="links-container">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
