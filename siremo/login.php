<?php
session_start();
include 'connection.php';

$email = mysqli_real_escape_string($conn, trim($_POST['email']));
$kata_sandi = md5(trim($_POST['kata_sandi']));

$query = "SELECT * FROM pelanggan WHERE email='$email' AND kata_sandi='$kata_sandi'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Cek apakah email terdaftar
    $check_email = mysqli_query($conn, "SELECT * FROM pelanggan WHERE email='$email'");
    if (mysqli_num_rows($check_email) == 0) {
        echo "<script>alert('Email tidak terdaftar!'); window.history.back();</script>";
    } else {
        echo "<script>alert('Password salah!'); window.history.back();</script>";
    }
} else {
    $row = mysqli_fetch_assoc($result);
    $_SESSION['id_pelanggan'] = $row['id_pelanggan'];
    $_SESSION['nama'] = $row['nama_lengkap'];
    $_SESSION['email'] = $row['email'];

    
}
?>