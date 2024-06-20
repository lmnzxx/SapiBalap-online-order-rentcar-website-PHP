<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "sapibalap"; 

// Buat koneksi
$mysqli = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
