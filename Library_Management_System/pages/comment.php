<?php

session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once "../templates/config.php";


$message = '';
$message_type = '';


$filter_book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $User_ID = htmlspecialchars($_POST['User_ID']);
    $Book_ID = htmlspecialchars($_POST['Book_ID']);
    $Comment_text = htmlspecialchars($_POST['Comment_text']);

    if (empty($Comment_text)) {
        $message = 'Comment text cannot be empty!';
        $message_type = 'error';
    } else {
        try {
            
            $sql = "INSERT INTO comment (User_ID, Book_ID, Comment_text, created_at) 
                    VALUES (:User_ID, :Book_ID, :Comment_text, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'User_ID' => $User_ID,
                'Book_ID' => $Book_ID,
                'Comment_text' => $Comment_text
            ]);

            $message = 'Comment added successfully!';
            $message_type = 'success';
            
           
            header("Location: comment.php?book_id=" . $Book_ID . "&success=1");
            exit();
        } catch (Exception $e) {
            $message = 'Error adding comment: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $Comment_ID = intval($_POST['delete_comment_id']);
    $redirect_book_id = isset($_POST['redirect_book_id']) ? intval($_POST['redirect_book_id']) : null;
    
    try {
       
        $checkSql = "SELECT User_ID FROM comment WHERE Comment_ID = :Comment_ID";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['Comment_ID' => $Comment_ID]);
        $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment && $comment['User_ID'] === $_SESSION['username']) {
          
            $deleteSql = "DELETE FROM comment WHERE Comment_ID = :Comment_ID";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute(['Comment_ID' => $Comment_ID]);
            
          
            $redirect_url = 'comment.php';
            if ($redirect_book_id) {
                $redirect_url .= '?book_id=' . $redirect_book_id;
            }
            $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'delete_success=1';
            
            header("Location: " . $redirect_url);
            exit();
        } else {
          
            $message = 'You can only delete your own comments!';
            $message_type = 'error';
        }
    } catch (Exception $e) {
        $message = 'Error deleting comment: ' . $e->getMessage();
        $message_type = 'error';
    }
}


if (isset($_GET['success'])) {
    $message = 'Comment added successfully!';
    $message_type = 'success';
}

if (isset($_GET['delete_success'])) {
    $message = 'Comment deleted successfully!';
    $message_type = 'success';
}


