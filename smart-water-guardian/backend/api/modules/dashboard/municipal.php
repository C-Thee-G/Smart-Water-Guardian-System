<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';
require_once '../../middleware/auth_middleware.php';

$auth = new AuthMiddleware();
$user = $auth->validateToken();

if ($user['role'] !== 'municipal' && $
