<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    header("Location: login.html");
    exit();
}

$id_pemesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pemesanan == 0) {
    header("Location: dashboard.php");
    exit();
}

include 'connection.php';

// Ambil data pemesanan
$query = "SELECT p.*, m.merek, m.tipe, m.harga_per_hari, pel.nama_lengkap 
          FROM pemesanan p 
          JOIN mobil m ON p.id_mobil = m.id_mobil 
          JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan 
          WHERE p.id_pemesanan = $id_pemesanan";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}

$start = new DateTime($booking['tanggal_mulai']);
$end = new DateTime($booking['tanggal_selesai']);
$jumlah_hari = $start->diff($end)->days;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Booking - SIREMO</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f4f4f4;
    padding: 40px 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.header {
    background: #2563EB;
    color: white;
    padding: 30px;
    text-align: center;
}

.header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.header p {
    opacity: 0.9;
}

.content {
    padding: 30px;
}

.success-icon {
    text-align: center;
    margin-bottom: 20px;
}

.success-icon i {
    font-size: 70px;
    color: #22c55e;
}

.info-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #475569;
}

.info-value {
    color: #1e293b;
    font-weight: 500;
}

.total-price {
    background: #2563EB;
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin: 20px 0;
}

.total-price h3 {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.total-price h2 {
    font-size: 32px;
}

.btn-group {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #2563EB;
    color: white;
}

.btn-primary:hover {
    background: #1e4fc9;
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.btn-success {
    background: #22c55e;
    color: white;
}

.btn-success:hover {
    background: #16a34a;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

@media (max-width: 600px) {
    .btn-group {
        flex-direction: column;
    }
    .container {
        margin: 10px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Booking Berhasil!</h1>
        <p>Pemesanan Anda telah kami terima</p>
    </div>

    <div class="content">
        <div class="success-icon">
            <i class="fa-regular fa-circle-check"></i>
        </div>

        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Kode Booking</span>
                <span class="info-value"><?= $booking['kode_booking'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Nama Pelanggan</span>
                <span class="info-value"><?= htmlspecialchars($booking['nama_lengkap']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Mobil</span>
                <span class="info-value"><?= htmlspecialchars($booking['merek'] . ' ' . $booking['tipe']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Sewa</span>
                <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($booking['tanggal_selesai'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Lama Sewa</span>
                <span class="info-value"><?= $jumlah_hari ?> hari</span>
            </div>
            <div class="info-row">
                <span class="info-label">Opsi Sopir</span>
                <span class="info-value"><?= $booking['opsi_sopir'] == 1 ? 'Dengan Sopir (+Rp150.000/hari)' : 'Lepas Kunci' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value"><span class="status-badge status-pending">Menunggu Pembayaran</span></span>
            </div>
        </div>

        <div class="total-price">
            <h3>Total Pembayaran</h3>
            <h2>Rp<?= number_format($booking['total_harga'], 0, ',', '.') ?></h2>
        </div>

        <div class="btn-group">
            <a href="pembayaran.php?id=<?= $booking['id_pemesanan'] ?>" class="btn btn-success">
                <i class="fa-solid fa-credit-card"></i> Bayar Sekarang
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fa-solid fa-house"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

</body>
</html>