<?php
session_start();

// Ambil id mobil dari URL
$id_mobil = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Jika belum login, simpan URL tujuan ke session, lalu redirect ke login
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "mobil.php?id=" . $id_mobil;
    header("Location: login.html");
    exit();
}

// Ambil data mobil dari database
include 'connection.php';
$query = "SELECT * FROM mobil WHERE id_mobil = $id_mobil";
$result = mysqli_query($conn, $query);
$car = mysqli_fetch_assoc($result);

// Jika mobil tidak ditemukan, redirect ke dashboard
if (!$car) {
    header("Location: dashboard.php");
    exit();
}

$isLoggedIn = isset($_SESSION['id_pelanggan']) || isset($_SESSION['user']);
$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? 'Pelanggan';

// Tentukan status badge
$is_available = ($car['status'] == 'tersedia');
$status_badge = $is_available ? 
    '<span class="available">Tersedia</span>' : 
    '<span class="rented">Disewa</span>';

// ========== AMBIL DATA REVIEW DARI DATABASE ==========
$query_review = "SELECT r.*, p.nama_lengkap 
                 FROM review r 
                 JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan 
                 WHERE r.id_mobil = $id_mobil 
                 ORDER BY r.tanggal_review DESC";
$result_review = mysqli_query($conn, $query_review);

$ulasan_list = [];
$total_rating = 0;
$jumlah_ulasan = 0;

while ($row = mysqli_fetch_assoc($result_review)) {
    $ulasan_list[] = [
        'nama' => $row['nama_lengkap'],
        'rating' => $row['rating'],
        'komentar' => !empty($row['komentar']) ? $row['komentar'] : 'Tidak ada komentar',
        'tanggal' => date('d M Y', strtotime($row['tanggal_review']))
    ];
    $total_rating += $row['rating'];
    $jumlah_ulasan++;
}

// Hitung rata-rata rating
$rata_rata_rating = ($jumlah_ulasan > 0) ? round($total_rating / $jumlah_ulasan, 1) : 0;

// Fungsi untuk menampilkan bintang
function tampilkanBintangDetail($rating) {
    if ($rating == 0) {
        return '<i class="fa-regular fa-star"></i>
                <i class="fa-regular fa-star"></i>
                <i class="fa-regular fa-star"></i>
                <i class="fa-regular fa-star"></i>
                <i class="fa-regular fa-star"></i>';
    }
    $bintang_penuh = floor($rating);
    $bintang_setengah = ($rating - $bintang_penuh) >= 0.5 ? 1 : 0;
    $bintang_kosong = 5 - $bintang_penuh - $bintang_setengah;
    
    $html = '';
    for ($i = 0; $i < $bintang_penuh; $i++) {
        $html .= '<i class="fa-solid fa-star" style="color: #f7b500;"></i>';
    }
    if ($bintang_setengah) {
        $html .= '<i class="fa-solid fa-star-half-alt" style="color: #f7b500;"></i>';
    }
    for ($i = 0; $i < $bintang_kosong; $i++) {
        $html .= '<i class="fa-regular fa-star" style="color: #f7b500;"></i>';
    }
    return $html;
}

// Fungsi untuk mendapatkan nama file foto
function getFotoMobil($tipe) {
    $foto = strtolower(str_replace(' ', '', $tipe)) . '.jpg';
    // Cek apakah file ada
    if (file_exists('upload_foto/' . $foto)) {
        return $foto;
    }
    // Fallback ke placeholder
    return 'placeholder.jpg';
}

// Ambil spesifikasi dari database atau gunakan default
$spesifikasi = [
    'MVP',
    $car['kapasitas_kursi'] . ' Kursi',
    'AC',
    'Bluetooth',
    'USB'
];
$spesifikasi_json = json_encode($spesifikasi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Mobil - SIREMO</title>

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
}

/* ========== NAVBAR SAMA PERSIS KAYAK DASHBOARD ========== */
.navbar {
    background: #fff;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 40px;
    border-bottom: 1px solid #ddd;
}

.nav-left {
    min-width: 320px;
}

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

.btn-login {
    padding: 8px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    background: #2563EB;
    color: white;
    border: none;
    cursor: pointer;
}

.btn-login:hover {
    background: #1e4fc9;
}

