<?php

require_once "../templates/config.php";


$successMessage = '';
$errorMessage = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = htmlspecialchars($_POST['user_id']);
    try {
      
        $pdo->beginTransaction();
        
        
        $stmt = $pdo->prepare("DELETE FROM user WHERE User_ID = :user_id");
        $stmt->execute(['user_id' => $userId]);
        
        
        $pdo->commit();
        
        $successMessage = "User deleted successfully!";
    } catch (PDOException $e) {
       
        $pdo->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        $errorMessage = "Error deleting user: " . $e->getMessage();
    }
}


try {
    $users = $pdo->query("
        SELECT User_ID, Username, Email, Password, Role, Created_time
        FROM user
        ORDER BY User_ID
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $errorMessage = "Error fetching users. Please try again later.";
}


try {
    $admins = $pdo->query("
        SELECT Admin_ID, Admin_name, Admin_Email, Password
        FROM admin
        ORDER BY Admin_ID
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching administrators: " . $e->getMessage());
    $admins = [];
    $errorMessage = "Error fetching administrators. Please try again later.";
}


$totalUsers = count($users) + count($admins);
$adminCount = count($admins);
$userCount = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registered Users | Library System</title>
   
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
        
        
        .stats-row {
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .stat-card .count {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .title {
            font-size: 16px;
            color: #6c757d;
        }
        
        .stat-card.total .icon {
            color: #4e73df;
        }
        
        .stat-card.admin .icon {
            color: #e74a3b;
        }
        
        .stat-card.user .icon {
            color: #f6c23e;
        }
        
       
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .table-container h2 {
            color: #343a40;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .table-container h2 i {
            margin-right: 10px;
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
        
        .badge {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 30px;
        }
        
        .badge-admin {
            background-color: #e74a3b;
            color: white;
        }
        
        .badge-user {
            background-color: #f6c23e;
            color: white;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin-right: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        
        .password-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            
            .table-container {
                padding: 15px;
            }
            
            .stat-card {
                margin-bottom: 15px;
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
            <a class="nav-link" href="admin_book.php">
                <i class="fas fa-book me-2"></i>
                <span>Books</span>
            </a>
            <a class="nav-link active" href="admin_registered_users.php">
                <i class="fas fa-users me-2"></i>
                <span>Registered Users</span>
            </a>
        </nav>
    </div>

    
    <div class="content" id="content">
        <div class="dashboard-header">
            <h1><i class="fas fa-users me-2"></i> Registered Users</h1>
            <p>Manage all registered users in the library system</p>
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

       
        <div class="row stats-row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stat-card total">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="count"><?php echo $totalUsers; ?></div>
                    <div class="title">Total Users</div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stat-card admin">
                    <div class="icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="count"><?php echo $adminCount; ?></div>
                    <div class="title">Administrators</div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="stat-card user">
                    <div class="icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="count"><?php echo $userCount; ?></div>
                    <div class="title">Regular Users</div>
                </div>
            </div>
        </div>

       
        <div class="table-container">
            <h2><i class="fas fa-user"></i> Regular Users</h2>
            
          
            <div class="search-box">
                <div class="input-group">
                    <input type="text" class="form-control" id="userSearchInput" placeholder="Search by username, email or role...">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Created Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['User_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td class="password-cell"><?php echo htmlspecialchars($user['Password']); ?></td>
                                    <td>
                                        <?php 
                                        $role = htmlspecialchars($user['Role'] ?? 'user');
                                        $badgeClass = 'badge-user';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($role); ?></span>
                                    </td>
                                    <td><?php echo isset($user['Created_time']) ? date('Y-m-d H:i:s', strtotime($user['Created_time'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-danger btn-action" 
                                                    onclick="confirmDelete('<?php echo $user['User_ID']; ?>', '<?php echo $user['Username']; ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        
        
        <div class="table-container">
            <h2><i class="fas fa-user-shield"></i> Administrators</h2>
            
           
            <div class="search-box">
                <div class="input-group">
                    <input type="text" class="form-control" id="adminSearchInput" placeholder="Search by name or email...">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Admin ID</th>
                            <th>Admin Name</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No administrators found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['Admin_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['Admin_name']); ?></td>
                                    <td><?php echo htmlspecialchars($admin['Admin_Email']); ?></td>
                                    <td class="password-cell"><?php echo htmlspecialchars($admin['Password']); ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <button type="button" class="btn btn-sm btn-primary btn-action" disabled>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
       
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

   
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user <span id="deleteUserName" class="fw-bold"></span>?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="">
                        <input type="hidden" name="delete_user" value="1">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
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
        
     
        function confirmDelete(userId, username) {
            document.getElementById('deleteUserName').textContent = username;
            document.getElementById('deleteUserId').value = userId;
            
          
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
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
            
           
            const userSearchInput = document.getElementById('userSearchInput');
            if (userSearchInput) {
                userSearchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const tableRows = document.querySelectorAll('.table:first-of-type tbody tr');
                    
                    tableRows.forEach(function(row) {
                        const username = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();
                        const role = row.cells[4].textContent.toLowerCase();
                        
                        if (username.includes(searchValue) || email.includes(searchValue) || role.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            
            const adminSearchInput = document.getElementById('adminSearchInput');
            if (adminSearchInput) {
                adminSearchInput.addEventListener('keyup', function() {
                    const searchValue = this.value.toLowerCase();
                    const tableRows = document.querySelectorAll('.table:last-of-type tbody tr');
                    
                    tableRows.forEach(function(row) {
                        const adminName = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();
                        
                        if (adminName.includes(searchValue) || email.includes(searchValue)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
