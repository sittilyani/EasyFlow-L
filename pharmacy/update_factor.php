<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['factor'])) {
    $_SESSION['factor'] = $_POST['factor'];
    echo 'OK';
}