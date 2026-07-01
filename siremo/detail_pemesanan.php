<?php
session_start();

// ========== CEK LOGIN ==========
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "detail_pemesanan.php?id=" . ($_GET['id'] ?? 0);
    header("Location: login.html");
    exit();
}

// Ambil id_pelanggan dari session
$id_pelanggan = $_SESSION['id_pelanggan'] ?? 0;

// Jika menggunakan session 'user' (bukan 'id_pelanggan'), ambil dari database
if ($id_pelanggan == 0 && isset($_SESSION['user'])) {
    include 'connection.php';
    $username = $_SESSION['user'];
    $query_user = "SELECT id_pelanggan FROM pelanggan WHERE username = '$username' OR email = '$username'";
    $result_user = mysqli_query($conn, $query_user);
    if ($row_user = mysqli_fetch_assoc($result_user)) {
        $id_pelanggan = $row_user['id_pelanggan'];
        $_SESSION['id_pelanggan'] = $id_pelanggan;
    }
}

if ($id_pelanggan == 0) {
    header("Location: login.html");
    exit();
}

$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? 'Pelanggan';
$isLoggedIn = true;

include 'connection.php';

// ========== AMBIL ID PEMESANAN DARI URL ==========
$id_pemesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pemesanan == 0) {
    header("Location: riwayat.php");
    exit();
}

// ========== CEK STRUKTUR TABEL ==========
$cek_kolom = "SHOW COLUMNS FROM pemesanan";
$result_kolom = mysqli_query($conn, $cek_kolom);
$kolom_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom)) {
    $kolom_list[] = $row_kolom['Field'];
}

// Tentukan nama kolom yang benar
$kolom_tanggal_mulai = in_array('tanggal_mulai', $kolom_list) ? 'tanggal_mulai' : (in_array('tanggal_sewa', $kolom_list) ? 'tanggal_sewa' : 'tanggal_mulai');
$kolom_tanggal_selesai = in_array('tanggal_selesai', $kolom_list) ? 'tanggal_selesai' : (in_array('tanggal_kembali', $kolom_list) ? 'tanggal_kembali' : 'tanggal_selesai');
$kolom_status = in_array('status_pemesanan', $kolom_list) ? 'status_pemesanan' : (in_array('status', $kolom_list) ? 'status' : 'status_pemesanan');
$kolom_catatan = in_array('catatan', $kolom_list) ? 'catatan' : null;

// ========== AMBIL DATA PEMESANAN ==========
$query = "SELECT 
            p.*, 
            m.merek, 
            m.tipe, 
            m.kapasitas_kursi,
            m.harga_per_hari,
            pel.nama_lengkap as nama_pelanggan,
            pel.email,
            s.nama_sopir
          FROM pemesanan p
          JOIN mobil m ON p.id_mobil = m.id_mobil
          JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
          LEFT JOIN sopir s ON p.id_sopir = s.id_sopir
          WHERE p.id_pemesanan = $id_pemesanan AND p.id_pelanggan = $id_pelanggan";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($result);

// Jika data tidak ditemukan atau bukan milik pelanggan ini
if (!$data) {
    header("Location: riwayat.php");
    exit();
}

// ========== AMBIL TANGGAL DENGAN NAMA KOLOM YANG BENAR ==========
$tanggal_mulai = $data[$kolom_tanggal_mulai] ?? date('Y-m-d');
$tanggal_selesai = $data[$kolom_tanggal_selesai] ?? date('Y-m-d');

// Format tanggal
$data['tanggal_mulai_format'] = date('d M Y', strtotime($tanggal_mulai));
$data['tanggal_selesai_format'] = date('d M Y', strtotime($tanggal_selesai));

// Hitung durasi
$start = new DateTime($tanggal_mulai);
$end = new DateTime($tanggal_selesai);
$diff = $start->diff($end);
$days = $diff->days > 0 ? $diff->days : 1;

// Format total harga
$data['total_harga_format'] = 'Rp' . number_format($data['total_harga'], 0, ',', '.');

// Ambil status dengan nama kolom yang benar
$status_value = $data[$kolom_status] ?? 'Menunggu';

