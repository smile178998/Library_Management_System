<?php
session_start();
require_once 'templates/config.php'; 


if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}


$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'user_management', 'book_management', 'borrow_books', 'borrow_records', 'book_reservation', 'like_statistics', 'comment_management'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
   
    <header>
        <div class="navbar">
            <div class="logo">📘 Library Management</div>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
            <a href="pages/logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    
    <div class="main-layout">
        
        <aside class="sidebar">
            <nav>
                <ul>
                    <li><a href="index.php?page=dashboard">Dashboard</a></li>
                    <li><a href="index.php?page=user_management">User Management</a></li>
                    <li><a href="index.php?page=book_management">Book Management</a></li>
                    <li><a href="index.php?page=borrow_books">Borrow Books</a></li>
                    <li><a href="index.php?page=borrow_records">Borrow Records</a></li>
                    <li><a href="index.php?page=book_reservation">Book Reservations</a></li>
                    <li><a href="index.php?page=like_statistics">Like Statistics</a></li>
                    <li><a href="index.php?page=comment_management">Comment Management</a></li>
                </ul>
            </nav>
        </aside>

        
        <main class="content">
            <?php include "pages/{$page}.php"; ?>
        </main>
    </div>
</body>
</html>