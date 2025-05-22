<?php

require_once "../templates/config.php";


$totalBooks = 0;
$totalUsers = 0;
$monthlyBorrowStats = [];


try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_books FROM book");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalBooks = $result['total_books'];
} catch (PDOException $e) {
    error_log("Error fetching book count: " . $e->getMessage());
}


try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM user");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers = $result['total_users'];
} catch (PDOException $e) {
    error_log("Error fetching user count: " . $e->getMessage());
}


try {
 
    $monthlyBorrowStats = [
        '01' => 0, '02' => 0, '03' => 0, '04' => 0, '05' => 0, '06' => 0,
        '07' => 0, '08' => 0, '09' => 0, '10' => 0, '11' => 0, '12' => 0
    ];

    $currentYear = date('Y');
    
  
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(Borrow_date, '%m') AS month,
            COUNT(*) AS borrow_count
        FROM 
            borrow_record
        WHERE 
            YEAR(Borrow_date) = :year
        GROUP BY 
            DATE_FORMAT(Borrow_date, '%m')
    ");
    
    $stmt->execute(['year' => $currentYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    foreach ($results as $row) {
        $monthlyBorrowStats[$row['month']] = (int)$row['borrow_count'];
    }
    
    
    $currentMonth = date('m');
    if ($currentMonth < 12) {
        $lastYear = $currentYear - 1;
        
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(Borrow_date, '%m') AS month,
                COUNT(*) AS borrow_count
            FROM 
                borrow_record
            WHERE 
                YEAR(Borrow_date) = :year
                AND DATE_FORMAT(Borrow_date, '%m') > :current_month
            GROUP BY 
                DATE_FORMAT(Borrow_date, '%m')
        ");
        
        $stmt->execute([
            'year' => $lastYear,
            'current_month' => $currentMonth
        ]);
        
        $lastYearResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
       
        foreach ($lastYearResults as $row) {
            if ((int)$row['month'] > (int)$currentMonth) {
                $monthlyBorrowStats[$row['month']] = (int)$row['borrow_count'];
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Error fetching monthly borrowing statistics: " . $e->getMessage());
}


try {
    $topBooks = $pdo->query("
        SELECT 
            b.Book_ID,
            b.Bookname,
            COUNT(br.Book_ID) AS borrow_count
        FROM 
            borrow_record br
        JOIN 
            book b ON br.Book_ID = b.Book_ID
        GROUP BY 
            br.Book_ID
        ORDER BY 
            borrow_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching top borrowed books: " . $e->getMessage());
    $topBooks = [];
}


try {
    $overdueBooks = $pdo->query("
        SELECT 
            br.Record_ID,
            b.Bookname,
            u.Username,
            br.Borrow_date,
            br.Return_date,
            DATEDIFF(CURRENT_DATE, br.Return_date) AS days_overdue
        FROM 
            borrow_record br
        JOIN 
            book b ON br.Book_ID = b.Book_ID
        JOIN 
            user u ON br.User_ID = u.User_ID
        WHERE 
            br.Return_date < CURRENT_DATE
            AND br.Actual_return_date IS NULL
        ORDER BY 
            days_overdue DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching overdue books: " . $e->getMessage());
    $overdueBooks = [];
}


$monthlyBorrowStatsJSON = json_encode(array_values($monthlyBorrowStats));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Library Management System</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            position: relative;
        }
        
        .dashboard-header h1 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .logout-btn a {
            color: white;
            background-color: #dc3545;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn a i {
            margin-right: 5px;
        }
        
        .logout-btn a:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
       
        .stat-card {
            border-radius: 10px;
            border: none;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-card .count {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-card .title {
            font-size: 18px;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .stat-card.books .icon {
            color: #4e73df;
        }
        
        .stat-card.users .icon {
            color: #1cc88a;
        }
        
        .stat-card.overdue .icon {
            color: #e74a3b;
        }
        
        
        .feature-card {
            border-radius: 10px;
            border: none;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .feature-card .btn {
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .feature-card.books .icon {
            color: #4e73df;
        }
        
        .feature-card.users .icon {
            color: #1cc88a;
        }
        
       
        .chart-card {
            border-radius: 10px;
            border: none;
            background-color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .chart-card .card-header {
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
        }
        
        .chart-card .card-body {
            padding: 20px;
        }
        
      
        .table-card {
            border-radius: 10px;
            border: none;
            background-color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .table-card .card-header {
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-card .card-body {
            padding: 0;
        }
        
        .table-card .table {
            margin-bottom: 0;
        }
        
        .table-card .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table-card .badge {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 30px;
        }
        
        .badge-overdue {
            background-color: #e74a3b;
            color: white;
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
            
            .logout-btn {
                position: static;
                margin-top: 10px;
                text-align: right;
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
            <a class="nav-link active" href="admin_dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="admin_book.php">
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
            <h1><i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard</h1>
            <p>Welcome to the Library Management System</p>
            <div class="logout-btn">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card books">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="count"><?php echo number_format($totalBooks); ?></div>
                    <div class="title">Total Books</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card users">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="count"><?php echo number_format($totalUsers); ?></div>
                    <div class="title">Registered Users</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card overdue">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="count"><?php echo count($overdueBooks); ?></div>
                    <div class="title">Overdue Books</div>
                </div>
            </div>
        </div>

      
        <div class="chart-card">
            <div class="card-header">
                <i class="fas fa-chart-bar me-2"></i> Monthly Book Borrowing Statistics
            </div>
            <div class="card-body">
                <canvas id="borrowingChart" height="300"></canvas>
            </div>
        </div>

        <div class="row">
           
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header">
                        <div><i class="fas fa-star me-2"></i> Top Borrowed Books</div>
                        <a href="admin_book.php" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th class="text-center">Borrow Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topBooks)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No data available</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topBooks as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['Bookname']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $book['borrow_count']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

           
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header">
                        <div><i class="fas fa-exclamation-triangle me-2"></i> Overdue Books</div>
                        <a href="#" class="btn btn-sm btn-light">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Borrower</th>
                                        <th class="text-center">Days Overdue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($overdueBooks)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No overdue books</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($overdueBooks as $book): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['Bookname']); ?></td>
                                                <td><?php echo htmlspecialchars($book['Username']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-overdue"><?php echo $book['days_overdue']; ?> days</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       
        <div class="row">
            <div class="col-md-6">
                <div class="feature-card books">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Manage Books</h3>
                    <p>Add, edit, or remove books from the library collection.</p>
                    <a href="admin_book.php" class="btn btn-primary">Go to Books</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature-card users">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Manage Users</h3>
                    <p>View and manage registered library users.</p>
                    <a href="admin_registered_users.php" class="btn btn-success">Go to Users</a>
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
        
        
        document.addEventListener('DOMContentLoaded', function() {
           
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.getElementById('content').classList.add('collapsed');
            }
            
            
            const ctx = document.getElementById('borrowingChart').getContext('2d');
            const borrowingChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Books Borrowed',
                        data: <?php echo $monthlyBorrowStatsJSON; ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.7)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                                   'July', 'August', 'September', 'October', 'November', 'December'];
                                    return months[tooltipItems[0].dataIndex];
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
