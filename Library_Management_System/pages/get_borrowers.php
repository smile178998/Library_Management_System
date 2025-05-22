<?php
require_once "../templates/config.php";

header('Content-Type: application/json');

if (!isset($_GET['book_id'])) {
    echo json_encode(['error' => 'Book ID is required']);
    exit;
}

$bookId = htmlspecialchars($_GET['book_id']);

try {

    $stmt = $pdo->prepare("
        SELECT br.Record_ID, br.User_ID, br.Borrower_Name, 
               DATE_FORMAT(br.Borrow_date, '%Y-%m-%d') as Borrow_date, 
               DATE_FORMAT(br.Return_date, '%Y-%m-%d') as Return_date
        FROM borrow_record br
        WHERE br.Book_ID = :book_id AND br.Actual_return_date IS NULL
        ORDER BY br.Return_date ASC
    ");
    $stmt->execute(['book_id' => $bookId]);
    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['borrowers' => $borrowers]);
} catch (PDOException $e) {
    error_log("Error fetching borrowers: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch borrowers']);
}
?>
