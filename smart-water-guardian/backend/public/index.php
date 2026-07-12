<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Water Guardian</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div id="app">
        <?php
        $page = $_GET['page'] ?? 'login';
        $role = $_SESSION['role'] ?? null;
        
        // Check if user is logged in
        if (isset($_SESSION['token'])) {
            switch ($role) {
                case 'consumer':
                    include 'views/consumer/dashboard.php';
                    break;
                case 'municipal':
                    include 'views/municipal/dashboard.php';
                    break;
                case 'admin':
                    include 'views/admin/dashboard.php';
                    break;
                default:
                    include 'views/auth/login.php';
            }
        } else {
            if ($page === 'register') {
                include 'views/auth/register.php';
            } else {
                include 'views/auth/login.php';
            }
        }
        ?>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
