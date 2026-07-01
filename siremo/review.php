<?php
session_start();

// ========== CEK LOGIN ==========
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "review.php?id=" . ($_GET['id'] ?? 0);
    header("Location: login.html");
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'] ?? 0;

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

include 'connection.php';

$id_pemesanan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pemesanan == 0) {
    header("Location: riwayat.php");
    exit();
}

// ========== CEK STRUKTUR TABEL PEMESANAN ==========
$cek_kolom = "SHOW COLUMNS FROM pemesanan";
$result_kolom = mysqli_query($conn, $cek_kolom);
$kolom_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom)) {
    $kolom_list[] = $row_kolom['Field'];
}

$kolom_status = in_array('status_pemesanan', $kolom_list) ? 'status_pemesanan' : (in_array('status', $kolom_list) ? 'status' : 'status_pemesanan');

// ========== CEK PEMESANAN ==========
// Cek apakah pemesanan milik pelanggan ini (status tidak perlu Selesai, biarkan semua)
$query_check = "SELECT p.*, m.merek, m.tipe, m.kapasitas_kursi 
                FROM pemesanan p 
                JOIN mobil m ON p.id_mobil = m.id_mobil 
                WHERE p.id_pemesanan = $id_pemesanan 
                AND p.id_pelanggan = $id_pelanggan";

$result_check = mysqli_query($conn, $query_check);
$data = mysqli_fetch_assoc($result_check);

// Jika data tidak ditemukan
if (!$data) {
    $_SESSION['error'] = "Pemesanan tidak ditemukan!";
    header("Location: riwayat.php");
    exit();
}

// Ambil status
$status_value = $data[$kolom_status] ?? 'Menunggu';
$status_lower = strtolower($status_value);

// Jika status bukan Selesai, beri peringatan tapi tetap bisa review (atau bisa diubah)
// Tapi lebih baik tetap kasih akses review kalau user mau
if ($status_lower != 'selesai' && $status_lower != 'completed' && $status_lower != 'done') {
    // Tampilkan peringatan tapi tetap bisa review
    $warning = "Pemesanan ini belum selesai. Review bisa dilakukan setelah pemesanan selesai.";
}

// ========== CEK STRUKTUR TABEL REVIEW ==========
$cek_kolom_review = "SHOW COLUMNS FROM review";
$result_kolom_review = mysqli_query($conn, $cek_kolom_review);
$kolom_review_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom_review)) {
    $kolom_review_list[] = $row_kolom['Field'];
}

$kolom_id_pemesanan = in_array('id_pemesanan', $kolom_review_list) ? 'id_pemesanan' : null;
$kolom_id_mobil = in_array('id_mobil', $kolom_review_list) ? 'id_mobil' : null;

// Cek apakah sudah pernah review
$review_exists = false;
if ($kolom_id_pemesanan) {
    $check_review = mysqli_query($conn, "SELECT id_review FROM review WHERE id_pemesanan = $id_pemesanan");
    $review_exists = mysqli_num_rows($check_review) > 0;
} else {
    $check_review = mysqli_query($conn, "SELECT id_review FROM review WHERE id_pelanggan = $id_pelanggan AND id_mobil = {$data['id_mobil']}");
    $review_exists = mysqli_num_rows($check_review) > 0;
}

if ($review_exists) {
    $_SESSION['error'] = "Anda sudah memberikan review untuk pemesanan ini.";
    header("Location: detail_pemesanan.php?id=" . $id_pemesanan);
    exit();
}

