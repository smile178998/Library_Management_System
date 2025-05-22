<?php
require_once "../templates/config.php";

$successMessage = '';
$errorMessage = '';
$book = null;
$categories = [];


if (!isset($_GET['book_id'])) {
    header("Location: admin_book.php");
    exit();
}

$book_id = htmlspecialchars($_GET['book_id']);


try {
    $stmt = $pdo->prepare("
        SELECT b.Book_ID, b.Bookname, b.Authors, b.Publishers, b.Publication_date, 
               b.Copies_available, b.ISBN, bc.Categories_ID, c.Categories_name
        FROM book b
        LEFT JOIN book_category bc ON b.Book_ID = bc.Book_ID
        LEFT JOIN category c ON bc.Categories_ID = c.Categories_ID
        WHERE b.Book_ID = :book_id
    ");
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $errorMessage = "Book not found!";
    }
    
  
    $categories = $pdo->query("SELECT * FROM category")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching book details: " . $e->getMessage());
    $errorMessage = "Error fetching book details. Please try again later.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $bookname = htmlspecialchars($_POST['bookname']);
    $authors = htmlspecialchars($_POST['authors']);
    $publishers = htmlspecialchars($_POST['publishers']);
    $publication_date = htmlspecialchars($_POST['publication_date']);
    $copies_available = htmlspecialchars($_POST['copies_available']);
    $isbn = htmlspecialchars($_POST['isbn']);
    $category_id = intval($_POST['category_id']); 


    if (empty($bookname) || empty($authors) || empty($publishers) || empty($publication_date) || 
        empty($copies_available) || empty($isbn) || empty($category_id)) {
        $errorMessage = "All fields are required!";
    } else {
        try {
      
            $pdo->beginTransaction();
            
        
            $stmt = $pdo->prepare("
                UPDATE book 
                SET Bookname = :bookname, 
                    Authors = :authors, 
                    Publishers = :publishers, 
                    Publication_date = :publication_date, 
                    Copies_available = :copies_available, 
                    ISBN = :isbn 
                WHERE Book_ID = :book_id
            ");
            
            $stmt->execute([
                'bookname' => $bookname,
                'authors' => $authors,
                'publishers' => $publishers,
                'publication_date' => $publication_date,
                'copies_available' => $copies_available,
                'isbn' => $isbn,
                'book_id' => $book_id
            ]);
            
    
            if ($category_id !== ($book['Categories_ID'] ?? null)) {
           
                $stmt = $pdo->prepare("DELETE FROM book_category WHERE Book_ID = :book_id");
                $stmt->execute(['book_id' => $book_id]);
                
         
                $stmt = $pdo->prepare("INSERT INTO book_category (Book_ID, Categories_ID) VALUES (:book_id, :category_id)");
                $stmt->execute([
                    'book_id' => $book_id,
                    'category_id' => $category_id
                ]);
            }
            
      
            if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
                $imageTmpPath = $_FILES['book_image']['tmp_name'];
                $imageName = $_FILES['book_image']['name'];
                $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array(strtolower($imageExtension), $allowedExtensions)) {
                    $uploadDir = "../uploads/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                
                    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    foreach ($possibleExtensions as $ext) {
                        $imagePath = "{$uploadDir}book_{$book_id}.{$ext}";
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                            break;
                        }
                    }
                    
              
                    $targetFileName = "book_{$book_id}.{$imageExtension}";
                    $targetFilePath = $uploadDir . $targetFileName;
                    
                    if (!move_uploaded_file($imageTmpPath, $targetFilePath)) {
                        throw new Exception("Failed to save book image.");
                    }
                } else {
                    throw new Exception("Invalid image format. Only JPG, JPEG, PNG, and GIF are allowed.");
                }
            }
            
      
            $pdo->commit();
            
            $successMessage = "Book updated successfully!";
            
     
            $stmt = $pdo->prepare("
                SELECT b.Book_ID, b.Bookname, b.Authors, b.Publishers, b.Publication_date, 
                    b.Copies_available, b.ISBN, bc.Categories_ID, c.Categories_name
                FROM book b
                LEFT JOIN book_category bc ON b.Book_ID = bc.Book_ID
                LEFT JOIN category c ON bc.Categories_ID = c.Categories_ID
                WHERE b.Book_ID = :book_id
            ");
            $stmt->execute(['book_id' => $book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
        
            $pdo->rollBack();
            error_log("Error updating book: " . $e->getMessage());
            $errorMessage = "Error updating book: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book | Library Management System</title>
 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
   
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar .nav-link {
            color: white;
            font-size: 16px;
            padding: 15px 20px;
            display: block;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid #ffffff;
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 3px solid #ffffff;
        }
        
        .sidebar.collapsed .nav-link {
            text-align: center;
            padding: 15px 5px;
        }
        
        .sidebar.collapsed .nav-link span {
            display: none;
        }
        
        .sidebar-toggler {
            position: absolute;
            top: 10px;
            right: -15px;
            background-color: #343a40;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1100;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggler:hover {
            background-color: #495057;
        }
        
       
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .content.collapsed {
            margin-left: 70px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header h1 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .edit-section {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .edit-section h2 {
            color: #343a40;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .edit-section h2 i {
            margin-right: 10px;
        }
        
        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-size: 16px;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .image-preview {
            width: 150px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .current-image {
            max-width: 150px;
            max-height: 200px;
            border-radius: 5px;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
      
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .content {
                margin-left: 70px;
            }
            
            .dashboard-header {
                padding: 15px;
            }
            
            .dashboard-header h1 {
                font-size: 24px;
            }
            
            .edit-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <button class="sidebar-toggler" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link active" href="admin_book.php">
                <i class="fas fa-book me-2"></i>
                <span>Books</span>
            </a>
            <a class="nav-link" href="admin_registered_users.php">
                <i class="fas fa-users me-2"></i>
                <span>Registered Users</span>
            </a>
        </nav>
    </div>

  
    <div class="content" id="content">
        <div class="dashboard-header">
            <h1><i class="fas fa-edit me-2"></i> Edit Book</h1>
            <p>Update book information in the library system</p>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="message success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($book): ?>
            <div class="edit-section">
                <h2><i class="fas fa-book"></i> Edit Book Details</h2>
                
                <form method="POST" action="" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="update_book" value="1">
                    
                    <div class="col-md-6">
                        <label for="book_id" class="form-label">Book ID</label>
                        <input type="text" class="form-control" id="book_id" value="<?php echo htmlspecialchars($book['Book_ID']); ?>" readonly>
                        <div class="form-text">Book ID cannot be changed</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>" required>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="bookname" class="form-label">Book Name</label>
                        <input type="text" class="form-control" id="bookname" name="bookname" value="<?php echo htmlspecialchars($book['Bookname']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="authors" class="form-label">Author</label>
                        <input type="text" class="form-control" id="authors" name="authors" value="<?php echo htmlspecialchars($book['Authors']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="publishers" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="publishers" name="publishers" value="<?php echo htmlspecialchars($book['Publishers']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="publication_date" class="form-label">Publication Date</label>
                        <input type="text" class="form-control" id="publication_date" name="publication_date" value="<?php echo htmlspecialchars($book['Publication_date']); ?>" placeholder="YYYY-MM-DD" required>
                        <div class="form-text">Enter date in YYYY-MM-DD format (e.g., 2023-05-15)</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="copies_available" class="form-label">Copies Available</label>
                        <input type="number" class="form-control" id="copies_available" name="copies_available" min="0" value="<?php echo htmlspecialchars($book['Copies_available']); ?>" required>
                    </div>
                    
                    <div class="col-md-12">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['Categories_ID']; ?>" <?php echo ($category['Categories_ID'] == $book['Categories_ID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['Categories_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Current Book Cover</label>
                        <div>
                            <?php
                                $imageFound = false;
                                $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                foreach ($possibleExtensions as $ext) {
                                    $imagePath = "../uploads/book_{$book['Book_ID']}.{$ext}";
                                    if (file_exists($imagePath)) {
                                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Current Book Cover" class="current-image">';
                                        $imageFound = true;
                                        break;
                                    }
                                }
                                
                                if (!$imageFound) {
                                    echo '<div class="alert alert-info">No image available for this book.</div>';
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mt-3">
                        <label for="book_image" class="form-label">Update Book Image</label>
                        <input type="file" class="form-control" id="book_image" name="book_image" accept="image/*" onchange="previewImage(this)">
                        <div class="form-text">Upload a new cover image for the book (JPG, JPEG, PNG, or GIF). Leave empty to keep current image.</div>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="preview" src="#" alt="Image Preview">
                        </div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <a href="admin_book.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Book List
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> Book not found!
                <p class="mt-3">
                    <a href="admin_book.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Book List
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
      
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
            
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
        
      
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const imagePreview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    imagePreview.style.display = 'flex';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                imagePreview.style.display = 'none';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
           
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('content').classList.add('collapsed');
            }
            
         
            const messages = document.querySelectorAll('.message');
            if (messages.length > 0) {
                setTimeout(function() {
                    messages.forEach(function(message) {
                        message.style.transition = 'opacity 1s ease';
                        message.style.opacity = '0';
                        setTimeout(function() {
                            message.style.display = 'none';
                        }, 1000);
                    });
                }, 5000);
            }
        });
    </script>
</body>
</html>
