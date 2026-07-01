<?php
session_start();

// ========== CEK LOGIN ==========
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "riwayat.php";
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

// Jika masih 0, redirect ke login
if ($id_pelanggan == 0) {
    header("Location: login.html");
    exit();
}

$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? 'Pelanggan';
$isLoggedIn = true;

include 'connection.php';

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
$kolom_order = in_array('tanggal_pemesanan', $kolom_list) ? 'p.tanggal_pemesanan' : 'p.id_pemesanan';

// ========== AMBIL DATA PEMESANAN ==========
$query = "SELECT 
            p.*, 
            m.merek, 
            m.tipe, 
            m.kapasitas_kursi,
            s.nama_sopir
          FROM pemesanan p
          JOIN mobil m ON p.id_mobil = m.id_mobil
          LEFT JOIN sopir s ON p.id_sopir = s.id_sopir
          WHERE p.id_pelanggan = $id_pelanggan
          ORDER BY $kolom_order DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

$pemesanan_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Format harga
    $row['total_harga_format'] = 'Rp' . number_format($row['total_harga'], 0, ',', '.');
    
    // Format tanggal - gunakan nama kolom yang sudah dideteksi
    $tanggal_mulai = $row[$kolom_tanggal_mulai] ?? date('Y-m-d');
    $tanggal_selesai = $row[$kolom_tanggal_selesai] ?? date('Y-m-d');
    
    $row['tanggal_mulai_format'] = date('d M Y', strtotime($tanggal_mulai));
    $row['tanggal_selesai_format'] = date('d M Y', strtotime($tanggal_selesai));
    
    // Nama mobil lengkap
    $row['nama_mobil'] = $row['merek'] . ' ' . $row['tipe'] . ' (' . $row['kapasitas_kursi'] . ' Kursi)';
    
    // Ambil status dengan nama kolom yang benar
    $status_value = $row[$kolom_status] ?? 'Menunggu';
    $status_clean = strtolower(trim($status_value));
    
    // Badge status dengan warna
    $status_badge = '';
    switch ($status_clean) {
        case 'menunggu':
        case 'pending':
            $status_badge = '<span class="badge badge-warning"><i class="fa-regular fa-clock"></i> Menunggu</span>';
            break;
        case 'dikonfirmasi':
        case 'confirmed':
            $status_badge = '<span class="badge badge-success"><i class="fa-regular fa-circle-check"></i> Dikonfirmasi</span>';
            break;
        case 'selesai':
        case 'completed':
        case 'done':
        case 'selsai':
            $status_badge = '<span class="badge badge-info"><i class="fa-regular fa-flag-checkered"></i> Selesai</span>';
            break;
        case 'ditolak':
        case 'rejected':
        case 'declined':
            $status_badge = '<span class="badge badge-danger"><i class="fa-regular fa-circle-xmark"></i> Ditolak</span>';
            break;
        case 'batal':
        case 'canceled':
        case 'cancelled':
            $status_badge = '<span class="badge badge-secondary"><i class="fa-regular fa-ban"></i> Batal</span>';
            break;
        default:
            $status_badge = '<span class="badge badge-secondary">' . $status_value . '</span>';
    }
    $row['status_badge'] = $status_badge;
    
    // ========== AKSI BERDASARKAN STATUS (PAKAI strpos biar lebih fleksibel) ==========
    $aksi = '';
    
    // Cek apakah status mengandung kata "selesai" atau "completed" atau "done"
    if (strpos($status_clean, 'selesai') !== false || 
        strpos($status_clean, 'completed') !== false || 
        strpos($status_clean, 'done') !== false) {
        // Selesai → LANGSUNG ke Review
        $aksi = '<a href="review.php?id=' . $row['id_pemesanan'] . '" class="btn-aksi btn-review">
                    <i class="fa-regular fa-star"></i> Review
                </a>';
    } elseif (strpos($status_clean, 'menunggu') !== false || strpos($status_clean, 'pending') !== false) {
        // Menunggu → Lihat Detail (untuk upload bukti)
        $aksi = '<a href="detail_pemesanan.php?id=' . $row['id_pemesanan'] . '" class="btn-aksi btn-detail">
                    <i class="fa-regular fa-eye"></i> Lihat Detail
                </a>';
    } else {
        // Lainnya (Dikonfirmasi, Ditolak, Batal) → Lihat Detail
        $aksi = '<a href="detail_pemesanan.php?id=' . $row['id_pemesanan'] . '" class="btn-aksi btn-detail">
                    <i class="fa-regular fa-eye"></i> Lihat Detail
                </a>';
    }
    $row['aksi'] = $aksi;
    
    // Foto mobil (buat dari nama tipe)
    $foto_file = strtolower(str_replace(' ', '', $row['tipe'])) . '.jpg';
    if (!file_exists('upload_foto/' . $foto_file)) {
        $foto_file = 'placeholder.jpg';
    }
    $row['foto_file'] = $foto_file;
    
    $pemesanan_list[] = $row;
}