$error = '';
$warning = $warning ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = intval($_POST['rating']);
    $komentar = mysqli_real_escape_string($conn, $_POST['komentar']);
    
    if ($rating < 1 || $rating > 5) {
        $error = "Rating harus antara 1-5!";
    } elseif (strlen($komentar) < 10) {
        $error = "Komentar minimal 10 karakter!";
    } else {
        // Build query insert berdasarkan kolom yang ada
        if ($kolom_id_pemesanan && $kolom_id_mobil) {
            $query_insert = "INSERT INTO review (id_pelanggan, id_mobil, id_pemesanan, rating, komentar, tanggal_review) 
                             VALUES ($id_pelanggan, {$data['id_mobil']}, $id_pemesanan, $rating, '$komentar', NOW())";
        } elseif ($kolom_id_mobil) {
            $query_insert = "INSERT INTO review (id_pelanggan, id_mobil, rating, komentar, tanggal_review) 
                             VALUES ($id_pelanggan, {$data['id_mobil']}, $rating, '$komentar', NOW())";
        } else {
            $query_insert = "INSERT INTO review (id_pelanggan, rating, komentar, tanggal_review) 
                             VALUES ($id_pelanggan, $rating, '$komentar', NOW())";
        }
        
        if (mysqli_query($conn, $query_insert)) {
            $_SESSION['success'] = "Review berhasil dikirim! Terima kasih atas ulasannya.";
            header("Location: detail_pemesanan.php?id=" . $id_pemesanan);
            exit();
        } else {
            $error = "Gagal menyimpan review: " . mysqli_error($conn);
        }
    }
}