// Status badge dengan warna dan icon yang lebih keren
$status_badge = '';
$status_color = '';
$status_icon = '';
switch ($status_value) {
    case 'Menunggu':
        $status_badge = '<span class="badge badge-warning"><i class="fa-regular fa-clock"></i> Menunggu</span>';
        $status_color = '#fef3c7';
        break;
    case 'Dikonfirmasi':
        $status_badge = '<span class="badge badge-success"><i class="fa-regular fa-circle-check"></i> Dikonfirmasi</span>';
        $status_color = '#d1fae5';
        break;
    case 'Selesai':
        $status_badge = '<span class="badge badge-info"><i class="fa-regular fa-flag-checkered"></i> Selesai</span>';
        $status_color = '#dbeafe';
        break;
    case 'Ditolak':
        $status_badge = '<span class="badge badge-danger"><i class="fa-regular fa-circle-xmark"></i> Ditolak</span>';
        $status_color = '#fee2e2';
        break;
    case 'Batal':
        $status_badge = '<span class="badge badge-secondary"><i class="fa-regular fa-ban"></i> Batal</span>';
        $status_color = '#e5e7eb';
        break;
    default:
        $status_badge = '<span class="badge badge-secondary">' . $status_value . '</span>';
        $status_color = '#e5e7eb';
}

// Ambil catatan jika ada
$catatan = '';
if ($kolom_catatan && isset($data[$kolom_catatan])) {
    $catatan = $data[$kolom_catatan];
}

// Foto mobil
$foto_file = strtolower(str_replace(' ', '', $data['tipe'])) . '.jpg';
if (!file_exists('upload_foto/' . $foto_file)) {
    $foto_file = 'placeholder.jpg';
}
$data['foto_file'] = $foto_file;

// Cek apakah pemesanan bisa di-review (status Selesai)
$can_review = ($status_value == 'Selesai');

// Cek apakah sudah pernah review
$review_exists = false;
if ($can_review) {
    $check_review = mysqli_query($conn, "SELECT id_review FROM review WHERE id_pemesanan = $id_pemesanan");
    $review_exists = mysqli_num_rows($check_review) > 0;
}