try {
    $bookSql = "SELECT b.Book_ID, b.Bookname, b.Authors, b.Copies_available, 
                       COUNT(c.Comment_ID) AS comment_count
                FROM book b
                LEFT JOIN comment c ON b.Book_ID = c.Book_ID";
    

    if (!empty($search_query)) {
        $bookSql .= " WHERE b.Bookname LIKE :search OR b.Authors LIKE :search";
    }
    
    $bookSql .= " GROUP BY b.Book_ID ORDER BY b.Bookname";
    
    $bookStmt = $pdo->prepare($bookSql);
    
    if (!empty($search_query)) {
        $searchParam = '%' . $search_query . '%';
        $bookStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $bookStmt->execute();
    $books = $bookStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching books: " . $e->getMessage());
}


$selected_book = null;
if ($filter_book_id) {
    try {
        $bookDetailSql = "SELECT Book_ID, Bookname, Authors, Copies_available FROM book WHERE Book_ID = :Book_ID";
        $bookDetailStmt = $pdo->prepare($bookDetailSql);
        $bookDetailStmt->execute(['Book_ID' => $filter_book_id]);
        $selected_book = $bookDetailStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Error fetching book details: " . $e->getMessage());
    }
}


$comments = [];
if ($filter_book_id) {
    try {
        $sql = "SELECT c.Comment_ID, c.User_ID, c.Book_ID, c.Comment_text, u.username, b.Bookname,
                       b.Book_ID as book_id_for_image, c.created_at
                FROM comment c
                LEFT JOIN user u ON c.User_ID = u.User_ID
                LEFT JOIN book b ON c.Book_ID = b.Book_ID
                WHERE c.Book_ID = :Book_ID
                ORDER BY c.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['Book_ID' => $filter_book_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Error fetching comments: " . $e->getMessage());
    }
}


$recentComments = [];
if (!$filter_book_id) {
    try {
        $sql = "SELECT c.Comment_ID, c.User_ID, c.Book_ID, c.Comment_text, u.username, b.Bookname,
                       b.Book_ID as book_id_for_image, c.created_at
                FROM comment c
                LEFT JOIN user u ON c.User_ID = u.User_ID
                LEFT JOIN book b ON c.Book_ID = b.Book_ID
                ORDER BY c.created_at DESC
                LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $recentComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Error fetching recent comments: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Comments</title>

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
        
       
        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .book-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .book-cover {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }
        
        .book-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            line-height: 1.3;
        }
        
        .book-author {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            font-style: italic;
        }
        
        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            font-size: 0.85rem;
        }
        
        .comment-count {
            display: flex;
            align-items: center;
            color: #666;
        }
        
        .comment-count i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .book-action {
            margin-top: 10px;
            text-align: center;
        }
        
     
        .comment-form-container {
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
        
    
        .comments-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .comments-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comments-header-left {
            display: flex;
            align-items: center;
        }
        
        .comments-header-left i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .comments-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .comment-list {
            padding: 0;
        }
        
        .comment-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .comment-item:last-child {
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
        
        .comment-content {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .comment-user {
            font-weight: 500;
            color: #333;
        }
        
        .comment-book {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .comment-text {
            color: #555;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .comment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #777;
        }
        
        .comment-actions {
            display: flex;
            gap: 10px;
        }
        
        .delete-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
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
        
        .delete-btn.disabled {
            background-color: #e0e0e0;
            color: #9e9e9e;
            cursor: not-allowed;
        }
        
        .delete-btn.disabled:hover {
            background-color: #e0e0e0;
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
        
     
        .book-detail-header {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .book-detail-cover {
            width: 120px;
            height: 160px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .book-detail-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-detail-info {
            flex: 1;
        }
        
        .book-detail-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .book-detail-author {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .book-detail-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .book-detail-meta-item {
            display: flex;
            align-items: center;
            color: #555;
            font-size: 0.9rem;
        }
        
        .book-detail-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .book-detail-actions {
            display: flex;
            gap: 10px;
        }
        
      
        .search-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            position: relative;
        }
        
        .search-input i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }
        
        .search-input input {
            padding-left: 35px !important;
            border-radius: 4px;
            border: 1px solid #ddd;
            height: 40px;
            box-sizing: border-box;
        }
        
        
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .content {
                margin-left: 200px;
            }
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
            .book-detail-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .book-detail-cover {
                margin-bottom: 15px;
                margin-right: 0;
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
            .comment-item {
                flex-direction: column;
            }
            .book-thumbnail {
                margin-bottom: 10px;
            }
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
            .search-form {
                flex-direction: column;
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
            <a href="reservation.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i> Reservation
            </a>
            <a href="comment.php" class="menu-item active">
                <i class="fas fa-comment"></i> Comment
            </a>
        </div>

      
        <div class="content">
            <h4><i class="fas fa-comment"></i> Book Comments</h4>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($filter_book_id && $selected_book): ?>
          
                <div class="book-detail-header">
                    <?php 
                    
                    $imagePath = "";
                    $bookId = $selected_book['Book_ID'];
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
                    <div class="book-detail-cover">
                        <?php if (!empty($imagePath)): ?>
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="material-icons">menu_book</i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="book-detail-info">
                        <h5 class="book-detail-title"><?php echo htmlspecialchars($selected_book['Bookname']); ?></h5>
                        <div class="book-detail-author">by <?php echo htmlspecialchars($selected_book['Authors']); ?></div>
                        <div class="book-detail-meta">
                            <div class="book-detail-meta-item">
                                <i class="fas fa-book"></i>
                                <span>Copies Available: <?php echo htmlspecialchars($selected_book['Copies_available']); ?></span>
                            </div>
                            <div class="book-detail-meta-item">
                                <i class="fas fa-comments"></i>
                                <span><?php echo count($comments); ?> Comments</span>
                            </div>
                        </div>
                        <div class="book-detail-actions">
                            <a href="comment.php" class="btn waves-effect waves-light blue">
                                <i class="fas fa-arrow-left"></i> Back to All Books
                            </a>
                            <a href="borrow.php?book_id=<?php echo $selected_book['Book_ID']; ?>" class="btn waves-effect waves-light">
                                <i class="fas fa-clipboard-list"></i> Borrow
                            </a>
                        </div>
                    </div>
                </div>

                
                <div class="comment-form-container">
                    <div class="form-header">
                        <i class="fas fa-plus-circle"></i>
                        <h5>Add Your Comment</h5>
                    </div>
                    <form method="POST" action="comment.php?book_id=<?php echo $filter_book_id; ?>">
                        <input type="hidden" name="User_ID" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                        <input type="hidden" name="Book_ID" value="<?php echo htmlspecialchars($filter_book_id); ?>">
                        
                        <div class="row">
                            <div class="input-field col s12">
                                <textarea id="Comment_text" name="Comment_text" class="materialize-textarea" required></textarea>
                                <label for="Comment_text">Your Comment</label>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col s12">
                                <button type="submit" name="submit_comment" class="btn waves-effect waves-light">
                                    <i class="material-icons left">send</i>Post Comment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

              
                <div class="comments-container">
                    <div class="comments-header">
                        <div class="comments-header-left">
                            <i class="fas fa-comments"></i>
                            <h5>Comments for "<?php echo htmlspecialchars($selected_book['Bookname']); ?>"</h5>
                        </div>
                        <div class="comments-stats">
                            <span class="badge white-text"><?php echo count($comments); ?> Comments</span>
                        </div>
                    </div>
                    
                    <div class="comment-list">
                        <?php if (empty($comments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <p>No comments yet for this book.</p>
                                <p>Be the first to share your thoughts!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item" id="comment-<?php echo $comment['Comment_ID']; ?>">
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <span class="comment-user">
                                                <i class="fas fa-user-circle"></i> 
                                                <?php echo htmlspecialchars($comment['username'] ?? 'Unknown User'); ?>
                                            </span>
                                            <div class="comment-actions">
                                                <?php if ($comment['User_ID'] === $_SESSION['username']): ?>
                                                    <form method="POST" action="comment.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                        <input type="hidden" name="delete_comment_id" value="<?php echo htmlspecialchars($comment['Comment_ID']); ?>">
                                                        <input type="hidden" name="redirect_book_id" value="<?php echo htmlspecialchars($filter_book_id); ?>">
                                                        <button type="submit" class="delete-btn waves-effect">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="delete-btn disabled" disabled title="You can only delete your own comments">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="comment-text">
                                            <?php echo htmlspecialchars($comment['Comment_text']); ?>
                                        </div>
                                        
                                        <div class="comment-footer">
                                            <span class="comment-date">
                                                <i class="fas fa-clock"></i> 
                                                <?php 
                                                if (isset($comment['created_at'])) {
                                                    echo date('F j, Y, g:i a', strtotime($comment['created_at']));
                                                } else {
                                                    echo 'Date not available';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
              
                <div class="search-section">
                    <form method="GET" action="comment.php" class="search-form">
                        <div class="search-input">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search books by title or author..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <button type="submit" class="btn waves-effect waves-light">Search</button>
                        <?php if (!empty($search_query)): ?>
                            <a href="comment.php" class="btn waves-effect waves-light red lighten-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (!empty($recentComments)): ?>
               
                    <div class="comments-container">
                        <div class="comments-header">
                            <div class="comments-header-left">
                                <i class="fas fa-clock"></i>
                                <h5>Recent Comments</h5>
                            </div>
                        </div>
                        
                        <div class="comment-list">
                            <?php foreach ($recentComments as $comment): ?>
                                <?php 
                              
                                $imagePath = "";
                                $bookId = isset($comment['book_id_for_image']) ? $comment['book_id_for_image'] : $comment['Book_ID'];
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
                                <div class="comment-item">
                                    <div class="book-thumbnail">
                                        <a href="comment.php?book_id=<?php echo htmlspecialchars($comment['Book_ID']); ?>">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                            <?php else: ?>
                                                <i class="material-icons">menu_book</i>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <span class="comment-user">
                                                <i class="fas fa-user-circle"></i> 
                                                <?php echo htmlspecialchars($comment['username'] ?? 'Unknown User'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="comment-book">
                                            <i class="fas fa-book"></i> 
                                            <a href="comment.php?book_id=<?php echo htmlspecialchars($comment['Book_ID']); ?>">
                                                <?php echo htmlspecialchars($comment['Bookname'] ?? 'Unknown Book'); ?>
                                            </a>
                                        </div>
                                        
                                        <div class="comment-text">
                                            <?php echo htmlspecialchars($comment['Comment_text']); ?>
                                        </div>
                                        
                                        <div class="comment-footer">
                                            <span class="comment-date">
                                                <i class="fas fa-clock"></i> 
                                                <?php 
                                                if (isset($comment['created_at'])) {
                                                    echo date('F j, Y, g:i a', strtotime($comment['created_at']));
                                                } else {
                                                    echo 'Date not available';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

         
                <h5><i class="fas fa-book"></i> All Books</h5>
                
                <?php if (empty($books)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <p>No books found. Try a different search term.</p>
                    </div>
                <?php else: ?>
                    <div class="book-grid">
                        <?php foreach ($books as $book): ?>
                            <?php 
                        
                            $imagePath = "";
                            $bookId = $book['Book_ID'];
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
                            <div class="book-card">
                                <div class="book-cover">
                                    <a href="comment.php?book_id=<?php echo htmlspecialchars($book['Book_ID']); ?>">
                                        <?php if (!empty($imagePath)): ?>
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="material-icons">menu_book</i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="book-info">
                                    <div class="book-title"><?php echo htmlspecialchars($book['Bookname']); ?></div>
                                    <div class="book-author"><?php echo htmlspecialchars($book['Authors'] ?? 'Unknown Author'); ?></div>
                                    <div class="book-meta">
                                        <div class="comment-count">
                                            <i class="fas fa-comments"></i> <?php echo $book['comment_count']; ?> comments
                                        </div>
                                        <div class="copies-available">
                                            <?php echo $book['Copies_available']; ?> available
                                        </div>
                                    </div>
                                    <div class="book-action">
                                        <a href="comment.php?book_id=<?php echo htmlspecialchars($book['Book_ID']); ?>" class="btn-small waves-effect waves-light">
                                            View Comments
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

   
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
           
            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);
            
            var textareas = document.querySelectorAll('.materialize-textarea');
            M.textareaAutoResize(textareas);
            
            
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        });
    </script>
</body>
</html>