// Foto mobil
$foto_file = strtolower(str_replace(' ', '', $data['tipe'])) . '.jpg';
if (!file_exists('upload_foto/' . $foto_file)) {
    $foto_file = 'placeholder.jpg';
}
$data['foto_file'] = $foto_file;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Review - SIREMO</title>
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
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 35px;
        }

        .card-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .card-header h2 {
            font-size: 26px;
            font-weight: 600;
            color: #1f2937;
        }
        .card-header h2 i {
            color: #f7b500;
            margin-right: 12px;
        }
        .card-header p {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }

        /* ========== CAR INFO ========== */
        .car-info-review {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid #e5e7eb;
        }
        .car-info-review img {
            width: 60px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .car-info-review .detail {
            flex: 1;
        }
        .car-info-review .detail .name {
            font-weight: 600;
            color: #1f2937;
            font-size: 15px;
        }
        .car-info-review .detail .spec {
            font-size: 13px;
            color: #6b7280;
        }

        /* ========== WARNING ========== */
        .warning-message {
            background: #fef3c7;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcd34d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning-message i {
            font-size: 18px;
            color: #d97706;
        }

        /* ========== FORM ========== */
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-group label .required {
            color: #ef4444;
            margin-left: 4px;
        }
        .form-group label i {
            color: #2563EB;
            margin-right: 6px;
        }

        .rating-stars {
            display: flex;
            gap: 10px;
            font-size: 44px;
            cursor: pointer;
            user-select: none;
            padding: 5px 0;
        }
        .rating-stars .star {
            color: #d1d5db;
            transition: all 0.2s;
        }
        .rating-stars .star.active {
            color: #f7b500;
        }
        .rating-stars .star:hover {
            transform: scale(1.15);
        }

        .rating-text {
            display: block;
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
            min-height: 24px;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 120px;
            outline: none;
            transition: 0.2s;
        }
        .form-group textarea:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-group textarea::placeholder {
            color: #9ca3af;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fca5a5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .error-message i {
            font-size: 18px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
        .btn-submit {
            background: #2563EB;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        .btn-submit:hover {
            background: #1e4fc9;
        }
        .btn-cancel {
            background: #e5e7eb;
            color: #374151;
            padding: 12px 35px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        .btn-cancel:hover {
            background: #d1d5db;
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
        @media (max-width: 600px) {
            .card {
                padding: 20px;
            }
            .card-header h2 {
                font-size: 22px;
            }
            .rating-stars {
                font-size: 34px;
                gap: 6px;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn-submit, .btn-cancel {
                padding: 12px 20px;
            }
            .car-info-review {
                flex-direction: column;
                text-align: center;
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
        <div class="card-header">
            <h2><i class="fa-regular fa-star"></i> Beri Review</h2>
            <p>Bagikan pengalaman Anda menggunakan mobil ini</p>
        </div>

        <!-- Info Mobil -->
        <div class="car-info-review">
            <img src="upload_foto/<?= $data['foto_file'] ?>" 
                 alt="<?= htmlspecialchars($data['tipe']) ?>"
                 onerror="this.src='https://via.placeholder.com/60x50?text=Mobil'">
            <div class="detail">
                <div class="name"><?= htmlspecialchars($data['merek'] . ' ' . $data['tipe']) ?></div>
                <div class="spec">
                    <i class="fa-regular fa-calendar"></i> 
                    <?= date('d M Y', strtotime($data['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($data['tanggal_selesai'])) ?>
                    &nbsp;|&nbsp; <i class="fa-regular fa-user"></i> <?= $data['kapasitas_kursi'] ?> Kursi
                </div>
            </div>
        </div>

        <!-- Warning jika status belum Selesai -->
        <?php if ($warning): ?>
            <div class="warning-message">
                <i class="fa-regular fa-triangle-exclamation"></i> <?= $warning ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fa-regular fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Rating Bintang -->
            <div class="form-group">
                <label>
                    <i class="fa-regular fa-star"></i> Rating Anda 
                    <span class="required">*</span>
                </label>
                <div class="rating-stars" id="ratingStars">
                    <i class="fa-regular fa-star star" data-value="1"></i>
                    <i class="fa-regular fa-star star" data-value="2"></i>
                    <i class="fa-regular fa-star star" data-value="3"></i>
                    <i class="fa-regular fa-star star" data-value="4"></i>
                    <i class="fa-regular fa-star star" data-value="5"></i>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
                <span class="rating-text" id="ratingText">
                    <i class="fa-regular fa-hand-point-up" style="color:#2563EB;"></i> 
                    Klik bintang untuk memberi rating
                </span>
            </div>

            <!-- Komentar -->
            <div class="form-group">
                <label>
                    <i class="fa-regular fa-comment"></i> Komentar 
                    <span class="required">*</span>
                </label>
                <textarea name="komentar" placeholder="Ceritakan pengalaman Anda menggunakan mobil ini. Apa yang Anda sukai? Ada saran untuk kami?" required></textarea>
                <small style="color:#9ca3af; font-size:12px; display:block; margin-top:5px;">
                    <i class="fa-regular fa-info-circle"></i> Minimal 10 karakter
                </small>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn-submit">
                    <i class="fa-regular fa-paper-plane"></i> Kirim Review
                </button>
                <a href="detail_pemesanan.php?id=<?= $id_pemesanan ?>" class="btn-cancel">
                    <i class="fa-regular fa-arrow-left"></i> Batal
                </a>
            </div>
        </form>
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

<script>
    // ========== RATING STARS ==========
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('ratingInput');
    const ratingText = document.getElementById('ratingText');

    const ratingMessages = {
        0: '<i class="fa-regular fa-hand-point-up" style="color:#2563EB;"></i> Klik bintang untuk memberi rating',
        1: '⭐ Sangat Buruk - Tidak direkomendasikan',
        2: '⭐⭐ Buruk - Masih banyak kekurangan',
        3: '⭐⭐⭐ Cukup - Biasa saja',
        4: '⭐⭐⭐⭐ Baik - Memuaskan',
        5: '⭐⭐⭐⭐⭐ Sangat Baik - Luar biasa!'
    };

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            ratingInput.value = value;
            
            stars.forEach(s => {
                const val = parseInt(s.dataset.value);
                if (val <= value) {
                    s.className = 'fa-solid fa-star star active';
                } else {
                    s.className = 'fa-regular fa-star star';
                }
            });
            
            ratingText.innerHTML = ratingMessages[value] || ratingMessages[0];
        });

        star.addEventListener('mouseenter', function() {
            const value = parseInt(this.dataset.value);
            stars.forEach(s => {
                const val = parseInt(s.dataset.value);
                if (val <= value) {
                    s.className = 'fa-solid fa-star star';
                    s.style.color = '#f7b500';
                } else {
                    s.className = 'fa-regular fa-star star';
                    s.style.color = '#d1d5db';
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            const selected = parseInt(ratingInput.value);
            stars.forEach(s => {
                const val = parseInt(s.dataset.value);
                if (selected > 0 && val <= selected) {
                    s.className = 'fa-solid fa-star star active';
                    s.style.color = '#f7b500';
                } else {
                    s.className = 'fa-regular fa-star star';
                    s.style.color = '#d1d5db';
                }
            });
            if (selected > 0) {
                ratingText.innerHTML = ratingMessages[selected] || ratingMessages[0];
            } else {
                ratingText.innerHTML = ratingMessages[0];
            }
        });
    });
</script>

</body>
</html>