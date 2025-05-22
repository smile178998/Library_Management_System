<?php

require_once "../templates/config.php";

$successMessage = '';
$errorMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_full_book'])) {
    $book_id = htmlspecialchars($_POST['book_id']);
    $bookname = htmlspecialchars($_POST['bookname']);
    $authors = htmlspecialchars($_POST['authors']);
    $publishers = htmlspecialchars($_POST['publishers']);
    $publication_date = htmlspecialchars($_POST['publication_date']);
    $copies_available = htmlspecialchars($_POST['copies_available']);
    $isbn = htmlspecialchars($_POST['isbn']);
    
    try {
        $stmt = $pdo->prepare("UPDATE book SET 
            Bookname = :bookname, 
            Authors = :authors, 
            Publishers = :publishers, 
            Publication_date = :publication_date, 
            Copies_available = :copies_available, 
            ISBN = :isbn 
            WHERE Book_ID = :book_id");
            
        $stmt->execute([
            'bookname' => $bookname,
            'authors' => $authors,
            'publishers' => $publishers,
            'publication_date' => $publication_date,
            'copies_available' => $copies_available,
            'isbn' => $isbn,
            'book_id' => $book_id
        ]);
        
        $successMessage = "Book updated successfully!";
        
       
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => $successMessage]);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error updating book: " . $e->getMessage());
        $errorMessage = "Error updating book: " . $e->getMessage();
        
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_book'])) {
    $book_id = htmlspecialchars($_POST['edit_book_id']);
    $copies_available = htmlspecialchars($_POST['edit_copies_available']);
    
    try {
        $stmt = $pdo->prepare("UPDATE book SET Copies_available = :copies_available WHERE Book_ID = :book_id");
        $stmt->execute([
            'copies_available' => $copies_available,
            'book_id' => $book_id
        ]);
        
        $successMessage = "Book updated successfully!";
    } catch (PDOException $e) {
        error_log("Error updating book: " . $e->getMessage());
        $errorMessage = "Error updating book: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $record_id = htmlspecialchars($_POST['record_id']);
    $book_id = htmlspecialchars($_POST['book_id']);
    
    try {
      
        $pdo->beginTransaction();
        
        
        $stmt = $pdo->prepare("UPDATE borrow_record SET Actual_return_date = NOW() WHERE Record_ID = :record_id");
        $stmt->execute(['record_id' => $record_id]);
        
       
        $stmt = $pdo->prepare("UPDATE book SET Copies_available = Copies_available + 1 WHERE Book_ID = :book_id");
        $stmt->execute(['book_id' => $book_id]);
        
        
        $pdo->commit();
        
        $successMessage = "Book returned successfully!";
    } catch (PDOException $e) {
        
        $pdo->rollBack();
        error_log("Error returning book: " . $e->getMessage());
        $errorMessage = "Error returning book: " . $e->getMessage();
    }
}

