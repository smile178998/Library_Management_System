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
    die("Error fetching user data: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Borrower_Name'], $_POST['Student_ID'], $_POST['Book_ID'])) {
    $Borrower_Name = htmlspecialchars($_POST['Borrower_Name']);
    $Student_ID = htmlspecialchars($_POST['Student_ID']);
    $Book_ID = htmlspecialchars($_POST['Book_ID']);

    try {
        
        $userCheckSql = "SELECT * FROM user WHERE user_ID = :user_ID";
        $userCheckStmt = $pdo->prepare($userCheckSql);
        $userCheckStmt->execute(['user_ID' => $Student_ID]);
        $user = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
           
            $message = 'Borrowing failed! The provided Student ID does not exist in the system.';
            $message_type = 'error';
        } else {
          
            $bookCheckSql = "SELECT * FROM book WHERE Book_ID = :Book_ID";
            $bookCheckStmt = $pdo->prepare($bookCheckSql);
            $bookCheckStmt->execute(['Book_ID' => $Book_ID]);
            $book = $bookCheckStmt->fetch(PDO::FETCH_ASSOC);

            if (!$book) {
           
                $message = 'Borrowing failed! The book does not exist.';
                $message_type = 'error';
            } elseif ($book['Copies_available'] <= 0) {
             
                $message = 'Borrowing failed! No copies of this book are currently available.';
                $message_type = 'error';
            } else {
           
                $borrowCheckSql = "SELECT COUNT(*) as count FROM borrow_record 
                                  WHERE User_ID = :User_ID AND Book_ID = :Book_ID AND Actual_return_date IS NULL";
                $borrowCheckStmt = $pdo->prepare($borrowCheckSql);
                $borrowCheckStmt->execute([
                    'User_ID' => $Student_ID,
                    'Book_ID' => $Book_ID
                ]);
                $borrowCheck = $borrowCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($borrowCheck['count'] > 0) {
                    $message = 'Borrowing failed! This user already has this book borrowed.';
                    $message_type = 'error';
                } else {
                 
                    $insertSql = "INSERT INTO borrow_record (User_ID, Book_ID, Borrow_date, Return_date, Borrower_Name) 
                                VALUES (:User_ID, :Book_ID, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), :Borrower_Name)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        'User_ID' => $Student_ID,
                        'Book_ID' => $Book_ID,
                        'Borrower_Name' => $Borrower_Name
                    ]);
                    
                    
                    $updateBookSql = "UPDATE book SET Copies_available = Copies_available - 1 WHERE Book_ID = :Book_ID";
                    $updateBookStmt = $pdo->prepare($updateBookSql);
                    $updateBookStmt->execute(['Book_ID' => $Book_ID]);

                    $message = 'Book borrowed successfully! Return date is in 14 days.';
                    $message_type = 'success';
                }
            }
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
        $message_type = 'error';
    }
}


try {
    $bookSql = "SELECT Book_ID, Bookname, Authors, Copies_available FROM book WHERE Copies_available > 0 ORDER BY Bookname";
    $bookStmt = $pdo->prepare($bookSql);
    $bookStmt->execute();
    $availableBooks = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching books: " . $e->getMessage());
}


try {
    $userSql = "SELECT User_ID, username FROM user ORDER BY username";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute();
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching users: " . $e->getMessage());
}


$displayUserId = $currentUserId;


