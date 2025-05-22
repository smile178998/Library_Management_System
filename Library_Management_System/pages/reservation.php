<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once "../templates/config.php";

$message = '';
$message_type = '';


try {
    $userIdQuery = "SELECT User_ID FROM user WHERE username = :username";
    $userIdStmt = $pdo->prepare($userIdQuery);
    $userIdStmt->execute(['username' => $_SESSION['username']]);
    $userData = $userIdStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        die("User not found in database");
    }
    
    $currentUserId = $userData['User_ID'];
} catch (Exception $e) {
    die("Error retrieving user data: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['User_ID'], $_POST['Book_ID'])) {
    $User_ID = htmlspecialchars($_POST['User_ID']);
    $Book_ID = htmlspecialchars($_POST['Book_ID']);
    $Expiry_date = date("Y-m-d", strtotime("+7 days")); 

    try {
       
        $bookSql = "SELECT Bookname, Copies_available FROM book WHERE Book_ID = :Book_ID";
        $bookStmt = $pdo->prepare($bookSql);
        $bookStmt->execute(['Book_ID' => $Book_ID]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            throw new Exception("Book not found.");
        }
        
        if ($book['Copies_available'] <= 0) {
            throw new Exception("No copies available for reservation.");
        }
        
        
        $checkSql = "SELECT COUNT(*) as count FROM reservation WHERE User_ID = :User_ID AND Book_ID = :Book_ID";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'User_ID' => $User_ID,
            'Book_ID' => $Book_ID
        ]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception("You already have a reservation for this book.");
        }
        

        $sql = "INSERT INTO reservation (User_ID, Book_ID, Expiry_date) VALUES (:User_ID, :Book_ID, :Expiry_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'User_ID' => $User_ID,
            'Book_ID' => $Book_ID,
            'Expiry_date' => $Expiry_date
        ]);

        $message = 'Reservation added successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error adding reservation: ' . $e->getMessage();
        $message_type = 'error';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation_id'])) {
    $Reservation_ID = intval($_POST['delete_reservation_id']);
    try {
    
        $checkOwnerSql = "SELECT User_ID FROM reservation WHERE Reservation_ID = :Reservation_ID";
        $checkOwnerStmt = $pdo->prepare($checkOwnerSql);
        $checkOwnerStmt->execute(['Reservation_ID' => $Reservation_ID]);
        $reservation = $checkOwnerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation || $reservation['User_ID'] != $currentUserId) {
            throw new Exception("You can only cancel your own reservations.");
        }
        
        $deleteSql = "DELETE FROM reservation WHERE Reservation_ID = :Reservation_ID";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute(['Reservation_ID' => $Reservation_ID]);
        $message = 'Reservation deleted successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error deleting reservation: ' . $e->getMessage();
        $message_type = 'error';
    }
}


try {
    $bookSql = "SELECT Book_ID, Bookname, Copies_available FROM book WHERE Copies_available > 0 ORDER BY Bookname";
    $bookStmt = $pdo->prepare($bookSql);
    $bookStmt->execute();
    $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching books: " . $e->getMessage());
}