// Cek apakah ada sopir
$nama_sopir = $data['nama_sopir'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemesanan - SIREMO</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ========== NAVBAR ========== */
        .navbar {
            background: #fff;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            border-bottom: 1px solid #ddd;
        }
        .nav-left { min-width: 320px; }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo img {
            width: 70px;
            height: 60px;
            object-fit: contain;
        }
        .logo-text h2 {
            font-size: 28px;
            letter-spacing: 12px;
            color: #1f2937;
            line-height: 1;
        }
        .logo-text p {
            font-size: 12px;
            color: #666;
            letter-spacing: 2px;
            margin-top: 6px;
        }
        .nav-menu {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            gap: 50px;
            list-style: none;
        }
        .nav-menu li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            color: #666;
            cursor: pointer;
            padding: 30px 0;
        }
        .nav-menu li a {
            text-decoration: none;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-menu li.active {
            color: #2563EB;
            border-bottom: 3px solid #2563EB;
            font-weight: 600;
        }
        .nav-menu li.active a {
            color: #2563EB;
        }
        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 180px;
            justify-content: flex-end;
        }
        .profile .avatar-icon {
            width: 40px;
            height: 40px;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 20px;
        }
        .profile span {
            font-size: 15px;
            font-weight: 500;
        }
        .btn-logout {
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }

        /* ========== CONTAINER ========== */
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .card-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }
        .card-header h2 i {
            color: #2563EB;
            margin-right: 12px;
        }
        .btn-back {
            background: #e5e7eb;
            color: #374151;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-back:hover {
            background: #d1d5db;
        }
        .btn-back i {
            font-size: 14px;
        }

        .card-body {
            padding: 30px;
        }

        /* ========== STATUS BAR - LEBIH KEREN ========== */
        .status-bar {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        .status-bar .left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .status-bar .left .label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-bar .left .label i {
            color: #2563EB;
            font-size: 16px;
        }
        .status-bar .badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            letter-spacing: 0.3px;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #fcd34d;
        }
        .badge-warning i {
            color: #d97706;
            font-size: 16px;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #6ee7b7;
        }
        .badge-success i {
            color: #059669;
            font-size: 16px;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #93c5fd;
        }
        .badge-info i {
            color: #2563eb;
            font-size: 16px;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fca5a5;
        }
        .badge-danger i {
            color: #dc2626;
            font-size: 16px;
        }
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
            border: 2px solid #d1d5db;
        }
        .badge-secondary i {
            color: #6b7280;
            font-size: 16px;
        }

        .status-bar .status-date {
            font-size: 13px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-bar .status-date i {
            color: #9ca3af;
        }

        /* ========== DETAIL GRID ========== */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .detail-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        .detail-section h4 {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .detail-section h4 i {
            color: #2563EB;
            margin-right: 8px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-item .label {
            color: #6b7280;
            font-size: 14px;
        }
        .detail-item .value {
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }

        .car-image-large {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
        }

        /* ========== TOTAL ========== */
        .total-box {
            background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .total-box .label {
            font-size: 14px;
            opacity: 0.9;
        }
        .total-box .amount {
            font-size: 32px;
            font-weight: 700;
            margin-top: 5px;
        }

        /* ========== BUTTON ========== */
        .btn-review {
            display: inline-block;
            background: #2563EB;
            color: white;
            padding: 10px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: 0.2s;
        }
        .btn-review:hover {
            background: #1e4fc9;
        }

        .review-info {
            background: #f0f6ff;
            padding: 12px 18px;
            border-radius: 8px;
            color: #1e40af;
            font-size: 14px;
            margin-top: 15px;
            border: 1px solid #93c5fd;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 900px) {
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 15px;
                gap: 15px;
            }
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 20px;
            }
            .nav-menu li {
                padding: 10px 0;
            }
        }
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .container {
                padding: 0 15px;
                margin: 20px auto;
            }
            .card-body {
                padding: 20px;
            }
            .status-bar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .status-bar .left {
                justify-content: center;
            }
            .status-bar .badge {
                justify-content: center;
            }
            .status-bar .status-date {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar">
    <div class="nav-left">
        <div class="logo">
            <img src="upload_foto/logo siremo.png" alt="Logo SIREMO">
            <div class="logo-text">
                <h2>SIREMO</h2>
                <p>RENT A CAR, EASY, & TRUSTED</p>
            </div>
        </div>
    </div>

    <ul class="nav-menu">
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Beranda</a></li>
        <li><a href="dashboard.php#mobil"><i class="fa-solid fa-car"></i> Mobil</a></li>
        <li class="active"><a href="riwayat.php"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat</a></li>
        <li><a href="bantuan.php"><i class="fa-regular fa-circle-question"></i> Bantuan</a></li>
    </ul>

    <div class="profile">
        <div class="avatar-icon"><i class="fa-regular fa-user"></i></div>
        <span><?= htmlspecialchars($nama_user) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <h2><i class="fa-regular fa-receipt"></i> Detail Pemesanan</h2>
            <a href="riwayat.php" class="btn-back"><i class="fa-regular fa-arrow-left"></i> Kembali ke Riwayat</a>
        </div>

        <div class="card-body">
            <!-- Status Bar Keren -->
            <div class="status-bar">
                <div class="left">
                    <span class="label">
                        <i class="fa-regular fa-circle-check"></i> Status Pemesanan :
                    </span>
                    <?= $status_badge ?>
                </div>
                <div class="status-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d M Y', strtotime($tanggal_mulai)) ?> - <?= date('d M Y', strtotime($tanggal_selesai)) ?>
                </div>
            </div>

            <div class="detail-grid">
                <!-- Kolom Kiri: Info Mobil -->
                <div class="detail-section">
                    <h4><i class="fa-solid fa-car"></i> Info Mobil</h4>
                    <img src="upload_foto/<?= $data['foto_file'] ?>" 
                         alt="<?= htmlspecialchars($data['tipe']) ?>"
                         class="car-image-large"
                         onerror="this.src='https://via.placeholder.com/800x250?text=Mobil'">
                    <div class="detail-item">
                        <span class="label">Merek & Tipe</span>
                        <span class="value"><?= htmlspecialchars($data['merek'] . ' ' . $data['tipe']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Kapasitas Kursi</span>
                        <span class="value"><?= $data['kapasitas_kursi'] ?> Kursi</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Harga / Hari</span>
                        <span class="value">Rp<?= number_format($data['harga_per_hari'], 0, ',', '.') ?></span>
                    </div>
                    <?php if (!empty($nama_sopir)): ?>
                    <div class="detail-item">
                        <span class="label">Sopir</span>
                        <span class="value"><?= htmlspecialchars($nama_sopir) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Kolom Kanan: Info Pemesanan -->
                <div class="detail-section">
                    <h4><i class="fa-regular fa-calendar"></i> Info Pemesanan</h4>
                    <div class="detail-item">
                        <span class="label">Tanggal Mulai</span>
                        <span class="value"><?= $data['tanggal_mulai_format'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Tanggal Selesai</span>
                        <span class="value"><?= $data['tanggal_selesai_format'] ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Durasi</span>
                        <span class="value"><?= $days ?> Hari</span>
                    </div>
                    <?php if (!empty($catatan)): ?>
                    <div class="detail-item" style="flex-wrap:wrap; gap:5px;">
                        <span class="label">Catatan</span>
                        <span class="value" style="font-weight:400; font-size:13px; color:#4b5563;"><?= nl2br(htmlspecialchars($catatan)) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Total -->
            <div class="total-box">
                <div class="label">Total yang Harus Dibayarkan</div>
                <div class="amount"><?= $data['total_harga_format'] ?></div>
            </div>

            <!-- Tombol Review (hanya jika status Selesai) -->
            <?php if ($can_review): ?>
                <?php if ($review_exists): ?>
                    <div class="review-info">
                        <i class="fa-regular fa-circle-check"></i> Anda sudah memberikan review untuk pemesanan ini.
                    </div>
                <?php else: ?>
                    <div style="text-align:center; margin-top:20px;">
                        <a href="review.php?id=<?= $id_pemesanan ?>" class="btn-review">
                            <i class="fa-regular fa-star"></i> Beri Review
                        </a>
                    </div>
                <?php endif; ?>
            <?php elseif ($status_value == 'Menunggu'): ?>
                <div style="text-align:center; margin-top:20px;">
                    <p style="color:#6b7280; font-size:14px; margin-bottom:10px;">
                        <i class="fa-regular fa-clock"></i> Pesanan masih menunggu konfirmasi. 
                        Silahkan upload bukti pembayaran.
                    </p>
                    <a href="proses_booking.php" class="btn-review">
                        <i class="fa-regular fa-upload"></i> Upload Bukti Pembayaran
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer" style="background: #1f2937; color: #fff; padding: 30px 25px 20px; margin-top: 30px;">
    <div class="footer-container" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 40px;">
        <div class="footer-col">
            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #fff;">
                <i class="fa-solid fa-building" style="margin-right:10px; color:#2563EB;"></i> SIREMO
            </h4>
            <p style="color: #cbd5e1; font-size: 14px; line-height: 1.8;">Rent a Car, Easy, & Trusted</p>
            <p style="font-size:13px; color:#9ca3af; margin-top:10px;">
                SIREMO adalah penyedia layanan sewa mobil terpercaya yang berkomitmen memberikan pengalaman perjalanan nyaman dan aman bagi pelanggan.
            </p>
        </div>

        <div class="footer-col">
            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #fff;">
                <i class="fa-solid fa-location-dot" style="margin-right:10px; color:#2563EB;"></i> Alamat Kami
            </h4>
            <address style="font-style:normal; color:#cbd5e1; font-size:14px; line-height:1.8;">
                <p><i class="fa-solid fa-store" style="width:20px; color:#2563EB; margin-right:8px;"></i> SIREMO Rent A Car</p>
                <p><i class="fa-solid fa-map-pin" style="width:20px; color:#2563EB; margin-right:8px;"></i> Jl. Raya Kampus No. 123</p>
                <p><i class="fa-solid fa-city" style="width:20px; color:#2563EB; margin-right:8px;"></i> Kec. Lowokwaru, Kota Malang</p>
                <p><i class="fa-solid fa-map" style="width:20px; color:#2563EB; margin-right:8px;"></i> Jawa Timur, Indonesia 65141</p>
                <p style="margin-top:10px;"><i class="fa-solid fa-phone" style="width:20px; color:#2563EB; margin-right:8px;"></i> +62 812-3456-7890</p>
                <p><i class="fa-solid fa-envelope" style="width:20px; color:#2563EB; margin-right:8px;"></i> info@siremo.com</p>
                <p><i class="fa-solid fa-clock" style="width:20px; color:#2563EB; margin-right:8px;"></i> Buka: Senin - Minggu (08.00 - 21.00 WIB)</p>
            </address>
        </div>

        <div class="footer-col">
            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 15px; color: #fff;">
                <i class="fa-solid fa-link" style="margin-right:10px; color:#2563EB;"></i> Tautan Cepat
            </h4>
            <p><a href="dashboard.php" style="color:#cbd5e1; text-decoration:none;">Beranda</a></p>
            <p><a href="dashboard.php#mobil" style="color:#cbd5e1; text-decoration:none;">Daftar Mobil</a></p>
            <p><a href="riwayat.php" style="color:#cbd5e1; text-decoration:none;">Riwayat Pemesanan</a></p>
            <p><a href="bantuan.php" style="color:#cbd5e1; text-decoration:none;">Bantuan</a></p>
        </div>
    </div>
    <div class="footer-bottom" style="max-width:1200px; margin:0 auto; border-top:1px solid #374151; padding-top:15px; margin-top:20px; text-align:center; font-size:13px; color:#9ca3af;">
        &copy; <?= date('Y') ?> SIREMO Rent A Car. All Rights Reserved.
    </div>
</footer>

</body>
</html>