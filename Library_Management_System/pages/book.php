<?php

session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once "../templates/config.php";


$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';


try {
    $categorySql = "SELECT * FROM category";
    $categoryStmt = $pdo->prepare($categorySql);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching categories: " . $e->getMessage());
}


try {
    $sql = "SELECT b.*, GROUP_CONCAT(c.Categories_name SEPARATOR ', ') AS Categories 
            FROM book b
            LEFT JOIN book_category bc ON b.Book_ID = bc.Book_ID
            LEFT JOIN category c ON bc.Categories_ID = c.Categories_ID
            WHERE (b.Bookname LIKE :search OR b.Authors LIKE :search)";
   
    if ($categoryFilter) {
        $sql .= " AND EXISTS (SELECT 1 FROM book_category bc WHERE bc.Book_ID = b.Book_ID AND bc.Categories_ID = :category)";
    }
    $sql .= " GROUP BY b.Book_ID";
    $stmt = $pdo->prepare($sql);
    $params = ['search' => "%$searchQuery%"];
    if ($categoryFilter) {
        $params['category'] = $categoryFilter;
    }
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching books: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Books</title>
    
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
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .book-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .book-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .book-image.no-image {
            background-color: #e0e0e0;
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
        
        
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-container .input-field {
            margin-top: 0;
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
            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
        
        
        .copies-available {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .available {
            background-color: #e8f5e9;
            color: #43a047;
        }
        
        .low-stock {
            background-color: #fff8e1;
            color: #ffb300;
        }
        
        .unavailable {
            background-color: #ffebee;
            color: #e53935;
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
            <a href="book.php" class="menu-item active">
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
            <a href="comment.php" class="menu-item">
                <i class="fas fa-comment"></i> Comment
            </a>
        </div>

        
        <div class="content">
            <h4><i class="fas fa-book"></i> Books</h4>

           
            <div class="search-container">
                <form method="GET" action="book.php">
                    <div class="row">
                        <div class="input-field col s12 m6">
                            <i class="material-icons prefix">search</i>
                            <input id="search" type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <label for="search">Search Books</label>
                        </div>
                        <div class="input-field col s12 m4">
                            <select name="category">
                                <option value="" disabled selected>Filter by Category</option>
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['Categories_ID']); ?>" 
                                        <?php echo $categoryFilter == $category['Categories_ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['Categories_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Categories</label>
                        </div>
                        <div class="col s12 m2">
                            <button type="submit" class="btn waves-effect waves-light" style="margin-top: 15px;">
                                <i class="material-icons left">search</i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

           
            <div class="book-grid">
                <?php if (empty($books)): ?>
                    <div class="col s12 center-align">
                        <p>No books found. Try a different search term or category.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <?php 
                            
                            $imagePath = isset($book['image_path']) ? $book['image_path'] : '';
                            
                            
                            if (empty($imagePath)) {
                                $possibleImages = [
                                    "../uploads/book_" . $book['Book_ID'] . ".jpg",
                                    "../uploads/book_" . $book['Book_ID'] . ".png",
                                    "../uploads/book_" . $book['Book_ID'] . ".jpeg"
                                ];
                                
                                foreach ($possibleImages as $img) {
                                    if (file_exists($img)) {
                                        $imagePath = $img;
                                        break;
                                    }
                                }
                            }
                            
                           
                            if (empty($imagePath) || !file_exists($imagePath)) {
                               
                                $genericImage = "../uploads/book_" . (($book['Book_ID'] % 6) + 1) . ".jpg";
                                if (file_exists($genericImage)) {
                                    $imagePath = $genericImage;
                                } else {
                                    $imagePath = "";
                                }
                            }
                            ?>
                            
                            <div class="book-image <?php echo empty($imagePath) ? 'no-image' : ''; ?>" 
                                 <?php if (!empty($imagePath)): ?>
                                 style="background-image: url('<?php echo htmlspecialchars($imagePath); ?>');"
                                 <?php endif; ?>>
                                <?php if (empty($imagePath)): ?>
                                    <i class="material-icons" style="font-size: 48px; color: #aaa;">menu_book</i>
                                <?php endif; ?>
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
                                
                                <div class="book-meta">
                                    <strong>ISBN:</strong> <?php echo htmlspecialchars($book['ISBN']); ?>
                                </div>
                                
                                <div class="book-meta">
                                    <strong>Status:</strong> 
                                    <?php 
                                    $copies = (int)$book['Copies_available'];
                                    if ($copies > 3) {
                                        echo '<span class="copies-available available">' . $copies . ' Available</span>';
                                    } elseif ($copies > 0) {
                                        echo '<span class="copies-available low-stock">' . $copies . ' Left</span>';
                                    } else {
                                        echo '<span class="copies-available unavailable">Unavailable</span>';
                                    }
                                    ?>
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
          
            var elems = document.querySelectorAll('select');
            M.FormSelect.init(elems);
            
            
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
        });
    </script>
</body>
</html>