try {
  
    $sql = "SELECT r.Reservation_ID, r.User_ID, r.Book_ID, r.Expiry_date, 
                   b.Bookname, u.username
            FROM reservation r
            LEFT JOIN book b ON r.Book_ID = b.Book_ID
            LEFT JOIN user u ON r.User_ID = u.User_ID
            WHERE r.User_ID = :User_ID
            ORDER BY r.Expiry_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['User_ID' => $currentUserId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching reservations: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Reservation</title>
  
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #26a69a;
            --sidebar-bg: #ffffff;
            --sidebar-active: #26a69a;
            --header-bg: #2c3e50;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        
        .main-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
       
        header {
            background-color: var(--header-bg);
            color: white;
            padding: 15px 20px;
            position: fixed;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        header .brand-logo {
            font-size: 1.5rem;
            font-weight: 500;
        }
        
      
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 64px;
            left: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 900;
        }
        
        .sidebar .menu-item {
            display: block;
            padding: 15px 20px;
            color: #555;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .sidebar .menu-item:hover {
            background-color: rgba(38, 166, 154, 0.1);
            border-left-color: var(--primary-color);
        }
        
        .sidebar .menu-item.active {
            background-color: var(--sidebar-active);
            color: white;
            border-left-color: #1d8c82;
        }
        
        .sidebar .menu-item i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
        }
        
        .content {
            flex: 1;
            margin-left: 250px;
            margin-top: 64px;
            padding: 20px;
        }
        
        
        .reservation-form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-header i {
            font-size: 24px;
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .form-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
            color: #333;
        }
        
        
        .reservations-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .reservations-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
        }
        
        .reservations-header i {
            margin-right: 10px;
        }
        
        .reservations-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .reservation-list {
            padding: 0;
        }
        
        .reservation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .reservation-item:last-child {
            border-bottom: none;
        }
        
        .book-thumbnail {
            width: 60px;
            height: 80px;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .book-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .reservation-details {
            flex: 1;
        }
        
        .reservation-book {
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .reservation-user {
            color: #555;
            margin-bottom: 5px;
        }
        
        .reservation-expiry {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .expiry-soon {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .expiry-normal {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .expiry-expired {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .reservation-actions {
            margin-left: 15px;
        }
        
        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }
        
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        
     
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .alert.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert.error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
      
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
       
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 200px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
            .reservation-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .book-thumbnail {
                margin-bottom: 10px;
            }
            .reservation-actions {
                margin-left: 0;
                margin-top: 10px;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
   
    <header>
        <div class="brand-logo"> Library Management</div>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="../pages/logout.php" class="btn red logout-btn waves-effect waves-light">Logout</a>
        </div>
    </header>

    <div class="main-container">

        <div class="sidebar">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="book.php" class="menu-item">
                <i class="fas fa-book"></i> Book
            </a>
            <a href="borrow.php" class="menu-item">
                <i class="fas fa-clipboard-list"></i> Borrow
            </a>
            <a href="borrow_history.php" class="menu-item">
                <i class="fas fa-history"></i> Borrow History
            </a>
            <a href="like.php" class="menu-item">
                <i class="fas fa-heart"></i> Like
            </a>
            <a href="reservation.php" class="menu-item active">
                <i class="fas fa-calendar-alt"></i> Reservation
            </a>
            <a href="comment.php" class="menu-item">
                <i class="fas fa-comment"></i> Comment
            </a>
        </div>

   
        <div class="content">
            <h4><i class="fas fa-calendar-alt"></i> Reservations</h4>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

         
            <div class="reservation-form-container">
                <div class="form-header">
                    <i class="fas fa-plus-circle"></i>
                    <h5>Reserve a Book</h5>
                </div>
                <form method="POST" action="reservation.php">
                    <div class="row">
                        <div class="input-field col s12 m6">
                            <input id="User_ID" type="text" name="User_ID" value="<?php echo htmlspecialchars($currentUserId); ?>" readonly>
                            <label for="User_ID" class="active">User ID</label>
                        </div>
                        <div class="input-field col s12 m6">
                            <select id="Book_ID" name="Book_ID" class="browser-default" required>
                                <option value="" disabled selected>Select a book</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo htmlspecialchars($book['Book_ID']); ?>">
                                        <?php echo htmlspecialchars($book['Bookname']); ?> 
                                        (<?php echo htmlspecialchars($book['Copies_available']); ?> available)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="Book_ID" class="active">Book</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col s12">
                            <p>Reservation will expire in 7 days</p>
                            <button type="submit" class="btn waves-effect waves-light">
                                <i class="material-icons left">add</i>Reserve Book
                            </button>
                        </div>
                    </div>
                </form>
            </div>

           
            <div class="reservations-container">
                <div class="reservations-header">
                    <i class="fas fa-list"></i>
                    <h5>My Reservations</h5>
                </div>
                
                <div class="reservation-list">
                    <?php if (empty($reservations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>You don't have any reservations yet.</p>
                            <p>Use the form above to reserve a book.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reservations as $reservation): 
                         
                            $expiryDate = new DateTime($reservation['Expiry_date']);
                            $today = new DateTime();
                            $interval = $today->diff($expiryDate);
                            $daysRemaining = $interval->invert ? -$interval->days : $interval->days;
                            
                     
                            $expiryClass = 'expiry-normal';
                            $expiryText = '';
                            
                            if ($interval->invert) {
                                $expiryClass = 'expiry-expired';
                                $expiryText = 'Expired ' . $interval->days . ' days ago';
                            } elseif ($daysRemaining <= 2) {
                                $expiryClass = 'expiry-soon';
                                $expiryText = 'Expires in ' . $daysRemaining . ' days';
                            } else {
                                $expiryText = 'Expires in ' . $daysRemaining . ' days';
                            }
                            
                      
                            $imagePath = "";
                            $bookId = $reservation['Book_ID'];
                            $possibleImages = [
                                "../uploads/book_" . $bookId . ".jpg",
                                "../uploads/book_" . $bookId . ".png",
                                "../uploads/book_" . $bookId . ".jpeg"
                            ];
                            
                            foreach ($possibleImages as $img) {
                                if (file_exists($img)) {
                                    $imagePath = $img;
                                    break;
                                }
                            }
                            
                          
                            if (empty($imagePath)) {
                                $genericImage = "../uploads/book_" . (($bookId % 6) + 1) . ".jpg";
                                if (file_exists($genericImage)) {
                                    $imagePath = $genericImage;
                                }
                            }
                        ?>
                            <div class="reservation-item">
                                <div class="book-thumbnail">
                                    <?php if (!empty($imagePath)): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                    <?php else: ?>
                                        <i class="material-icons">menu_book</i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="reservation-details">
                                    <div class="reservation-book">
                                        <?php echo htmlspecialchars($reservation['Bookname'] ?? 'Unknown Book'); ?>
                                    </div>
                                    
                                    <div class="reservation-expiry <?php echo $expiryClass; ?>">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo $expiryText; ?>
                                    </div>
                                    
                                    <div class="reservation-id">
                                        <small>Reservation ID: <?php echo htmlspecialchars($reservation['Reservation_ID']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="reservation-actions">
                                    <form method="POST" action="reservation.php">
                                        <input type="hidden" name="delete_reservation_id" value="<?php echo htmlspecialchars($reservation['Reservation_ID']); ?>">
                                        <button type="submit" class="delete-btn waves-effect">
                                            <i class="fas fa-trash-alt"></i> Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

  
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
      
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
            
     
            var deleteForms = document.querySelectorAll('.reservation-actions form');
            deleteForms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!confirm('Are you sure you want to cancel this reservation?')) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