/* ========== CONTAINER ========== */
.container {
    width: 95%;
    margin: 25px auto;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

/* ========== LEFT ========== */
.left {
    flex: 1.3;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.breadcrumb {
    font-size: 13px;
    color: #888;
    margin-bottom: 15px;
}

.breadcrumb a {
    text-decoration: none;
    color: #2563EB;
}

.car-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.car-title h2 {
    font-size: 32px;
    font-weight: 600;
}

.available {
    background: #22c55e;
    color: white;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
}

.rented {
    background: #ef4444;
    color: white;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
}

.rating {
    margin-bottom: 15px;
}

.car-image {
    margin: 15px 0;
}

.car-image img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    border-radius: 12px;
}

.price {
    margin-top: 15px;
    font-size: 30px;
    font-weight: 700;
    color: #2563EB;
}

.price span {
    font-size: 15px;
    color: #555;
    font-weight: 400;
}

.specs {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.spec {
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    font-size: 13px;
}

.spec i {
    font-size: 20px;
    color: #2563EB;
    margin-bottom: 5px;
    display: block;
}

.desc {
    margin-top: 25px;
}

.desc h4 {
    margin-bottom: 10px;
}

.desc p {
    font-size: 14px;
    color: #666;
    line-height: 1.7;
}

/* ========== RIGHT ========== */
.right {
    flex: 1;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.right h3 {
    font-size: 24px;
    margin-bottom: 5px;
}

.right p {
    font-size: 13px;
    color: #777;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    outline: none;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: #2563EB;
}

.form-group textarea {
    height: 100px;
    resize: none;
}

.row {
    display: flex;
    gap: 15px;
}

.row .form-group {
    flex: 1;
}

.option {
    display: flex;
    gap: 25px;
    margin: 10px 0 20px 0;
}

.option label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    cursor: pointer;
}

.summary {
    background: #f7f7f7;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.total {
    color: #2563EB;
    font-weight: 700;
    font-size: 18px;
}

.btn {
    width: 100%;
    background: #2563EB;
    border: none;
    padding: 14px;
    border-radius: 8px;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
}

.btn:hover {
    background: #1e4fc9;
}

.btn-disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

/* ========== ULASAN ========== */
.review-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-top: 20px;
    width: 100%;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.review-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.review-rating {
    font-size: 24px;
    font-weight: 700;
    color: #2563EB;
}

.review-stars {
    font-size: 18px;
    color: #f7b500;
}

.review-count {
    color: #666;
    font-size: 14px;
}

.review-card {
    border-bottom: 1px solid #eee;
    padding: 20px 0;
}

.review-card:last-child {
    border-bottom: none;
}

.reviewer-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.review-date {
    font-size: 12px;
    color: #888;
    margin-left: 10px;
    font-weight: normal;
}

.review-comment {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
}

@media (max-width: 900px) {
    .container {
        flex-direction: column;
    }
    .navbar {
        flex-direction: column;
        height: auto;
        padding: 15px;
        gap: 15px;
    }
    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
    .nav-menu li {
        padding: 10px 0;
    }
    .specs {
        grid-template-columns: repeat(2, 1fr);
    }
    .row {
        flex-direction: column;
        gap: 0;
    }
}

@media (max-width: 480px) {
    .car-title h2 {
        font-size: 24px;
    }
    .price {
        font-size: 24px;
    }
}
</style>
</head>
<body>

<!-- ========== NAVBAR SAMA PERSIS KAYAK DASHBOARD ========== -->
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
        <li class="active"><a href="#"><i class="fa-solid fa-car"></i> Mobil</a></li>
        <li><a href="riwayat.php"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat</a></li>
        <li><a href="bantuan.php"><i class="fa-regular fa-circle-question"></i> Bantuan</a></li>
    </ul>

    <div class="profile">
        <?php if ($isLoggedIn): ?>
            <div class="avatar-icon"><i class="fa-regular fa-user"></i></div>
            <span><?= htmlspecialchars($nama_user) ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        <?php else: ?>
            <div class="avatar-icon"><i class="fa-regular fa-user"></i></div>
            <a href="login.html" class="btn-login">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">

    <!-- KIRI: Detail Mobil -->
    <div class="left">
        <div class="breadcrumb">
            ← <a href="dashboard.php">Kembali</a> / Mobil / <?= htmlspecialchars($car['merek'] . ' ' . $car['tipe']) ?>
        </div>
        <div class="car-title">
            <h2><?= htmlspecialchars($car['merek'] . ' ' . $car['tipe']) ?></h2>
            <?= $status_badge ?>
        </div>
        <div class="rating">
            <?= tampilkanBintangDetail($rata_rata_rating) ?>
            <span style="margin-left: 5px; color:#666;"><?= $rata_rata_rating ?> (<?= $jumlah_ulasan ?> ulasan)</span>
        </div>
        <div class="car-image">
            <img src="upload_foto/<?= getFotoMobil($car['tipe']) ?>" 
                 alt="<?= htmlspecialchars($car['tipe']) ?>"
                 onerror="this.src='https://via.placeholder.com/800x320?text=Mobil'">
        </div>
        <div class="price">
            Rp<?= number_format($car['harga_per_hari'], 0, ',', '.') ?> <span>/hari</span>
        </div>
        <div class="specs">
            <div class="spec"><i class="fa-solid fa-car"></i><br>Matic</div>
            <div class="spec"><i class="fa-solid fa-user-group"></i><br><?= $car['kapasitas_kursi'] ?> Kursi</div>
            <div class="spec"><i class="fa-solid fa-snowflake"></i><br>AC</div>
            <div class="spec"><i class="fa-solid fa-bluetooth"></i><br>Bluetooth</div>
        </div>
        <div class="desc">
            <h4>Deskripsi</h4>
            <p><?= htmlspecialchars($car['merek'] . ' ' . $car['tipe']) ?> adalah pilihan tepat untuk perjalanan keluarga maupun bisnis. Nyaman, irit bahan bakar, dan memiliki kabin yang luas.</p>
        </div>
    </div>

    <!-- KANAN: Form Booking -->
    <div class="right">
        <h3>Form Booking</h3>
        <p><?= $is_available ? 'Lengkapi data pemesanan untuk melanjutkan.' : 'Maaf, mobil sedang disewa.' ?></p>

        <form action="proses_booking.php" method="POST">
            <!-- Hidden fields untuk data mobil -->
            <input type="hidden" name="id_mobil" value="<?= $car['id_mobil'] ?>">
            <input type="hidden" name="harga_per_hari" value="<?= $car['harga_per_hari'] ?>">
            <input type="hidden" name="nama_mobil" value="<?= htmlspecialchars($car['merek'] . ' ' . $car['tipe']) ?>">
            <input type="hidden" name="foto_mobil" value="<?= getFotoMobil($car['tipe']) ?>">
            <input type="hidden" name="spesifikasi" value='<?= $spesifikasi_json ?>'>

            <div class="row">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" <?= !$is_available ? 'disabled' : '' ?> required>
                </div>
                <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" <?= !$is_available ? 'disabled' : '' ?> required>
                </div>
            </div>

            <label style="font-size:13px;font-weight:600;">Opsi Sopir</label>
            <div class="option">
                <label><input type="radio" name="opsi_sopir" value="0" > Lepas Kunci</label>
                <label><input type="radio" name="opsi_sopir" value="1"> Dengan Sopir (+Rp150.000/hari)</label>
            </div>

            <div class="summary">
                <div class="summary-item">
                    <span>Harga Sewa</span>
                    <span id="harga_sewa">Rp<?= number_format($car['harga_per_hari'], 0, ',', '.') ?></span>
                </div>
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span id="subtotal">Rp0</span>
                </div>
                <hr>
                <div class="summary-item total">
                    <span>Total</span>
                    <span id="total">Rp0</span>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan (Opsional)</label>
                <textarea name="catatan" placeholder="Tambahkan catatan untuk pemesanan Anda (misal: kebutuhan khusus, lokasi antar jemput, dll)" <?= !$is_available ? 'disabled' : '' ?>></textarea>
            </div>

            <?php if ($is_available): ?>
                <button type="submit" class="btn">Lanjutkan Booking →</button>
            <?php else: ?>
                <button type="button" class="btn btn-disabled" disabled>Mobil Tidak Tersedia</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ULASAN -->
<div class="container" style="margin-top: 0;">
    <div class="review-section">
        <div class="review-header">
            <div class="review-rating"><?= $rata_rata_rating ?>/5</div>
            <div class="review-stars"><?= tampilkanBintangDetail($rata_rata_rating) ?></div>
            <div class="review-count">(<?= $jumlah_ulasan ?> ulasan)</div>
        </div>

        <?php if (count($ulasan_list) > 0): ?>
            <?php foreach ($ulasan_list as $ulasan): ?>
                <div class="review-card">
                    <div class="reviewer-name">
                        <?= htmlspecialchars($ulasan['nama']) ?>
                        <span class="review-date"><?= $ulasan['tanggal'] ?></span>
                    </div>
                    <div class="review-rating-stars">
                        <?= tampilkanBintangDetail($ulasan['rating']) ?>
                    </div>
                    <div class="review-comment">
                        <?= nl2br(htmlspecialchars($ulasan['komentar'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#888;">Belum ada ulasan.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    const hargaPerHari = <?= $car['harga_per_hari'] ?>;
    const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
    const tanggalSelesai = document.querySelector('input[name="tanggal_selesai"]');
    const opsiSopir = document.querySelectorAll('input[name="opsi_sopir"]');
    const subtotalSpan = document.getElementById('subtotal');
    const totalSpan = document.getElementById('total');

    function hitungTotal() {
        if (!tanggalMulai.value || !tanggalSelesai.value) {
            subtotalSpan.innerText = 'Rp0';
            totalSpan.innerText = 'Rp0';
            return;
        }
        
        const start = new Date(tanggalMulai.value);
        const end = new Date(tanggalSelesai.value);
        const hari = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (hari <= 0) {
            subtotalSpan.innerText = 'Rp0';
            totalSpan.innerText = 'Rp0';
            return;
        }
        
        let total = hargaPerHari * hari;
        
        let sopir = false;
        for (let radio of opsiSopir) {
            if (radio.checked && radio.value == '1') sopir = true;
        }
        
        if (sopir) total += 150000 * hari;
        
        subtotalSpan.innerText = 'Rp' + (hargaPerHari * hari).toLocaleString('id-ID');
        totalSpan.innerText = 'Rp' + total.toLocaleString('id-ID');
    }
    
    if (tanggalMulai && tanggalSelesai) {
        tanggalMulai.addEventListener('change', hitungTotal);
        tanggalSelesai.addEventListener('change', hitungTotal);
        opsiSopir.forEach(radio => radio.addEventListener('change', hitungTotal));
    }
</script>

</body>
</html>