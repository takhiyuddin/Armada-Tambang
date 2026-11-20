<?php
require_once __DIR__ . '/includes/init.php';

if (!empty($_SESSION['id_user'])) {
    redirect('dashboard.php');
}
redirect('login.php');
