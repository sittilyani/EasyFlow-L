<?php
$host = 'host.docker.internal';
$username = 'root';
$password = 'root-pwd';
$database = 'methadone';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>