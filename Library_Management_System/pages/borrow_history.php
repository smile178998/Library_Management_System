<?php

session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once "../templates/config.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record_id'])) {
    $recordId = intval($_POST['delete_record_id']);
    
   
    $currentUserSql = "SELECT User_ID FROM user WHERE username = :username";
    $currentUserStmt = $pdo->prepare($currentUserSql);
    $currentUserStmt->execute(['username' => $_SESSION['username']]);
    $currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
    $currentUserId = $currentUser['User_ID'];
    
  
    $checkRecordSql = "SELECT User_ID FROM borrow_record WHERE Record_ID = :Record_ID";
    $checkRecordStmt = $pdo->prepare($checkRecordSql);
    $checkRecordStmt->execute(['Record_ID' => $recordId]);
    $record = $checkRecordStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record && $record['User_ID'] == $currentUserId) {
        try {
            $deleteSql = "DELETE FROM borrow_record WHERE Record_ID = :Record_ID";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute(['Record_ID' => $recordId]);
            $deleteMessage = "Record deleted successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $deleteMessage = "Error deleting record: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $deleteMessage = "You can only delete your own records.";
        $messageType = "error";
    }
}


try {
  
    $currentUserSql = "SELECT User_ID FROM user WHERE username = :username";
    $currentUserStmt = $pdo->prepare($currentUserSql);
    $currentUserStmt->execute(['username' => $_SESSION['username']]);
    $currentUser = $currentUserStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentUser) {
        $currentUserId = $currentUser['User_ID'];
        
       
        $sql = "SELECT br.*, b.Bookname, u.username 
                FROM borrow_record br
                LEFT JOIN book b ON br.Book_ID = b.Book_ID
                LEFT JOIN user u ON br.User_ID = u.User_ID
                WHERE br.User_ID = :user_id
                ORDER BY br.Borrow_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $currentUserId]);
        $borrowHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $borrowHistory = [];
        $deleteMessage = "Error: User information not found.";
        $messageType = "error";
    }
} catch (Exception $e) {
    die("Error fetching borrow history: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Borrow History</title>
   
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
        
        
        .history-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .history-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-header-left {
            display: flex;
            align-items: center;
        }
        
        .history-header-left i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .history-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .history-filters {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .filter-input {
            flex: 1;
            position: relative;
        }
        
        .filter-input input {
            width: 100%;
            padding: 8px 10px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .filter-input i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
       
        .history-records {
            padding: 0;
        }
        
        .record-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .record-item:last-child {
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
        
        .record-details {
            flex: 1;
        }
        
        .record-book {
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .record-user {
            color: #555;
            margin-bottom: 5px;
        }
        
        .record-dates {
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
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .status-returned {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .status-overdue {
            background-color: #ffebee;
            color: #e53935;
        }
        
        .status-active {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .record-actions {
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
            .record-dates {
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
            .record-item {
                flex-direction: column;
            }
            .book-thumbnail {
                margin-bottom: 10px;
            }
            .record-actions {
                margin-left: 0;
                margin-top: 10px;
                align-self: flex-end;
            }
            .history-filters {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
 
    <header>
        <div class="brand-logo">Library Management</div>
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
            <a href="borrow_history.php" class="menu-item active">
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
            <h4><i class="fas fa-history"></i> Borrow History</h4>
            
            <?php if (!empty($deleteMessage)): ?>
                <div class="alert <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($deleteMessage); ?>
                </div>
            <?php endif; ?>


            <div class="history-container">
                <div class="history-header">
                    <div class="history-header-left">
                        <i class="fas fa-book-reader"></i>
                        <h5>My Borrowing Records</h5>
                    </div>
                    <div class="history-stats">
                        <span class="badge white-text"><?php echo count($borrowHistory); ?> Records</span>
                    </div>
                </div>
                
                <div class="history-filters">
                    <div class="filter-input">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by book name...">
                    </div>
                    <div class="filter-input">
                        <i class="fas fa-filter"></i>
                        <select id="statusFilter" class="browser-default">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="returned">Returned</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                
                <div class="history-records">
                    <?php if (empty($borrowHistory)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>No borrowing records found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrowHistory as $record): 
                       
                            $status = '';
                            $statusClass = '';
                            $today = new DateTime();
                            $returnDate = new DateTime($record['Return_date']);
                            
                            if (!empty($record['Actual_return_date'])) {
                                $status = 'Returned';
                                $statusClass = 'status-returned';
                            } elseif ($returnDate < $today) {
                                $status = 'Overdue';
                                $statusClass = 'status-overdue';
                            } else {
                                $status = 'Active';
                                $statusClass = 'status-active';
                            }
                            
                           
                            $imagePath = "";
                            $bookId = $record['Book_ID'];
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
                            <div class="record-item" data-status="<?php echo strtolower($status); ?>">
                                <div class="book-thumbnail">
                                    <?php if (!empty($imagePath)): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                    <?php else: ?>
                                        <i class="material-icons">menu_book</i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="record-details">
                                    <div class="record-book">
                                        <?php echo htmlspecialchars($record['Bookname'] ?? 'Unknown Book'); ?>
                                    </div>
                                    
                                    <div class="record-dates">
                                        <div class="date-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($record['Borrow_date'])); ?></span>
                                        </div>
                                        
                                        <div class="date-item">
                                            <i class="fas fa-calendar-times"></i>
                                            <span>Due: <?php echo date('M d, Y', strtotime($record['Return_date'])); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($record['Actual_return_date'])): ?>
                                        <div class="date-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>Returned: <?php echo date('M d, Y', strtotime($record['Actual_return_date'])); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $status; ?>
                                    </div>
                                </div>
                                
                                <div class="record-actions">
                                    <form method="POST" action="borrow_history.php" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                        <input type="hidden" name="delete_record_id" value="<?php echo htmlspecialchars($record['Record_ID']); ?>">
                                        <button type="submit" class="delete-btn waves-effect">
                                            <i class="fas fa-trash-alt"></i> Delete
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
            
           
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const recordItems = document.querySelectorAll('.record-item');
            
            function filterRecords() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                
                let visibleCount = 0;
                
                recordItems.forEach(item => {
                    const bookName = item.querySelector('.record-book').textContent.toLowerCase();
                    const itemStatus = item.getAttribute('data-status');
                    
                    const matchesSearch = bookName.includes(searchTerm);
                    const matchesStatus = statusValue === 'all' || itemStatus === statusValue;
                    
                    if (matchesSearch && matchesStatus) {
                        item.style.display = 'flex';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                let emptyState = document.querySelector('.empty-filter-state');
                if (visibleCount === 0 && recordItems.length > 0) {
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'empty-state empty-filter-state';
                        emptyState.innerHTML = `
                            <i class="fas fa-filter"></i>
                            <p>No records match your search criteria.</p>
                        `;
                        document.querySelector('.history-records').appendChild(emptyState);
                    }
                } else if (emptyState) {
                    emptyState.remove();
                }
            }
            
            searchInput.addEventListener('input', filterRecords);
            statusFilter.addEventListener('change', filterRecords);
        });
    </script>
</body>
</html>