try {
    $currentUserSql = "SELECT br.*, b.Bookname, b.Authors, b.Book_ID as book_id_for_image
                      FROM borrow_record br
                      JOIN book b ON br.Book_ID = b.Book_ID
                      WHERE br.User_ID = :User_ID AND br.Actual_return_date IS NULL
                      ORDER BY br.Return_date ASC";
    $currentUserStmt = $pdo->prepare($currentUserSql);
    $currentUserStmt->execute(['User_ID' => $displayUserId]);
    $currentBorrows = $currentUserStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error fetching current borrows: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Borrow</title>

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
        
       
        .borrow-form-container {
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
        
        .form-info {
            background-color: #e3f2fd;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .form-info i {
            color: #1976d2;
            margin-right: 10px;
            font-size: 20px;
        }
        
       
        .current-borrows-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .borrows-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .borrows-header-left {
            display: flex;
            align-items: center;
        }
        
        .borrows-header-left i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .borrows-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .borrow-list {
            padding: 0;
        }
        
        .borrow-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .borrow-item:last-child {
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
        
        .borrow-details {
            flex: 1;
        }
        
        .borrow-book {
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .borrow-author {
            color: #666;
            margin-bottom: 10px;
            font-style: italic;
        }
        
        .borrow-dates {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 5px;
        }
        
        .date-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .date-item i {
            color: #777;
        }
        
        .due-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .due-soon {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .due-normal {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .due-overdue {
            background-color: #ffebee;
            color: #e53935;
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
        
      
        .book-select-container {
            margin-bottom: 20px;
        }
        
        .book-option {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .book-option-details {
            margin-left: 10px;
        }
        
        .book-option-title {
            font-weight: 500;
        }
        
        .book-option-author {
            font-size: 0.9rem;
            color: #666;
        }
        
        .book-option-copies {
            font-size: 0.8rem;
            color: #43a047;
        }
        
      
        .user-switch {
            margin-top: 10px;
            text-align: right;
        }
        
      
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 200px;
            }
            .borrow-dates {
                flex-direction: column;
                gap: 5px;
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
            .borrow-item {
                flex-direction: column;
            }
            .book-thumbnail {
                margin-bottom: 10px;
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
            <a href="borrow.php" class="menu-item active">
                <i class="fas fa-clipboard-list"></i> Borrow
            </a>
            <a href="borrow_history.php" class="menu-item">
                <i class="fas fa-history"></i> Borrow History
            </a>
            <a href="like.php" class="menu-item">
                <i class="fas fa-heart"></i> Like
            </a>
            <a href="reservation.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Reservation
            </a>
            <a href="comment.php" class="menu-item">
                <i class="fas fa-comment"></i> Comment
            </a>
        </div>

        
        <div class="content">
            <h4><i class="fas fa-clipboard-list"></i> Borrow Books</h4>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

          
            <div class="borrow-form-container">
                <div class="form-header">
                    <i class="fas fa-book-reader"></i>
                    <h5>Borrow a Book</h5>
                </div>
                
                <div class="form-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Books are borrowed for 14 days. Please return them on time to avoid late fees.</span>
                </div>
                
                <form method="POST" action="borrow.php" id="borrowForm">
                    <div class="row">
                        <div class="input-field col s12 m6">
                            <input id="Borrower_Name" type="text" name="Borrower_Name" required>
                            <label for="Borrower_Name">Borrower Name</label>
                        </div>
                        
                        <div class="input-field col s12 m6">
                            <input id="Student_ID" type="text" name="Student_ID" required>
                            <label for="Student_ID">Student ID</label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12">
                            <label for="Book_ID" class="active">Select Book</label>
                            <select id="Book_ID" name="Book_ID" class="browser-default" required>
                                <option value="" disabled selected>Choose a book to borrow</option>
                                <?php foreach ($availableBooks as $book): ?>
                                    <option value="<?php echo htmlspecialchars($book['Book_ID']); ?>">
                                        <?php echo htmlspecialchars($book['Bookname']); ?> by <?php echo htmlspecialchars($book['Authors']); ?> 
                                        (<?php echo htmlspecialchars($book['Copies_available']); ?> available)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col s12">
                            <button type="submit" class="btn waves-effect waves-light">
                                <i class="material-icons left">library_add</i>Borrow Book
                            </button>
                            <a href="borrow_history.php" class="btn blue waves-effect waves-light" style="margin-left: 10px;">
                                <i class="material-icons left">history</i>View History
                            </a>
                        </div>
                    </div>
                </form>
            </div>

           
            <div class="current-borrows-container">
                <div class="borrows-header">
                    <div class="borrows-header-left">
                        <i class="fas fa-book"></i>
                        <h5>Your Current Borrows</h5>
                    </div>
                    <div class="borrows-stats">
                        <span class="badge white-text"><?php echo count($currentBorrows); ?> Books</span>
                    </div>
                </div>
                
                <div class="borrow-list">
                    <?php if (empty($currentBorrows)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <p>You don't have any books borrowed at the moment.</p>
                            <p>Use the form above to borrow books from our library.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($currentBorrows as $borrow): 
                          
                            $dueDate = new DateTime($borrow['Return_date']);
                            $today = new DateTime();
                            $interval = $today->diff($dueDate);
                            $daysRemaining = $interval->invert ? -$interval->days : $interval->days;
                            
                        
                            $dueClass = 'due-normal';
                            $dueText = '';
                            
                            if ($interval->invert) {
                                $dueClass = 'due-overdue';
                                $dueText = 'Overdue by ' . $interval->days . ' days';
                            } elseif ($daysRemaining <= 3) {
                                $dueClass = 'due-soon';
                                $dueText = 'Due in ' . $daysRemaining . ' days';
                            } else {
                                $dueText = 'Due in ' . $daysRemaining . ' days';
                            }
                            
                          
                            $imagePath = "";
                            $bookId = $borrow['Book_ID'];
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
                            <div class="borrow-item">
                                <div class="book-thumbnail">
                                    <?php if (!empty($imagePath)): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                    <?php else: ?>
                                        <i class="material-icons">menu_book</i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="borrow-details">
                                    <div class="borrow-book">
                                        <?php echo htmlspecialchars($borrow['Bookname']); ?>
                                    </div>
                                    
                                    <div class="borrow-author">
                                        by <?php echo htmlspecialchars($borrow['Authors']); ?>
                                    </div>
                                    
                                    <div class="borrow-dates">
                                        <div class="date-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($borrow['Borrow_date'])); ?></span>
                                        </div>
                                        
                                        <div class="date-item">
                                            <i class="fas fa-calendar-times"></i>
                                            <span>Due: <?php echo date('M d, Y', strtotime($borrow['Return_date'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="due-badge <?php echo $dueClass; ?>">
                                        <i class="fas fa-clock"></i> <?php echo $dueText; ?>
                                    </div>
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
            
          
            const bookSelect = document.getElementById('Book_ID');
            if (bookSelect) {
                bookSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const bookId = selectedOption.value;
                    
                   
                    console.log('Selected book ID:', bookId);
                });
            }
        });
    </script>
</body>
</html>
