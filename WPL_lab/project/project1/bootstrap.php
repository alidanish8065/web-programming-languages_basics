<?php
// bootstrap.php – runs once per request

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/config.php';
require_once __DIR__ . '/public/dbconfig.php';


