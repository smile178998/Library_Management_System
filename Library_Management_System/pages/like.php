<?php

session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once "../templates/config.php";


$username = $_SESSION['username'];
$userIdQuery = "SELECT User_ID FROM user WHERE username = :username";
$userIdStmt = $pdo->prepare($userIdQuery);
$userIdStmt->execute(['username' => $username]);
$userData = $userIdStmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
 
    die("User not found in database");
}

$User_ID = $userData['User_ID']; 


$sql = "SELECT b.*, 
               (SELECT COUNT(*) FROM book_like WHERE Book_ID = b.Book_ID) as like_count,
               GROUP_CONCAT(c.Categories_name SEPARATOR ', ') AS Categories
        FROM book b
        LEFT JOIN book_category bc ON b.Book_ID = bc.Book_ID
        LEFT JOIN category c ON bc.Categories_ID = c.Categories_ID
        GROUP BY b.Book_ID
        ORDER BY like_count DESC, b.Bookname";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);


$likedBooksSql = "SELECT Book_ID FROM book_like WHERE User_ID = :User_ID";
$likedBooksStmt = $pdo->prepare($likedBooksSql);
$likedBooksStmt->execute(['User_ID' => $User_ID]);
$likedBooks = $likedBooksStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Book_ID'], $_POST['Action'])) {
    $Book_ID = (int)$_POST['Book_ID'];
    $action = $_POST['Action'];

    try {
        if ($action === 'like') {
      
            if (in_array($Book_ID, $likedBooks)) {
                $error = "You have already liked this book.";
            } else {
        
                $likeSql = "INSERT INTO book_like (Book_ID, User_ID, Liked_At) VALUES (:Book_ID, :User_ID, NOW())";
                $likeStmt = $pdo->prepare($likeSql);
                $likeStmt->execute(['Book_ID' => $Book_ID, 'User_ID' => $User_ID]);

         
                $likedBooks[] = $Book_ID;
                $success = "Book added to your favorites!";
            }
        } elseif ($action === 'unlike') {
      
            if (in_array($Book_ID, $likedBooks)) {
          
                $unlikeSql = "DELETE FROM book_like WHERE Book_ID = :Book_ID AND User_ID = :User_ID";
                $unlikeStmt = $pdo->prepare($unlikeSql);
                $unlikeStmt->execute(['Book_ID' => $Book_ID, 'User_ID' => $User_ID]);

         
                $likedBooks = array_diff($likedBooks, [$Book_ID]);
                $success = "Book removed from your favorites.";
            } else {
                $error = "You have not liked this book.";
            }
        }

 
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => !isset($error),
                'message' => isset($error) ? $error : $success,
                'liked' => in_array($Book_ID, $likedBooks)
            ]);
            exit();
        }

    
        header("Location: like.php?success=" . urlencode($success));
        exit();
    } catch (Exception $e) {
        $error = "Failed to process your request: " . $e->getMessage();
        
     
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit();
        }
    }
}


