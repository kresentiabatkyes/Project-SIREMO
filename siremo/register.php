<?php
session_start();
include 'connection.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nomor_telepon = mysqli_real_escape_string($conn, trim($_POST['telepon']));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $kata_sandi = isset($_POST['kata_sandi']) ? md5(trim($_POST['kata_sandi'])) : '';

    $check_email = mysqli_query($conn, "SELECT * FROM pelanggan WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "Email sudah terdaftar! Gunakan email lain.";
    } else {
        $query = "INSERT INTO pelanggan (nama_lengkap, email, kata_sandi, nomor_telepon, alamat) 
                  VALUES ('$nama_lengkap', '$email', '$kata_sandi', '$nomor_telepon', '$alamat')";
        
        if (mysqli_query($conn, $query)) {
            $success = "Pendaftaran berhasil! Silakan login.";
        } else {
            $error = "Pendaftaran gagal: " . mysqli_error($conn);
        }
    }
    header("Location: dashboard.php");
    exit();
}
?>