<?php

session_start();

 
if (isset($_SESSION['admin_name'])) {
    header("Location: admin_dashboard.php"); 
    exit();
}


require_once "../templates/config.php";


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_email = trim($_POST['admin_email']);
    $password = trim($_POST['password']);

    try {
       
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE Admin_Email = :admin_email");
        $stmt->bindParam(':admin_email', $admin_email, PDO::PARAM_STR);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

       
        if ($admin && password_verify($password, $admin['Password'])) {
         
            $_SESSION['admin_name'] = $admin['Admin_name'];
            $_SESSION['admin_email'] = $admin['Admin_Email'];
            $_SESSION['admin_id'] = $admin['Admin_ID'];

           
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } catch (Exception $e) {
        $error = "Error connecting to the database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Library Management System</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
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
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
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
            border-color: #343a40;
            box-shadow: 0 0 0 3px rgba(52, 58, 64, 0.1);
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
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
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
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            display: block;
            width: 100%;
            padding: 15px;
            background: #f8f9fa;
            color: #343a40;
            border: 1px solid #e1e1e1;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #dc3545;
            animation: shake 0.5s ease-in-out;
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
                <i class="fas fa-user-shield"></i>
            </div>
            <h2>Admin Login</h2>
            <p>Library Management System</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label for="admin_email">Email Address</label>
                    <i class="fas fa-envelope form-icon"></i>
                    <input id="admin_email" type="email" name="admin_email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock form-icon"></i>
                    <input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
            
           
            <a href="login.php" class="btn-secondary">
                <i class="fas fa-user me-2"></i> User Login
            </a>
        </div>
    </div>
</body>
</html>