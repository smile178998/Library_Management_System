<?php

session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


require_once "../templates/config.php";


$totalBooks = 0;
$currentBorrowed = 0;
$overdueBooks = 0;
$likedBooks = [];

try {
   
    $userIdQuery = "SELECT User_ID FROM user WHERE username = :username";
    $userIdStmt = $pdo->prepare($userIdQuery);
    $userIdStmt->execute(['username' => $_SESSION['username']]);
    $userData = $userIdStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        die("User not found in database");
    }
    
    $currentUserId = $userData['User_ID'];

 
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM book");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalBooks = $result['total'];
    
 
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM borrow_record WHERE User_ID = :User_ID AND Actual_return_date IS NULL");
    $stmt->execute(['User_ID' => $currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBorrowed = $result['total'];

    
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM borrow_record WHERE User_ID = :User_ID AND Actual_return_date IS NULL AND Return_date < CURDATE()");
    $stmt->execute(['User_ID' => $currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $overdueBooks = $result['total'];

   
    $stmt = $pdo->prepare("
        SELECT b.Bookname, b.Authors, b.Book_ID 
        FROM book_like bl
        INNER JOIN book b ON bl.Book_ID = b.Book_ID
        WHERE bl.User_ID = :User_ID
        LIMIT 5
    ");
    $stmt->execute(['User_ID' => $currentUserId]);
    $likedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM book_like WHERE User_ID = :User_ID");
    $stmt->execute(['User_ID' => $currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $likedBooksCount = $result['total'];
    
   
    $stmt = $pdo->prepare("
        SELECT b.Bookname, br.Return_date
        FROM borrow_record br
        INNER JOIN book b ON br.Book_ID = b.Book_ID
        WHERE br.User_ID = :User_ID AND br.Actual_return_date IS NULL
        ORDER BY br.Return_date ASC
        LIMIT 10
    ");
    $stmt->execute(['User_ID' => $currentUserId]);
    $upcomingDueDates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - Dashboard</title>
   
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #26a69a;
            --sidebar-bg: #ffffff;
            --sidebar-active: #26a69a;
            --header-bg: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 100%);
            --card-green: #4CAF50;
            --card-yellow: #FFC107;
            --card-red: #F44336;
            --card-blue: #2196F3;
        }
        
        body {
            font-family: 'Poppins', 'Roboto', sans-serif;
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
        
        /* Enhanced Header Styles */
        header {
            background: var(--header-bg);
            color: white;
            padding: 15px 20px;
            position: fixed;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        header .brand-logo {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        header .brand-logo::before {
            content: "\f02d";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 10px;
            font-size: 1.4rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info span {
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }
        
        .user-info strong {
            font-weight: 600;
        }
        
        .logout-btn {
            border-radius: 20px;
            padding: 0 15px;
            height: 36px;
            line-height: 36px;
            font-weight: 500;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transform: translateY(-2px);
        }
        
        /* Original styles below */
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
        
        
        .stat-card {
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .stat-card.green {
            background-color: var(--card-green);
        }
        
        .stat-card.yellow {
            background-color: var(--card-yellow);
        }
        
        .stat-card.red {
            background-color: var(--card-red);
        }
        
        .stat-card.blue {
            background-color: var(--card-blue);
        }
        
        .stat-card .card-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 48px;
            opacity: 0.3;
        }
        
        .stat-card .card-title {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-card .card-value {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .section-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
            color: #333;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .liked-book {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .liked-book:last-child {
            border-bottom: none;
        }
        
        .book-thumbnail {
            width: 50px;
            height: 70px;
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
        
        .book-info {
            flex: 1;
        }
        
        .book-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .book-author {
            color: #666;
            font-size: 0.9rem;
        }
        
        
        .calendar-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
        }
        
        .calendar-title {
            font-size: 1.2rem;
            font-weight: 500;
            margin: 0;
        }
        
        .calendar-controls {
            display: flex;
            gap: 10px;
        }
        
        .calendar-controls button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .calendar-controls button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .calendar-grid {
            padding: 15px;
        }
        
        .calendar-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .calendar-grid th {
            padding: 10px;
            text-align: center;
            color: #555;
            font-weight: 500;
        }
        
        .calendar-grid td {
            padding: 8px;
            text-align: center;
            height: 40px;
            position: relative;
        }
        
        .calendar-grid td span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .calendar-grid td.today span {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        .calendar-grid td.has-event span {
            position: relative;
        }
        
        .calendar-grid td.has-event span::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .calendar-grid td.other-month {
            color: #ccc;
        }
        
       
        .due-date-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .due-date-item:last-child {
            border-bottom: none;
        }
        
        .due-date-book {
            font-weight: 500;
            color: #333;
        }
        
        .due-date {
            background-color: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #555;
        }
        
        .due-date.soon {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .due-date.overdue {
            background-color: #ffebee;
            color: #d32f2f;
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
        }
    </style>
</head>
<body>
    
    <!-- Enhanced Header -->
    <header>
        <div class="brand-logo">Library Management</div>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="../pages/logout.php" class="btn red logout-btn waves-effect waves-light">Logout</a>
        </div>
    </header>

    <div class="main-container">
        
        <div class="sidebar">
            <a href="dashboard.php" class="menu-item active">
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
            <a href="comment.php" class="menu-item">
                <i class="fas fa-comment"></i> Comment
            </a>
        </div>

       
        <div class="content">
            <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
            
            
            <div class="row">
                <div class="col s12 m6 l3">
                    <div class="stat-card green">
                        <i class="fas fa-book card-icon"></i>
                        <div class="card-title">Total Books</div>
                        <div class="card-value"><?php echo $totalBooks; ?></div>
                    </div>
                </div>
                <div class="col s12 m6 l3">
                    <div class="stat-card yellow">
                        <i class="fas fa-book-reader card-icon"></i>
                        <div class="card-title">Currently Borrowed</div>
                        <div class="card-value"><?php echo $currentBorrowed; ?></div>
                    </div>
                </div>
                <div class="col s12 m6 l3">
                    <div class="stat-card red">
                        <i class="fas fa-exclamation-circle card-icon"></i>
                        <div class="card-title">Overdue Books</div>
                        <div class="card-value"><?php echo $overdueBooks; ?></div>
                    </div>
                </div>
                <div class="col s12 m6 l3">
                    <div class="stat-card blue">
                        <i class="fas fa-heart card-icon"></i>
                        <div class="card-title">Liked Books</div>
                        <div class="card-value"><?php echo $likedBooksCount; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                
                <div class="col s12 l6">
                    
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-heart"></i> Your Liked Books</h5>
                            <a href="like.php" class="btn-flat waves-effect">View All</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($likedBooks)): ?>
                                <p class="center-align">You haven't liked any books yet.</p>
                            <?php else: ?>
                                <?php foreach ($likedBooks as $book): ?>
                                    <div class="liked-book">
                                        <div class="book-thumbnail">
                                            <?php 
                                           
                                            $imagePath = "";
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
                                            
                                            
                                            if (empty($imagePath)) {
                                                $genericImage = "../uploads/book_" . (($book['Book_ID'] % 6) + 1) . ".jpg";
                                                if (file_exists($genericImage)) {
                                                    $imagePath = $genericImage;
                                                }
                                            }
                                            
                                            if (!empty($imagePath)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Book cover">
                                            <?php else: ?>
                                                <i class="material-icons">menu_book</i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="book-info">
                                            <div class="book-title"><?php echo htmlspecialchars($book['Bookname']); ?></div>
                                            <div class="book-author"><?php echo htmlspecialchars($book['Authors']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                   
                    <div class="section-card">
                        <div class="section-header">
                            <h5><i class="fas fa-clock"></i> Upcoming Due Dates</h5>
                            <a href="borrow.php" class="btn-flat waves-effect">View All</a>
                        </div>
                        <div class="section-content">
                            <?php if (empty($upcomingDueDates)): ?>
                                <p class="center-align">You don't have any books due soon.</p>
                            <?php else: ?>
                                <?php foreach ($upcomingDueDates as $due): 
                                    $dueDate = new DateTime($due['Return_date']);
                                    $today = new DateTime();
                                    $interval = $today->diff($dueDate);
                                    $daysRemaining = $interval->invert ? -$interval->days : $interval->days;
                                    
                                    $dueDateClass = '';
                                    if ($interval->invert) {
                                        $dueDateClass = 'overdue';
                                    } elseif ($daysRemaining <= 3) {
                                        $dueDateClass = 'soon';
                                    }
                                ?>
                                    <div class="due-date-item">
                                        <div class="due-date-book"><?php echo htmlspecialchars($due['Bookname']); ?></div>
                                        <div class="due-date <?php echo $dueDateClass; ?>">
                                            <?php 
                                            if ($interval->invert) {
                                                echo 'Overdue by ' . $interval->days . ' days';
                                            } elseif ($daysRemaining == 0) {
                                                echo 'Due today';
                                            } else {
                                                echo 'Due in ' . $daysRemaining . ' days';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
               
                <div class="col s12 l6">
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <h5 class="calendar-title"><i class="fas fa-calendar-alt"></i> <span id="currentMonth"></span></h5>
                            <div class="calendar-controls">
                                <button id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                                <button id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="calendar-grid" id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
       
        document.addEventListener('DOMContentLoaded', function() {
            const calendarDiv = document.getElementById('calendar');
            const currentMonthDisplay = document.getElementById('currentMonth');
            const prevMonthButton = document.getElementById('prevMonth');
            const nextMonthButton = document.getElementById('nextMonth');

            
            let currentDate = new Date();

           
            const monthNames = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];

            
            const dueDates = [
                <?php foreach ($upcomingDueDates as $due): ?>
                {
                    date: new Date("<?php echo $due['Return_date']; ?>"),
                    title: "<?php echo addslashes($due['Bookname']); ?>"
                }
                <?php if (next($upcomingDueDates)) echo ','; ?>
                <?php endforeach; ?>
            ];

            function generateCalendar(year, month) {
                calendarDiv.innerHTML = '';

                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const firstDay = new Date(year, month, 1).getDay();
                
                
                const prevMonthLastDay = new Date(year, month, 0).getDate();
                
                
                const nextMonthDays = (42 - daysInMonth - firstDay) % 7;

                const table = document.createElement('table');
                
               
                const header = document.createElement('thead');
                const headerRow = document.createElement('tr');
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                days.forEach(day => {
                    const th = document.createElement('th');
                    th.textContent = day;
                    headerRow.appendChild(th);
                });
                header.appendChild(headerRow);
                table.appendChild(header);

               
                const body = document.createElement('tbody');
                let row = document.createElement('tr');

                
                for (let i = 0; i < firstDay; i++) {
                    const cell = document.createElement('td');
                    cell.classList.add('other-month');
                    const daySpan = document.createElement('span');
                    daySpan.textContent = prevMonthLastDay - firstDay + i + 1;
                    cell.appendChild(daySpan);
                    row.appendChild(cell);
                }

                
                const today = new Date();
                for (let day = 1; day <= daysInMonth; day++) {
                    const cell = document.createElement('td');
                    const daySpan = document.createElement('span');
                    daySpan.textContent = day;
                    cell.appendChild(daySpan);
                    
                  
                    const currentDay = new Date(year, month, day);
                    const hasEvent = dueDates.some(due => {
                        return due.date.getDate() === currentDay.getDate() && 
                               due.date.getMonth() === currentDay.getMonth() && 
                               due.date.getFullYear() === currentDay.getFullYear();
                    });
                    
                    if (hasEvent) {
                        cell.classList.add('has-event');
                        cell.title = "Book due on this day";
                    }
                    
                    
                    if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                        cell.classList.add('today');
                    }
                    
                    row.appendChild(cell);

                    
                    if ((day + firstDay) % 7 === 0) {
                        body.appendChild(row);
                        row = document.createElement('tr');
                    }
                }

                
                let nextMonthDay = 1;
                while (row.children.length < 7) {
                    const cell = document.createElement('td');
                    cell.classList.add('other-month');
                    const daySpan = document.createElement('span');
                    daySpan.textContent = nextMonthDay++;
                    cell.appendChild(daySpan);
                    row.appendChild(cell);
                }

              
                if (row.children.length > 0) {
                    body.appendChild(row);
                }

                table.appendChild(body);
                calendarDiv.appendChild(table);

              
                currentMonthDisplay.textContent = `${monthNames[month]} ${year}`;
            }

         
            function updateCalendar() {
                generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
            }

            updateCalendar();

         
            prevMonthButton.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateCalendar();
            });

            nextMonthButton.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateCalendar();
            });
            
            
            var elems = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(elems);
        });
    </script>
</body>
</html>