try {
    $categories = $pdo->query("SELECT * FROM category")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $errorMessage = "Error fetching categories. Please try again later.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $book_id = htmlspecialchars($_POST['book_id']); 
    $bookname = htmlspecialchars($_POST['bookname']);
    $authors = htmlspecialchars($_POST['authors']);
    $publishers = htmlspecialchars($_POST['publishers']);
    $publication_date = htmlspecialchars($_POST['publication_date']);
    $copies_available = htmlspecialchars($_POST['copies_available']);
    $isbn = htmlspecialchars($_POST['isbn']);
    $category_id = intval($_POST['category_id']); 

    if (empty($book_id) || empty($bookname) || empty($authors) || empty($publishers) || empty($publication_date) || empty($copies_available) || empty($isbn) || empty($category_id)) {
        $errorMessage = "All fields are required!";
    } else {
        
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

                
                $targetFileName = "book_{$book_id}." . $imageExtension;
                $targetFilePath = $uploadDir . $targetFileName;

               
                if (!move_uploaded_file($imageTmpPath, $targetFilePath)) {
                    $errorMessage = "Failed to move uploaded file. Please check the folder permissions.";
                }
            } else {
                $errorMessage = "Invalid image file format. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }

        if (empty($errorMessage)) {
            try {
               
                $pdo->beginTransaction();
                
                
                $stmt = $pdo->prepare("INSERT INTO book (Book_ID, Bookname, Authors, Publishers, Publication_date, Copies_available, ISBN) VALUES (:book_id, :bookname, :authors, :publishers, :publication_date, :copies_available, :isbn)");
                $stmt->execute([
                    'book_id' => $book_id,
                    'bookname' => $bookname,
                    'authors' => $authors,
                    'publishers' => $publishers,
                    'publication_date' => $publication_date,
                    'copies_available' => $copies_available,
                    'isbn' => $isbn
                ]);
                
                
                $stmt = $pdo->prepare("INSERT INTO book_category (Book_ID, Categories_ID) VALUES (:book_id, :category_id)");
                $stmt->execute([
                    'book_id' => $book_id,
                    'category_id' => $category_id
                ]);
                
                
                $pdo->commit();

               
                if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
                    $successMessage = "Book added successfully! Image uploaded successfully!";
                } else {
                    $successMessage = "Book added successfully!";
                }
            } catch (PDOException $e) {
               
                $pdo->rollBack();
                error_log("Error adding book: " . $e->getMessage());
                $errorMessage = "Error adding book: " . $e->getMessage();
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $bookId = htmlspecialchars($_POST['book_id']);
    try {
       
        $pdo->beginTransaction();
        
        
        $stmt = $pdo->prepare("DELETE FROM book_category WHERE Book_ID = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        
        
        $stmt = $pdo->prepare("DELETE FROM book WHERE Book_ID = :book_id");
        $stmt->execute(['book_id' => $bookId]);
        
       
        $pdo->commit();
        
        
        $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        foreach ($possibleExtensions as $ext) {
            $imagePath = "../uploads/book_{$bookId}.{$ext}";
            if (file_exists($imagePath)) {
                unlink($imagePath);
                break;
            }
        }
        
        $successMessage = "Book deleted successfully!";
    } catch (PDOException $e) {
        
        $pdo->rollBack();
        error_log("Error deleting book: " . $e->getMessage());
        $errorMessage = "Error deleting book: " . $e->getMessage();
    }
}


try {
    $books = $pdo->query("
        SELECT b.Book_ID, b.Bookname, b.Authors, b.Publishers, b.Publication_date, b.Copies_available, b.ISBN, c.Categories_name 
        FROM book b
        LEFT JOIN book_category bc ON b.Book_ID = bc.Book_ID
        LEFT JOIN category c ON bc.Categories_ID = c.Categories_ID
        ORDER BY b.Book_ID
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching books: " . $e->getMessage());
    $books = [];
    $errorMessage = "Error fetching books. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Book Management | Library System</title>
 
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
        
        .form-section, .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .form-section h2, .table-container h2 {
            color: #343a40;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .form-section h2 i, .table-container h2 i {
            margin-right: 10px;
        }
        
        .book-image {
            max-width: 100px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 105, 217, 0.2);
        }
        
        .btn-danger {
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }
        
        .table {
            vertical-align: middle;
        }
        
        .table thead th {
            background-color: #343a40;
            color: white;
            border: none;
        }
        
        .table-bordered {
            border: none;
        }
        
        .table-bordered td, .table-bordered th {
            border: 1px solid #dee2e6;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
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
            
            .form-section, .table-container {
                padding: 15px;
            }
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
            display: none;
        }
        
       
        .edit-mode input {
            width: 100%;
            padding: 5px;
            border: 1px solid #007bff;
            border-radius: 4px;
        }
        
       
        .borrowers-table {
            margin-top: 15px;
        }
        
        .borrowers-table th {
            background-color: #f8f9fa;
        }
        
        .overdue {
            color: #dc3545;
            font-weight: bold;
        }
        
        .btn-group-sm .btn {
            margin-right: 3px;
        }
        
        .modal-header {
            background-color: #343a40;
            color: white;
        }
        
        .modal-title {
            font-weight: 600;
        }

        .book-field {
            padding: 8px;
            vertical-align: middle;
        }

        .field-value {
            display: inline-block;
            width: 100%;
        }

        .field-input {
            display: none;
            width: 100%;
        }

       
        .book-field {
            position: relative;
        }

        .field-value {
            display: block;
            min-height: 1.5em;
        }

        .field-input {
            display: none;
            width: 100%;
            padding: 5px;
            border: 1px solid #007bff;
            border-radius: 4px;
        }

      
        .borrowers-row {
            background-color: #f8f9fa;
        }

        .borrowers-container {
            border-radius: 0 0 8px 8px;
            border-top: 1px solid #dee2e6;
            box-shadow: inset 0 3px 6px rgba(0,0,0,0.05);
        }

        .borrowers-data {
            max-height: 300px;
            overflow-y: auto;
        }

        .editable-mode .field-value {
            display: none;
        }

        .editable-mode .field-input {
            display: block;
        }

        .btn-save-container {
            display: none;
            padding: 10px 0;
            text-align: right;
        }

        .editable-mode .btn-save-container {
            display: block;
        }

        .book-edit-form {
            margin-bottom: 0;
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
        <h1><i class="fas fa-book me-2"></i> Admin Book Management</h1>
        <p>Manage all books in the library system</p>
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

  
    <div class="form-section">
        <h2><i class="fas fa-plus-circle"></i> Add a New Book</h2>
        <form method="POST" action="" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="add_book" value="1">
            
            <div class="col-md-6">
                <label for="book_id" class="form-label">Book ID</label>
                <input type="text" class="form-control" id="book_id" name="book_id" required>
            </div>
            
            <div class="col-md-6">
                <label for="isbn" class="form-label">ISBN</label>
                <input type="text" class="form-control" id="isbn" name="isbn" required>
            </div>
            
            <div class="col-md-12">
                <label for="bookname" class="form-label">Book Name</label>
                <input type="text" class="form-control" id="bookname" name="bookname" required>
            </div>
            
            <div class="col-md-6">
                <label for="authors" class="form-label">Author</label>
                <input type="text" class="form-control" id="authors" name="authors" required>
            </div>
            
            <div class="col-md-6">
                <label for="publishers" class="form-label">Publisher</label>
                <input type="text" class="form-control" id="publishers" name="publishers" required>
            </div>
            
            <div class="col-md-6">
                <label for="publication_date" class="form-label">Publication Date</label>
                <input type="text" class="form-control" id="publication_date" name="publication_date" placeholder="YYYY-MM-DD" required>
                <div class="form-text">Enter date in YYYY-MM-DD format (e.g., 2023-05-15)</div>
            </div>
            
            <div class="col-md-6">
                <label for="copies_available" class="form-label">Copies Available</label>
                <input type="number" class="form-control" id="copies_available" name="copies_available" min="0" required>
            </div>
            
            <div class="col-md-12">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['Categories_ID']; ?>">
                            <?php echo htmlspecialchars($category['Categories_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-12">
                <label for="book_image" class="form-label">Book Image</label>
                <input type="file" class="form-control" id="book_image" name="book_image" accept="image/*" onchange="previewImage(this)">
                <div class="form-text">Upload a cover image for the book (JPG, JPEG, PNG, or GIF).</div>
                <div class="image-preview" id="imagePreview">
                    <img id="preview" src="#" alt="Image Preview">
                    <span id="previewText">Image Preview</span>
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Add Book
                </button>
                <button type="reset" class="btn btn-secondary" onclick="resetImagePreview()">
                    <i class="fas fa-undo me-2"></i> Reset
                </button>
            </div>
        </form>
    </div>

   
    <div class="table-container">
        <h2><i class="fas fa-list"></i> Books List</h2>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Book Name</th>
                        <th>Author</th>
                        <th>Publisher</th>
                        <th>Publication Date</th>
                        <th>Copies</th>
                        <th>ISBN</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No books found in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
    <tr id="book-row-<?php echo $book['Book_ID']; ?>">
        <td class="text-center">
            <?php
                $imageFound = false;
                $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                foreach ($possibleExtensions as $ext) {
                    $imagePath = "../uploads/book_{$book['Book_ID']}.{$ext}";
                    if (file_exists($imagePath)) {
                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Book Cover" class="book-image">';
                        $imageFound = true;
                        break;
                    }
                }
                
                if (!$imageFound) {
                    echo '<img src="../uploads/default-placeholder.png" alt="No Image" class="book-image">';
                }
            ?>
        </td>
        <td class="book-field" data-field="book-id"><?php echo htmlspecialchars($book['Book_ID']); ?></td>
        <td class="book-field" data-field="bookname"><?php echo htmlspecialchars($book['Bookname']); ?></td>
        <td class="book-field" data-field="authors"><?php echo htmlspecialchars($book['Authors']); ?></td>
        <td class="book-field" data-field="publishers"><?php echo htmlspecialchars($book['Publishers']); ?></td>
        <td class="book-field" data-field="publication_date"><?php echo htmlspecialchars($book['Publication_date']); ?></td>
        <td class="book-field copies-cell" data-field="copies_available" data-book-id="<?php echo $book['Book_ID']; ?>">
            <span class="field-value"><?php echo htmlspecialchars($book['Copies_available']); ?></span>
            <input type="number" class="form-control field-input" style="display: none;" min="0" value="<?php echo htmlspecialchars($book['Copies_available']); ?>">
        </td>
        <td class="book-field" data-field="isbn"><?php echo htmlspecialchars($book['ISBN']); ?></td>
        <td class="book-field" data-field="category"><?php echo htmlspecialchars($book['Categories_name'] ?? 'Uncategorized'); ?></td>
        <td>
            <div class="d-flex gap-2">
                <a href="edit_book.php?book_id=<?php echo $book['Book_ID']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-sm btn-info toggle-borrowers-btn" data-book-id="<?php echo $book['Book_ID']; ?>" data-book-name="<?php echo htmlspecialchars($book['Bookname']); ?>">
                    <i class="fas fa-users"></i>
                </button>
                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                    <input type="hidden" name="delete_book" value="1">
                    <input type="hidden" name="book_id" value="<?php echo $book['Book_ID']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
    <tr class="borrowers-row" id="borrowers-row-<?php echo $book['Book_ID']; ?>" style="display: none;">
        <td colspan="10" class="p-0">
            <div class="borrowers-container p-3 bg-light">
                <h5 class="mb-3">Borrowers of "<?php echo htmlspecialchars($book['Bookname']); ?>"</h5>
                <div id="borrowers-data-<?php echo $book['Book_ID']; ?>" class="borrowers-data">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="borrowersModal" tabindex="-1" aria-labelledby="borrowersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="borrowersModalLabel">Book Borrowers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="book-title" class="mb-3"></h4>
                <div id="borrowers-container">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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
        const previewText = document.getElementById('previewText');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                previewText.style.display = 'none';
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    
    function resetImagePreview() {
        const preview = document.getElementById('preview');
        const previewText = document.getElementById('previewText');
        
        preview.src = '#';
        preview.style.display = 'none';
        previewText.style.display = 'block';
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
        
        
        const editButtons = document.querySelectorAll('.edit-book-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-book-id');
                const bookRow = document.getElementById(`book-row-${bookId}`);
                
                if (!bookRow.classList.contains('editable-mode')) {
                   
                    bookRow.classList.add('editable-mode');
                    
                    
                    const fields = bookRow.querySelectorAll('.book-field');
                    fields.forEach(field => {
                        const fieldName = field.getAttribute('data-field');
                        const fieldValue = field.querySelector('.field-value');
                        
                        
                        if (fieldName === 'book-id' || fieldName === 'category') {
                            return;
                        }
                        
                        if (!field.querySelector('.field-input')) {
                            const input = document.createElement('input');
                            input.type = fieldName === 'copies_available' ? 'number' : 'text';
                            input.className = 'form-control field-input';
                            input.value = fieldValue.textContent.trim();
                            if (fieldName === 'copies_available') {
                                input.min = 0;
                            }
                            field.appendChild(input);
                        } else {
                            const input = field.querySelector('.field-input');
                            input.value = fieldValue.textContent.trim();
                        }
                    });
                    
                    
                    if (!bookRow.querySelector('.btn-save-container')) {
                        const actionCell = bookRow.querySelector('td:last-child');
                        const saveContainer = document.createElement('div');
                        saveContainer.className = 'btn-save-container mt-2';
                        saveContainer.innerHTML = `
                            <button type="button" class="btn btn-sm btn-success save-book-btn" data-book-id="${bookId}">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary ms-2 cancel-edit-btn" data-book-id="${bookId}">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                        `;
                        actionCell.appendChild(saveContainer);
                        
                        
                        const saveBtn = saveContainer.querySelector('.save-book-btn');
                        saveBtn.addEventListener('click', function() {
                            saveBookChanges(bookId);
                        });
                        
                        const cancelBtn = saveContainer.querySelector('.cancel-edit-btn');
                        cancelBtn.addEventListener('click', function() {
                            cancelEditing(bookId);
                        });
                    }
                    
                    
                    this.innerHTML = '<i class="fas fa-pen"></i>';
                    this.title = 'Currently editing';
                    this.disabled = true;
                }
            });
        });
        
        
        const toggleBorrowersButtons = document.querySelectorAll('.toggle-borrowers-btn');
        toggleBorrowersButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-book-id');
                const bookName = this.getAttribute('data-book-name');
                const borrowersRow = document.getElementById(`borrowers-row-${bookId}`);
                
                if (borrowersRow.style.display === 'none') {
                    
                    document.querySelectorAll('.borrowers-row').forEach(row => {
                        if (row.id !== `borrowers-row-${bookId}` && row.style.display !== 'none') {
                            row.style.display = 'none';
                        }
                    });
                    
                   
                    borrowersRow.style.display = 'table-row';
                    
                    
                    loadBorrowersData(bookId);
                    
                    
                    this.classList.remove('btn-info');
                    this.classList.add('btn-secondary');
                } else {
                    
                    borrowersRow.style.display = 'none';
                    
                    
                    this.classList.remove('btn-secondary');
                    this.classList.add('btn-info');
                }
            });
        });
        
        
        function saveBookChanges(bookId) {
            const bookRow = document.getElementById(`book-row-${bookId}`);
            
            
            const bookname = bookRow.querySelector('[data-field="bookname"] .field-input').value;
            const authors = bookRow.querySelector('[data-field="authors"] .field-input').value;
            const publishers = bookRow.querySelector('[data-field="publishers"] .field-input').value;
            const publication_date = bookRow.querySelector('[data-field="publication_date"] .field-input').value;
            const copies_available = bookRow.querySelector('[data-field="copies_available"] .field-input').value;
            const isbn = bookRow.querySelector('[data-field="isbn"] .field-input').value;
            
            
            const formData = new FormData();
            formData.append('edit_full_book', '1');
            formData.append('book_id', bookId);
            formData.append('bookname', bookname);
            formData.append('authors', authors);
            formData.append('publishers', publishers);
            formData.append('publication_date', publication_date);
            formData.append('copies_available', copies_available);
            formData.append('isbn', isbn);
            
            
            fetch('admin_book.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                   
                    bookRow.querySelector('[data-field="bookname"] .field-value').textContent = bookname;
                    bookRow.querySelector('[data-field="authors"] .field-value').textContent = authors;
                    bookRow.querySelector('[data-field="publishers"] .field-value').textContent = publishers;
                    bookRow.querySelector('[data-field="publication_date"] .field-value').textContent = publication_date;
                    bookRow.querySelector('[data-field="copies_available"] .field-value').textContent = copies_available;
                    bookRow.querySelector('[data-field="isbn"] .field-value').textContent = isbn;
                    
                   
                    bookRow.classList.remove('editable-mode');
                  
                    const editBtn = bookRow.querySelector('.edit-book-btn');
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    editBtn.title = 'Edit book';
                    editBtn.disabled = false;
                    
                   
                    const successDiv = document.createElement('div');
                    successDiv.className = 'message success';
                    successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i> Book updated successfully!';
                    
                    const content = document.getElementById('content');
                    content.insertBefore(successDiv, content.firstChild.nextSibling);
                    
                  
                    setTimeout(function() {
                        successDiv.style.transition = 'opacity 1s ease';
                        successDiv.style.opacity = '0';
                        setTimeout(function() {
                            successDiv.remove();
                        }, 1000);
                    }, 3000);
                } else {
                    alert('Failed to update book: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update book. Please try again.');
            });
        }
        
        
        function cancelEditing(bookId) {
            const bookRow = document.getElementById(`book-row-${bookId}`);
            
           
            bookRow.classList.remove('editable-mode');
            
           
            const editBtn = bookRow.querySelector('.edit-book-btn');
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.title = 'Edit book';
            editBtn.disabled = false;
        }
        
       
        function loadBorrowersData(bookId) {
            const container = document.getElementById(`borrowers-data-${bookId}`);
         
            fetch(`get_borrowers.php?book_id=${bookId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.borrowers && data.borrowers.length > 0) {
                        let html = '<table class="table table-bordered borrowers-table">';
                        html += '<thead><tr>';
                        html += '<th>Student ID</th>';
                        html += '<th>Borrower Name</th>';
                        html += '<th>Borrow Date</th>';
                        html += '<th>Due Date</th>';
                        html += '<th>Action</th>';
                        html += '</tr></thead>';
                        html += '<tbody>';
                        
                        data.borrowers.forEach(borrower => {
                            const today = new Date();
                            const dueDate = new Date(borrower.Return_date);
                            const isOverdue = today > dueDate;
                            
                            html += '<tr>';
                            html += `<td>${borrower.User_ID}</td>`;
                            html += `<td>${borrower.Borrower_Name}</td>`;
                            html += `<td>${borrower.Borrow_date}</td>`;
                            html += `<td class="${isOverdue ? 'overdue' : ''}">${borrower.Return_date}</td>`;
                            html += '<td>';
                            html += `<button type="button" class="btn btn-sm btn-success return-book-btn" 
                                      data-record-id="${borrower.Record_ID}" 
                                      data-book-id="${bookId}">
                                     <i class="fas fa-undo-alt me-1"></i> Return
                                     </button>`;
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                        
                        
                        const returnButtons = container.querySelectorAll('.return-book-btn');
                        returnButtons.forEach(btn => {
                            btn.addEventListener('click', function() {
                                returnBook(this);
                            });
                        });
                    } else {
                        container.innerHTML = '<div class="alert alert-info">No one is currently borrowing this book.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = 
                        '<div class="alert alert-danger">Error loading borrowers. Please try again.</div>';
                });
        }
        
      
        function returnBook(button) {
            const recordId = button.getAttribute('data-record-id');
            const bookId = button.getAttribute('data-book-id');
            
            
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            
            const formData = new FormData();
            formData.append('return_book', '1');
            formData.append('record_id', recordId);
            formData.append('book_id', bookId);
            
           
            fetch('admin_book.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
              
                loadBorrowersData(bookId);
                
                
                const copiesCell = document.querySelector(`.copies-cell[data-book-id="${bookId}"]`);
                if (copiesCell) {
                    const copiesValue = copiesCell.querySelector('.field-value');
                    const currentCopies = parseInt(copiesValue.textContent);
                    copiesValue.textContent = currentCopies + 1;
                    
                    const copiesInput = copiesCell.querySelector('.field-input');
                    if (copiesInput) {
                        copiesInput.value = currentCopies + 1;
                    }
                }
                
             
                const successDiv = document.createElement('div');
                successDiv.className = 'message success';
                successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i> Book returned successfully!';
                
                const content = document.getElementById('content');
                content.insertBefore(successDiv, content.firstChild.nextSibling);
                
               
                setTimeout(function() {
                    successDiv.style.transition = 'opacity 1s ease';
                    successDiv.style.opacity = '0';
                    setTimeout(function() {
                        successDiv.remove();
                    }, 1000);
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to return book. Please try again.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-undo-alt me-1"></i> Return';
            });
        }
    });
</script>
</body>
</html>