$total_pemesanan = count($pemesanan_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - SIREMO</title>
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

        /* ========== MAIN CONTENT ========== */
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            flex: 1;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1f2937;
        }
        .page-header h1 i {
            color: #2563EB;
            margin-right: 12px;
        }
        .page-header p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        /* ========== SUCCESS MESSAGE ========== */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .alert-success i {
            font-size: 20px;
            color: #10b981;
        }

        /* ========== TABLE ========== */
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }
        thead th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        tbody tr:hover {
            background: #fafbfc;
        }
        tbody td {
            padding: 14px 16px;
            vertical-align: middle;
        }

        /* Mobil column */
        .car-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .car-cell img {
            width: 50px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }
        .car-cell .car-name {
            font-weight: 500;
            color: #1f2937;
            font-size: 13px;
        }

        /* ========== BADGE STATUS ========== */
        .badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge-warning i {
            color: #d97706;
        }
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .badge-success i {
            color: #059669;
        }
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .badge-info i {
            color: #2563eb;
        }
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .badge-danger i {
            color: #dc2626;
        }
        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .badge-secondary i {
            color: #6b7280;
        }

        /* ========== BUTTON AKSI ========== */
        .btn-aksi {
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-detail {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-detail:hover {
            background: #d1d5db;
        }
        .btn-review {
            background: #dbeafe;
            color: #1e40af;
        }
        .btn-review:hover {
            background: #bfdbfe;
        }

        /* ========== PAGINATION ========== */
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 15px;
        }
        .pagination-info {
            color: #6b7280;
            font-size: 14px;
        }
        .pagination-buttons {
            display: flex;
            gap: 6px;
        }
        .pagination-buttons .page-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            font-size: 14px;
        }
        .pagination-buttons .page-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }
        .pagination-buttons .page-btn.active {
            background: #2563EB;
            color: white;
            border-color: #2563EB;
        }
        .pagination-buttons .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* ========== KOSONG ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 60px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #6b7280;
            margin-bottom: 20px;
        }
        .empty-state .btn-primary {
            background: #2563EB;
            color: white;
            padding: 10px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            transition: 0.2s;
        }
        .empty-state .btn-primary:hover {
            background: #1e4fc9;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #1f2937;
            color: #fff;
            padding: 30px 25px 20px;
            margin-top: 30px;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
        }
        .footer-col h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #fff;
        }
        .footer-col h4 i {
            margin-right: 10px;
            color: #2563EB;
        }
        .footer-col p {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 5px;
        }
        .footer-col .alamat i {
            width: 20px;
            color: #2563EB;
            margin-right: 8px;
        }
        .footer-col .social {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        .footer-col .social a {
            color: #cbd5e1;
            font-size: 18px;
            transition: 0.2s;
        }
        .footer-col .social a:hover {
            color: #2563EB;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            border-top: 1px solid #374151;
            padding-top: 15px;
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
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
            .container {
                padding: 20px;
                width: 98%;
            }
            .pagination-wrapper {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .footer-container {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }
            .footer-col .social {
                justify-content: center;
            }
        }
        @media (max-width: 768px) {
            table {
                font-size: 12px;
            }
            thead th, tbody td {
                padding: 10px 12px;
            }
            .car-cell img {
                width: 40px;
                height: 32px;
            }
            .btn-aksi {
                font-size: 11px;
                padding: 4px 12px;
            }
        }
        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
            .page-header h1 {
                font-size: 22px;
            }
            .table-responsive {
                overflow-x: auto;
            }
            table {
                min-width: 600px;
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
    <div class="page-header">
        <h1><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pesanan</h1>
        <p>Berikut adalah daftar riwayat pemesanan mobil yang pernah Anda lakukan</p>
    </div>

    <!-- Success Message -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div>
                <strong>Pemesanan Berhasil!</strong> Silahkan upload bukti pembayaran untuk memverifikasi pesanan Anda.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($total_pemesanan > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mobil</th>
                        <th>Tanggal Sewa</th>
                        <th>Tanggal Kembali</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pemesanan_list as $row): ?>
                        <tr>
                            <td>
                                <div class="car-cell">
                                    <img src="upload_foto/<?= $row['foto_file'] ?>" 
                                         alt="<?= htmlspecialchars($row['tipe']) ?>"
                                         onerror="this.src='https://via.placeholder.com/50x40?text=Mobil'">
                                    <span class="car-name"><?= htmlspecialchars($row['nama_mobil']) ?></span>
                                </div>
                            </td>
                            <td><?= $row['tanggal_mulai_format'] ?></td>
                            <td><?= $row['tanggal_selesai_format'] ?></td>
                            <td><strong><?= $row['total_harga_format'] ?></strong></td>
                            <td><?= $row['status_badge'] ?></td>
                            <td><?= $row['aksi'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper">
            <div class="pagination-info">
                Menampilkan 1-<?= $total_pemesanan ?> dari <?= $total_pemesanan ?> pemesanan
            </div>
            <div class="pagination-buttons">
                <a href="#" class="page-btn disabled"><i class="fa-solid fa-chevron-left"></i></a>
                <a href="#" class="page-btn active">1</a>
                <a href="#" class="page-btn disabled"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar-circle-plus"></i>
            <h3>Belum Ada Pemesanan</h3>
            <p>Anda belum melakukan pemesanan mobil. Yuk, sewa mobil sekarang!</p>
            <a href="dashboard.php#mobil" class="btn-primary"><i class="fa-solid fa-car"></i> Cari Mobil</a>
        </div>
    <?php endif; ?>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h4><i class="fa-solid fa-building"></i> SIREMO</h4>
            <p>Rent a Car, Easy, & Trusted</p>
            <p style="font-size:13px; color:#9ca3af; margin-top:10px;">
                SIREMO adalah penyedia layanan sewa mobil terpercaya yang berkomitmen memberikan pengalaman perjalanan nyaman dan aman bagi pelanggan.
            </p>
            <div class="social">
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
                <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="#"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>

        <div class="footer-col">
            <h4><i class="fa-solid fa-location-dot"></i> Alamat Kami</h4>
            <address class="alamat">
                <p><i class="fa-solid fa-store"></i> SIREMO Rent A Car</p>
                <p><i class="fa-solid fa-map-pin"></i> Jl. Raya Kampus No. 123</p>
                <p><i class="fa-solid fa-city"></i> Kec. Lowokwaru, Kota Malang</p>
                <p><i class="fa-solid fa-map"></i> Jawa Timur, Indonesia 65141</p>
                <p style="margin-top:10px;"><i class="fa-solid fa-phone"></i> +62 812-3456-7890</p>
                <p><i class="fa-solid fa-envelope"></i> info@siremo.com</p>
                <p><i class="fa-solid fa-clock"></i> Buka: Senin - Minggu (08.00 - 21.00 WIB)</p>
            </address>
        </div>

        <div class="footer-col">
            <h4><i class="fa-solid fa-link"></i> Tautan Cepat</h4>
            <p><a href="dashboard.php" style="color:#cbd5e1; text-decoration:none;">Beranda</a></p>
            <p><a href="dashboard.php#mobil" style="color:#cbd5e1; text-decoration:none;">Daftar Mobil</a></p>
            <p><a href="riwayat.php" style="color:#cbd5e1; text-decoration:none;">Riwayat Pemesanan</a></p>
            <p><a href="bantuan.php" style="color:#cbd5e1; text-decoration:none;">Bantuan</a></p>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> SIREMO Rent A Car. All Rights Reserved.
    </div>
</footer>

</body>
</html>