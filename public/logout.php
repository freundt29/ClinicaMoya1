<?php
require_once __DIR__ . '/../backend/helpers/session.php';

session_logout();
header('Location: /proyecto1moya/public/login.php');
exit;