$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Like Books</title>
    
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
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .book-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .book-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .book-image.no-image {
            background-color: #e0e0e0;
        }
        
        .like-count {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .book-details {
            padding: 15px;
        }
        
        .book-title {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        
        .book-author {
            color: #666;
            margin-bottom: 10px;
        }
        
        .book-meta {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 5px;
        }
        
        .book-categories {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .category-tag {
            background-color: #e0f2f1;
            color: #00897b;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .like-button {
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .like-button.liked {
            background-color: #e91e63;
        }
        
        .like-button.liked:hover {
            background-color: #c2185b;
        }
        
        
        .filter-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .filter-header i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .filter-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-chip {
            background-color: #f0f0f0;
            border-radius: 16px;
            padding: 5px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-chip:hover {
            background-color: #e0e0e0;
        }
        
        .filter-chip.active {
            background-color: var(--primary-color);
            color: white;
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
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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
            <a href="borrow_history.php" class="menu-item">
                <i class="fas fa-history"></i> Borrow History
            </a>
            <a href="like.php" class="menu-item active">
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
            <h4><i class="fas fa-heart"></i> Favorite Books</h4>
            
            <?php if (!empty($success)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
          
            <div class="filter-section">
                <div class="filter-header">
                    <i class="fas fa-filter"></i>
                    <h5>Filter Books</h5>
                </div>
                <div class="filter-options">
                    <div class="filter-chip active" data-filter="all">All Books</div>
                    <div class="filter-chip" data-filter="liked">My Favorites</div>
                    <div class="filter-chip" data-filter="popular">Most Popular</div>
                </div>
            </div>
            
          
            <div class="book-grid">
                <?php if (empty($books)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <p>No books found in the library.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): 
                        $isLiked = in_array($book['Book_ID'], $likedBooks);
                        $likeCount = isset($book['like_count']) ? (int)$book['like_count'] : 0;
                        
                        
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
                        <div class="book-card" data-liked="<?php echo $isLiked ? 'true' : 'false'; ?>" data-likes="<?php echo $likeCount; ?>">
                            <div class="book-image <?php echo empty($imagePath) ? 'no-image' : ''; ?>" 
                                 <?php if (!empty($imagePath)): ?>
                                 style="background-image: url('<?php echo htmlspecialchars($imagePath); ?>');"
                                 <?php endif; ?>>
                                <?php if (empty($imagePath)): ?>
                                    <i class="material-icons" style="font-size: 48px; color: #aaa;">menu_book</i>
                                <?php endif; ?>
                                
                                <div class="like-count">
                                    <i class="fas fa-heart"></i> <?php echo $likeCount; ?>
                                </div>
                            </div>
                            
                            <div class="book-details">
                                <div class="book-title"><?php echo htmlspecialchars($book['Bookname']); ?></div>
                                <div class="book-author">by <?php echo htmlspecialchars($book['Authors']); ?></div>
                                
                                <div class="book-meta">
                                    <strong>Publisher:</strong> <?php echo htmlspecialchars($book['Publishers']); ?>
                                </div>
                                
                                <div class="book-meta">
                                    <strong>Published:</strong> <?php echo htmlspecialchars($book['Publication_date']); ?>
                                </div>
                                
                                <?php if (!empty($book['Categories'])): ?>
                                <div class="book-categories">
                                    <?php 
                                    $categoryArray = explode(', ', $book['Categories']);
                                    foreach ($categoryArray as $cat): 
                                    ?>
                                        <span class="category-tag"><?php echo htmlspecialchars($cat); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <form class="like-form" data-book-id="<?php echo htmlspecialchars($book['Book_ID']); ?>">
                                    <input type="hidden" name="Book_ID" value="<?php echo htmlspecialchars($book['Book_ID']); ?>">
                                    <input type="hidden" name="Action" value="<?php echo $isLiked ? 'unlike' : 'like'; ?>">
                                    <button type="submit" class="btn like-button waves-effect waves-light <?php echo $isLiked ? 'liked' : ''; ?>">
                                        <i class="fas <?php echo $isLiked ? 'fa-heart-broken' : 'fa-heart'; ?>"></i>
                                        <?php echo $isLiked ? 'Unlike' : 'Like'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
          
            const likeForms = document.querySelectorAll('.like-form');
            likeForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const bookId = this.getAttribute('data-book-id');
                    const formData = new FormData(this);
                    
                    fetch('like.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                         
                            const button = this.querySelector('.like-button');
                            const icon = button.querySelector('i');
                            const bookCard = this.closest('.book-card');
                            const likeCountElement = bookCard.querySelector('.like-count');
                            
                            if (data.liked) {
                                button.classList.add('liked');
                                button.innerHTML = '<i class="fas fa-heart-broken"></i> Unlike';
                                bookCard.setAttribute('data-liked', 'true');
                                
                          
                                let count = parseInt(likeCountElement.textContent.trim());
                                likeCountElement.innerHTML = '<i class="fas fa-heart"></i> ' + (count + 1);
                                bookCard.setAttribute('data-likes', count + 1);
                            } else {
                                button.classList.remove('liked');
                                button.innerHTML = '<i class="fas fa-heart"></i> Like';
                                bookCard.setAttribute('data-liked', 'false');
                                
                          
                                let count = parseInt(likeCountElement.textContent.trim());
                                likeCountElement.innerHTML = '<i class="fas fa-heart"></i> ' + (count - 1);
                                bookCard.setAttribute('data-likes', count - 1);
                            }
                            
                       
                            this.querySelector('input[name="Action"]').value = data.liked ? 'unlike' : 'like';
                            
                           
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert success';
                            successAlert.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                            
                            const content = document.querySelector('.content');
                            content.insertBefore(successAlert, content.firstChild.nextSibling);
                            
                       
                            setTimeout(() => {
                                successAlert.remove();
                            }, 3000);
                        } else {
                            
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert error';
                            errorAlert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                            
                            const content = document.querySelector('.content');
                            content.insertBefore(errorAlert, content.firstChild.nextSibling);
                            
                      
                            setTimeout(() => {
                                errorAlert.remove();
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
            
        
            const filterChips = document.querySelectorAll('.filter-chip');
            const bookCards = document.querySelectorAll('.book-card');
            
            filterChips.forEach(chip => {
                chip.addEventListener('click', function() {
                   
                    filterChips.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
            
                    bookCards.forEach(card => {
                        if (filter === 'all') {
                            card.style.display = 'block';
                        } else if (filter === 'liked') {
                            card.style.display = card.getAttribute('data-liked') === 'true' ? 'block' : 'none';
                        } else if (filter === 'popular') {
                        
                            const likes = parseInt(card.getAttribute('data-likes'));
                            card.style.display = likes > 0 ? 'block' : 'none';
                        }
                    });
                    
       
                    let visibleBooks = 0;
                    bookCards.forEach(card => {
                        if (card.style.display !== 'none') {
                            visibleBooks++;
                        }
                    });
                    
           
                    let emptyState = document.querySelector('.empty-filter-state');
                    if (visibleBooks === 0) {
                        if (!emptyState) {
                            emptyState = document.createElement('div');
                            emptyState.className = 'empty-state empty-filter-state';
                            emptyState.innerHTML = `
                                <i class="fas fa-filter"></i>
                                <p>No books match the selected filter.</p>
                            `;
                            document.querySelector('.book-grid').appendChild(emptyState);
                        }
                    } else if (emptyState) {
                        emptyState.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>
