<?php
session_start();
$isLoggedIn = isset($_SESSION['id_pelanggan']) || isset($_SESSION['user']);
$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? '';

include 'connection.php';

$query = "SELECT m.*, 
          COALESCE(ROUND(AVG(r.rating), 1), 0) as rata_rating, 
          COUNT(r.id_review) as jumlah_ulasan
          FROM mobil m
          LEFT JOIN review r ON m.id_mobil = r.id_mobil
          GROUP BY m.id_mobil
          ORDER BY m.id_mobil";
$result = mysqli_query($conn, $query);
$mobil_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    $nama_file = strtolower(str_replace(' ', '', $row['tipe'])) . '.jpg';
    
    $mobil_list[] = [
        'id' => $row['id_mobil'],
        'merek' => $row['merek'],
        'tipe' => $row['tipe'],
        'harga' => $row['harga_per_hari'],
        'status' => $row['status'],
        'kapasitas_kursi' => $row['kapasitas_kursi'],
        'foto' => 'upload_foto/' . $nama_file,
        'rating' => $row['rata_rating'],
        'ulasan' => $row['jumlah_ulasan']
    ];
}

function tampilkanBintang($rating) {
    if ($rating == 0) {
        return '<i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i><i class="fa-regular fa-star"></i>';
    }
    $bintang_penuh = floor($rating);
    $bintang_setengah = ($rating - $bintang_penuh) >= 0.5 ? 1 : 0;
    $bintang_kosong = 5 - $bintang_penuh - $bintang_setengah;
    
    $html = '';
    for ($i = 0; $i < $bintang_penuh; $i++) $html .= '<i class="fa-solid fa-star" style="color: #f7b500;"></i>';
    if ($bintang_setengah) $html .= '<i class="fa-solid fa-star-half-alt" style="color: #f7b500;"></i>';
    for ($i = 0; $i < $bintang_kosong; $i++) $html .= '<i class="fa-regular fa-star" style="color: #f7b500;"></i>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIREMO - Beranda</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#f4f4f4;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

.navbar{
    background:#fff;
    height:90px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 40px;
    border-bottom:1px solid #ddd;
    flex-wrap:wrap;
}

.nav-left{
    min-width:320px;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
}

.logo img{
    width:70px;
    height:60px;
    object-fit:contain;
}

.logo-text h2{
    font-size:28px;
    letter-spacing:12px;
    color:#1f2937;
    line-height:1;
}

.logo-text p{
    font-size:12px;
    color:#666;
    letter-spacing:2px;
    margin-top:6px;
}

.nav-menu{
    display:flex;
    align-items:center;
    justify-content:center;
    flex:1;
    gap:50px;
    list-style:none;
}

.nav-menu li{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:16px;
    color:#666;
    cursor:pointer;
    padding:30px 0;
}

.nav-menu li a{
    text-decoration:none;
    color:#666;
    display:flex;
    align-items:center;
    gap:8px;
}

.nav-menu li.active{
    color:#2563EB;
    border-bottom:3px solid #2563EB;
    font-weight:600;
}

.nav-menu li.active a{
    color:#2563EB;
}

.profile{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:180px;
    justify-content:flex-end;
}

.profile .avatar-icon{
    width:40px;
    height:40px;
    background:#e5e7eb;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#9ca3af;
    font-size:20px;
}

.profile span{
    font-size:15px;
    font-weight:500;
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

/* ========== HERO ========== */
.hero{
    text-align:center;
    padding:25px;
}

.hero h1{
    font-size:34px;
    color:#222;
    margin-bottom:8px;
}

.hero p{
    color:#777;
    font-size:14px;
}

/* ========== ALERT SUCCESS ========== */
.alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    padding: 15px 20px;
    border-radius: 10px;
    margin: 0 25px 20px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success i {
    font-size: 20px;
    color: #10b981;
}

.alert-success a {
    color: #065f46;
    font-weight: 600;
}

/* ========== SECTION ========== */
.section-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 25px;
    margin-bottom:20px;
}

.section-header h2{
    font-size:22px;
    color:#222;
}

.car-grid{
    padding:0 25px 30px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:25px;
}

.car-card{
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 3px 12px rgba(0,0,0,0.08);
    transition:.3s;
}

.car-card:hover{
    transform:translateY(-5px);
}

.car-image{
    position:relative;
}

.car-image img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.badge{
    position:absolute;
    top:12px;
    right:12px;
    color:white;
    font-size:11px;
    font-weight:600;
    padding:4px 10px;
    border-radius:20px;
}

.available{
    background:#22c55e;
}

.rented{
    background:#ef4444;
}

.car-info{
    padding:15px;
}

.car-title{
    display:flex;
    justify-content:space-between;
    margin-bottom:10px;
}

.car-title h3{
    font-size:18px;
}

.price{
    color:#2563EB;
    font-weight:700;
}

.rating {
    margin: 8px 0;
    font-size: 13px;
    color: #f7b500;
}

.rating i {
    margin-right: 2px;
}

.rating span {
    color: #666;
    margin-left: 5px;
}

.spec{
    color:#666;
    font-size:13px;
    margin-bottom:15px;
}

.btn{
    width:100%;
    border:none;
    background:#2563EB;
    color:white;
    padding:11px;
    border-radius:8px;
    cursor:pointer;
    font-weight:500;
    transition:0.2s;
}

.btn:hover{
    background:#1e4fc9;
}

/* ========== FOOTER / ALAMAT ========== */
.footer {
    background: #1f2937;
    color: #fff;
    padding: 40px 25px 30px;
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
    font-size: 18px;
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
    margin-bottom: 8px;
}

.footer-col .alamat {
    font-style: normal;
    line-height: 1.8;
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
    font-size: 20px;
    transition: 0.2s;
}

.footer-col .social a:hover {
    color: #2563EB;
}

.footer-bottom {
    max-width: 1200px;
    margin: 0 auto;
    border-top: 1px solid #374151;
    padding-top: 20px;
    margin-top: 25px;
    text-align: center;
    font-size: 13px;
    color: #9ca3af;
}

@media(max-width:768px){
    .navbar{
        flex-direction:column;
        height:auto;
        padding:15px;
        gap:15px;
    }
    .nav-menu{
        flex-wrap:wrap;
        justify-content:center;
        gap:20px;
    }
    .nav-menu li{
        padding:10px 0;
    }
    .hero h1{
        font-size:24px;
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
        <li class="active"><a href="dashboard.php"><i class="fa-solid fa-house"></i> Beranda</a></li>
        <li><a href="dashboard.php#mobil"><i class="fa-solid fa-car"></i> Mobil</a></li>
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

<!-- ========== HERO ========== -->
<section class="hero">
    <h1>Temukan Mobil Sewa Terbaik Untuk Perjalanan Anda</h1>
    <p>Pilih mobil sesuai kebutuhan dan nikmati perjalanan nyaman bersama SIREMO</p>
</section>

<!-- ========== ALERT SUCCESS BOOKING ========== -->
<?php if (isset($_GET['booking_success']) && $_GET['booking_success'] == 1): ?>
    <div class="alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong>Pemesanan Berhasil!</strong> Silahkan upload bukti pembayaran untuk verifikasi. Cek status pesanan Anda di 
            <a href="riwayat.php">Riwayat Pemesanan</a>.
        </div>
    </div>
<?php endif; ?>

<!-- ========== DAFTAR MOBIL ========== -->
<div class="section-header" id="mobil">
    <h2>Daftar Mobil</h2>
</div>

<div class="car-grid">
    <?php foreach ($mobil_list as $mobil): ?>
    <div class="car-card">
        <div class="car-image">
            <img src="<?= htmlspecialchars($mobil['foto']) ?>" 
                 alt="<?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['tipe']) ?>"
                 onerror="this.src='https://via.placeholder.com/300x220?text=Mobil'">
            <span class="badge <?= $mobil['status'] == 'tersedia' ? 'available' : 'rented' ?>">
                <?= $mobil['status'] == 'tersedia' ? 'Tersedia' : 'Disewa' ?>
            </span>
        </div>
        <div class="car-info">
            <div class="car-title">
                <h3><?= htmlspecialchars($mobil['merek'] . ' ' . $mobil['tipe']) ?></h3>
                <div class="price">Rp<?= number_format($mobil['harga'], 0, ',', '.') ?>/hari</div>
            </div>
            <div class="rating">
                <?= tampilkanBintang($mobil['rating']) ?>
                <span>(<?= $mobil['ulasan'] ?> ulasan)</span>
            </div>
            <div class="spec"><i class="fa-solid fa-user-group"></i> <?= $mobil['kapasitas_kursi'] ?> Kursi</div>
            <?php if ($isLoggedIn): ?>
                <button class="btn" onclick="location.href='mobil.php?id=<?= $mobil['id'] ?>'">Detail & Sewa</button>
            <?php else: ?>
                <button class="btn" onclick="location.href='login.html'">Detail & Sewa</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ========== FOOTER / ALAMAT ========== -->
<footer class="footer">
    <div class="footer-container">
        <!-- Kolom 1: Info Perusahaan -->
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

        <!-- Kolom 2: Alamat -->
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

        <!-- Kolom 3: Navigasi Cepat -->
        <div class="footer-col">
            <h4><i class="fa-solid fa-link"></i> Tautan Cepat</h4>
            <p><a href="dashboard.php" style="color:#cbd5e1; text-decoration:none;">Beranda</a></p>
            <p><a href="dashboard.php#mobil" style="color:#cbd5e1; text-decoration:none;">Daftar Mobil</a></p>
            <p><a href="riwayat.php" style="color:#cbd5e1; text-decoration:none;">Riwayat Pemesanan</a></p>
            <p><a href="bantuan.php" style="color:#cbd5e1; text-decoration:none;">Bantuan</a></p>
            <p><a href="#" style="color:#cbd5e1; text-decoration:none;">Syarat & Ketentuan</a></p>
            <p><a href="#" style="color:#cbd5e1; text-decoration:none;">Kebijakan Privasi</a></p>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> SIREMO Rent A Car. All Rights Reserved.
    </div>
</footer>

</body>
</html>