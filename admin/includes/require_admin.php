<?php

require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['admin_user_id'])) {
    header('Location: index.php');
    exit;
}
