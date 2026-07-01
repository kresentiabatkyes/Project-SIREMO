<?php
// proses_booking.php - Halaman upload bukti pembayaran
session_start();

// ========== CEK LOGIN ==========
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = "proses_booking.php";
    header("Location: login.html");
    exit();
}

// Ambil id_pelanggan dari session
$id_pelanggan = $_SESSION['id_pelanggan'] ?? 0;
$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? 'Pelanggan';
$isLoggedIn = true;

include 'connection.php';

// ========== AMBIL DATA DARI POST ==========
$id_mobil = isset($_POST['id_mobil']) ? intval($_POST['id_mobil']) : 0;
$tanggal_sewa = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : '';
$tanggal_kembali = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : '';
$opsi_sopir = isset($_POST['opsi_sopir']) ? intval($_POST['opsi_sopir']) : 0;
$catatan = isset($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : '';
$harga_per_hari = isset($_POST['harga_per_hari']) ? intval($_POST['harga_per_hari']) : 0;
$nama_mobil = isset($_POST['nama_mobil']) ? $_POST['nama_mobil'] : '';

// Jika tanggal kosong, redirect kembali ke mobil.php
if (empty($tanggal_sewa) || empty($tanggal_kembali)) {
    $_SESSION['booking_error'] = "Silahkan pilih tanggal mulai dan tanggal kembali!";
    header("Location: mobil.php?id=" . $id_mobil);
    exit();
}

// Ambil data mobil dari database
$query = "SELECT * FROM mobil WHERE id_mobil = $id_mobil";
$result = mysqli_query($conn, $query);
$car = mysqli_fetch_assoc($result);

// Jika mobil tidak ditemukan, redirect ke dashboard
if (!$car) {
    header("Location: dashboard.php");
    exit();
}

// ========== HITUNG DURASI DAN TOTAL ==========
$start = new DateTime($tanggal_sewa);
$end = new DateTime($tanggal_kembali);
$diff = $start->diff($end);
$days = $diff->days > 0 ? $diff->days : 1;

$biaya_sopir_per_hari = 150000;
$driverExtra = ($opsi_sopir == 1) ? $biaya_sopir_per_hari * $days : 0;
$total = ($harga_per_hari * $days) + $driverExtra;
$driver_text = ($opsi_sopir == 1) ? 'Dengan Sopir' : 'Lepas Kunci';

// ========== CEK STRUKTUR TABEL ==========
$cek_kolom = "SHOW COLUMNS FROM pemesanan";
$result_kolom = mysqli_query($conn, $cek_kolom);
$kolom_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom)) {
    $kolom_list[] = $row_kolom['Field'];
}

// Tentukan nama kolom berdasarkan yang ada di database
$kolom_tanggal_mulai = in_array('tanggal_mulai', $kolom_list) ? 'tanggal_mulai' : (in_array('tanggal_sewa', $kolom_list) ? 'tanggal_sewa' : 'tanggal_mulai');
$kolom_tanggal_selesai = in_array('tanggal_selesai', $kolom_list) ? 'tanggal_selesai' : (in_array('tanggal_kembali', $kolom_list) ? 'tanggal_kembali' : 'tanggal_selesai');
$kolom_status = in_array('status_pemesanan', $kolom_list) ? 'status_pemesanan' : (in_array('status', $kolom_list) ? 'status' : 'status_pemesanan');

// ========== SIMPAN KE DATABASE ==========
// Cek apakah ada sopir yang dipilih
$id_sopir = null;
if ($opsi_sopir == 1) {
    // Cari sopir yang tersedia
    $query_sopir = "SELECT id_sopir FROM sopir WHERE status = 'tersedia' LIMIT 1";
    $result_sopir = mysqli_query($conn, $query_sopir);
    if ($row_sopir = mysqli_fetch_assoc($result_sopir)) {
        $id_sopir = $row_sopir['id_sopir'];
    }
}

// Insert ke tabel pemesanan
$status = 'Menunggu';
$query_insert = "INSERT INTO pemesanan (
    id_pelanggan, 
    id_mobil, 
    id_sopir, 
    $kolom_tanggal_mulai, 
    $kolom_tanggal_selesai, 
    total_harga, 
    $kolom_status
";

// Tambahkan catatan jika kolomnya ada
if (in_array('catatan', $kolom_list)) {
    $query_insert .= ", catatan";
}

// Tambahkan tanggal_pemesanan jika kolomnya ada
if (in_array('tanggal_pemesanan', $kolom_list)) {
    $query_insert .= ", tanggal_pemesanan";
}

$query_insert .= ") VALUES (
    $id_pelanggan, 
    $id_mobil, 
    " . ($id_sopir ? $id_sopir : "NULL") . ", 
    '$tanggal_sewa', 
    '$tanggal_kembali', 
    $total, 
    '$status'
";

if (in_array('catatan', $kolom_list)) {
    $query_insert .= ", '$catatan'";
}

if (in_array('tanggal_pemesanan', $kolom_list)) {
    $query_insert .= ", NOW()";
}

$query_insert .= ")";

// Simpan ID pemesanan untuk digunakan di halaman
$id_pemesanan = 0;
if (mysqli_query($conn, $query_insert)) {
    $id_pemesanan = mysqli_insert_id($conn);
    
    // Simpan ke session untuk notifikasi
    $_SESSION['booking_success'] = [
        'id_pemesanan' => $id_pemesanan,
        'id_mobil' => $id_mobil,
        'nama_mobil' => $nama_mobil,
        'tanggal_mulai' => $tanggal_sewa,
        'tanggal_selesai' => $tanggal_kembali,
        'total' => $total,
        'status' => $status
    ];
} else {
    // Jika gagal, tampilkan error
    $error = mysqli_error($conn);
    echo "Error: " . $error;
    echo "<br><br>Query: " . $query_insert;
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIREMO · Upload Bukti Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body {
            background: #f4f4f4;
            min-height: 100vh;
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

        /* ========== CARD UTAMA ========== */
        .main-container {
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .card {
            max-width: 860px;
            width: 100%;
            background: white;
            border-radius: 32px;
            padding: 2rem 2rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0,20,40,0.08);
        }
        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.8rem;
            border-bottom: 1px solid #eef2f6;
            padding-bottom: 1.2rem;
            flex-wrap: wrap;
        }
        .back-link {
            margin-left: auto;
            color: #1e4a7a;
            font-weight: 500;
            font-size: 0.95rem;
            background: #f0f5fe;
            padding: 0.4rem 1.2rem;
            border-radius: 40px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .back-link:hover { background: #dce8fa; }
        .page-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #0b1a33;
            margin-bottom: 0.4rem;
        }
        .sub-title {
            color: #3c4f6e;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }
        
        /* ========== DUA KOLOM ========== */
        .two-col {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }
        .left-col {
            flex: 1.3;
            min-width: 280px;
        }
        .right-col {
            flex: 1;
            min-width: 240px;
            background: #f9fbfd;
            border-radius: 24px;
            padding: 1.5rem 1.2rem;
            align-self: flex-start;
            border: 1px solid #e9eef3;
        }
        
        /* ========== PAYMENT BOX ========== */
        .payment-box {
            background: #f9fbfd;
            border-radius: 20px;
            padding: 1.5rem 1.2rem;
            border: 1px solid #e9eef3;
            margin-bottom: 1.8rem;
        }
        .payment-box h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #0b1a33;
            margin-bottom: 0.75rem;
        }
        .bank-detail {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem 1.2rem;
            border-radius: 16px;
            border: 1px solid #e0e8f0;
            margin-top: 0.3rem;
        }
        .logo-bca {
            width: 52px;
            height: 52px;
            background: #1a2b4a;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .logo-bca img {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: contain;
        }
        .bank-info { display: flex; flex-direction: column; }
        .bank-name { font-weight: 600; color: #0b1a33; }
        .bank-account { font-weight: 500; color: #1f3a5e; letter-spacing: 0.5px; }
        .bank-owner { font-size: 0.85rem; color: #3f5a7a; }
        .checkbox-note {
            margin-top: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
            color: #1f3a5e;
        }
        .checkbox-note input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a4d7a;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        /* ========== UPLOAD AREA ========== */
        .upload-area {
            border: 2px dashed #cbd8e8;
            background: #fafcff;
            border-radius: 20px;
            padding: 2rem 1rem;
            text-align: center;
            transition: 0.2s;
            margin-top: 0.5rem;
            cursor: pointer;
        }
        .upload-area i {
            font-size: 2.2rem;
            color: #2e5a8a;
            background: #eaf1fb;
            padding: 0.6rem;
            border-radius: 60px;
            margin-bottom: 0.5rem;
        }
        .upload-area p { font-weight: 500; color: #0b1a33; }
        .upload-area span { color: #4a6a8f; font-size: 0.85rem; }
        .upload-area small { display: block; margin-top: 6px; color: #6f7f97; }
        .file-input-hidden { display: none; }
        
        /* ========== RINGKASAN ========== */
        .right-col h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #0b1a33;
            margin-bottom: 1.2rem;
            border-bottom: 1px solid #e0e8f0;
            padding-bottom: 0.6rem;
        }
        .summary-car {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .summary-car-img {
            width: 75px;
            height: 60px;
            border-radius: 14px;
            overflow: hidden;
            flex-shrink: 0;
            background: #d4e0ed;
        }
        .summary-car-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .summary-car-detail h5 {
            font-weight: 600;
            color: #0b1a33;
            font-size: 0.95rem;
        }
        .summary-car-detail .specs {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 8px;
            font-size: 0.7rem;
            color: #2f4b6e;
            margin-top: 4px;
        }
        .summary-car-detail .specs span {
            background: #eef4fa;
            padding: 0.1rem 0.6rem;
            border-radius: 40px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 0.3rem 0;
            border-bottom: 1px solid #e9eef3;
        }
        .summary-row:last-child { border-bottom: none; }
        .total-price {
            font-weight: 700;
            color: #0b1a33;
            font-size: 1.2rem;
            margin-top: 0.8rem;
            border-top: 2px solid #dce4ef;
            padding-top: 0.8rem;
            display: flex;
            justify-content: space-between;
        }
        .total-price span:last-child { color: #0d2b4a; }
        .important-note {
            margin-top: 1.2rem;
            background: #f0f6ff;
            padding: 0.7rem 1rem;
            border-radius: 14px;
            font-size: 0.8rem;
            color: #1f3f62;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            border-left: 3px solid #1a4d7a;
        }
        .important-note i { color: #1a4d7a; margin-top: 1px; }
        .btn-submit {
            background: #0b1a33;
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            margin-top: 1.5rem;
            cursor: pointer;
            transition: 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-submit:hover { background: #1f3f62; }

        /* ========== NOTE CATATAN ========== */
        .note-catatan {
            background: #f8fafc;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            font-size: 0.8rem;
            color: #4a6a8f;
            margin-top: 0.5rem;
            border: 1px solid #e9eef3;
        }
        .note-catatan strong { color: #0b1a33; }
        
        @media (max-width: 680px) {
            .card { padding: 1.5rem; }
            .two-col { flex-direction: column; }
            .right-col { width: 100%; }
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
        <li><a href="riwayat.php"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat</a></li>
        <li><a href="bantuan.php"><i class="fa-regular fa-circle-question"></i> Bantuan</a></li>
    </ul>

    <div class="profile">
        <div class="avatar-icon"><i class="fa-regular fa-user"></i></div>
        <span><?= htmlspecialchars($nama_user) ?></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-container">
    <div class="card">
        <!-- Header -->
        <div class="header-title">
            <div style="display:flex;align-items:center;gap:6px;">
                <h2 style="font-size:1.8rem;font-weight:700;color:#0b1a33;">SIREMO</h2>
                <span style="font-size:0.85rem;font-weight:500;color:#3b5e8b;background:#e9eff6;padding:0.15rem 0.9rem;border-radius:40px;">RENT A CAR</span>
            </div>
            <a href="mobil.php?id=<?= $id_mobil ?>" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Detail Pesanan</a>
        </div>

        <h2 class="page-title"><i class="fas fa-upload" style="margin-right: 10px; color: #1a4d7a;"></i>Upload Bukti Pembayaran</h2>
        <p class="sub-title">Silahkan lakukan pembayaran sesuai instruksi di bawah dan upload bukti transfer untuk memverifikasi pemesanan Anda.</p>

        <div class="two-col">
            <!-- KOLOM KIRI -->
            <div class="left-col">
                <!-- 1. Lakukan Pembayaran -->
                <div class="payment-box">
                    <h3><i class="fas fa-university" style="margin-right: 8px; color: #1a4d7a;"></i>1. Lakukan Pembayaran</h3>
                    <p style="font-size: 0.9rem; color: #1f3a5e; margin-bottom: 0.6rem;">Transfer sesuai nominal ke rekening berikut:</p>
                    <div class="bank-detail">
                        <div class="logo-bca">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%231a2b4a'/%3E%3Ctext x='14' y='58' font-family='Arial, sans-serif' font-weight='bold' font-size='28' fill='%23ffffff'%3EBCA%3C/text%3E%3C/svg%3E" alt="BCA logo">
                        </div>
                        <div class="bank-info">
                            <span class="bank-name">Bank BCA</span>
                            <span class="bank-account">123-245-7890</span>
                            <span class="bank-owner">a.n. SIREMO</span>
                        </div>
                    </div>
                    <div class="checkbox-note">
                        <input type="checkbox" id="confirmTransfer">
                        <label for="confirmTransfer">Pastikan nominal transfer sesuai dengan total yang harus dibayarkan.</label>
                    </div>
                </div>

                <!-- 2. Upload Bukti Transfer -->
                <div style="margin-top: 0.8rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #0b1a33; margin-bottom: 0.2rem;"><i class="fas fa-cloud-upload-alt" style="margin-right: 8px; color: #1a4d7a;"></i>2. Upload Bukti Transfer</h3>
                    <p style="font-size: 0.9rem; color: #3f5a7a; margin-bottom: 0.8rem;">Upload bukti transfer yang sudah Anda lakukan</p>

                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-file-image"></i>
                        <p>Klik atau seret file ke sini</p>
                        <span>(jpg, png, pdf, maks 2MB)</span>
                        <small><i class="fas fa-info-circle"></i> Max 2MB</small>
                        <input type="file" id="fileInput" class="file-input-hidden" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div id="filePreview" style="font-size: 0.85rem; color: #1f3a5e; margin-top: 4px; display: none;">
                        <i class="fas fa-check-circle" style="color: #1f8b4c;"></i> <span id="fileName">file.jpg</span>
                    </div>
                </div>

                <button class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Kirim Bukti Pembayaran</button>
                <p style="font-size: 0.7rem; color: #62748c; margin-top: 0.5rem; text-align: center;">Setelah mengirim, tim kami verifikasi dalam 1x24 jam</p>
            </div>

            <!-- KOLOM KANAN: RINGKASAN PESANAN -->
            <div class="right-col">
                <h4><i class="fas fa-receipt" style="margin-right: 6px;"></i>Ringkasan Pesanan</h4>
                <div id="dynamicSummary">
                    <div class="summary-car">
                        <div class="summary-car-img">
                            <img src="upload_foto/<?= strtolower(str_replace(' ', '', $car['tipe'])) . '.jpg' ?>" 
                                 alt="<?= htmlspecialchars($car['tipe']) ?>"
                                 onerror="this.src='https://via.placeholder.com/75x60?text=Mobil'">
                        </div>
                        <div class="summary-car-detail">
                            <h5><?= htmlspecialchars($car['merek'] . ' ' . $car['tipe']) ?></h5>
                            <div class="specs">
                                <span>MVP</span>
                                <span><?= $car['kapasitas_kursi'] ?> Kursi</span>
                                <span>AC</span>
                                <span>Bluetooth</span>
                                <span>USB</span>
                            </div>
                        </div>
                    </div>

                    <div class="summary-row"><span>Tanggal Mulai</span><span><?= date('d M Y', strtotime($tanggal_sewa)) ?></span></div>
                    <div class="summary-row"><span>Tanggal Selesai</span><span><?= date('d M Y', strtotime($tanggal_kembali)) ?></span></div>
                    <div class="summary-row"><span>Durasi</span><span><?= $days ?> Hari</span></div>
                    <div class="summary-row"><span>Opsi Sopir</span><span><?= $driver_text ?></span></div>
                    
                    <?php if ($driverExtra > 0): ?>
                        <div class="summary-row" style="font-size:0.8rem;color:#4a6a8f;">
                            <span>Biaya Sopir</span>
                            <span>+Rp<?= number_format($driverExtra, 0, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($catatan)): ?>
                        <div class="note-catatan">
                            <strong>Catatan:</strong> <?= htmlspecialchars($catatan) ?>
                        </div>
                    <?php endif; ?>

                    <div class="total-price">
                        <span>Total yang Harus Dibayarkan</span>
                        <span>Rp<?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                </div>

                <div class="important-note">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><strong>Penting</strong><br>Setelah mengirim bukti transfer, tim kami akan memverifikasi pembayaran Anda dalam waktu maksimal 1x24 jam.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const fileNameSpan = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');

        uploadArea.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.click();
        });

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#1a4d7a';
            uploadArea.style.background = '#eaf1fb';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#cbd8e8';
            uploadArea.style.background = '#fafcff';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#cbd8e8';
            uploadArea.style.background = '#fafcff';
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                handleFile(file);
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            }
        });

        fileInput.addEventListener('change', function(e) {
            if (this.files.length) {
                handleFile(this.files[0]);
            }
        });

        function handleFile(file) {
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            const maxSize = 2 * 1024 * 1024;
            if (!validTypes.includes(file.type)) {
                alert('Format file harus jpg, png, atau pdf.');
                resetFileInput();
                return;
            }
            if (file.size > maxSize) {
                alert('Ukuran file maksimal 2MB.');
                resetFileInput();
                return;
            }
            fileNameSpan.textContent = file.name;
            filePreview.style.display = 'block';
            uploadArea.style.borderColor = '#1f8b4c';
            uploadArea.style.background = '#f0faf5';
        }

        function resetFileInput() {
            fileInput.value = '';
            filePreview.style.display = 'none';
            uploadArea.style.borderColor = '#cbd8e8';
            uploadArea.style.background = '#fafcff';
        }

        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const checkTransfer = document.getElementById('confirmTransfer');
            if (!checkTransfer.checked) {
                alert('Harap centang "Pastikan nominal transfer sesuai" sebelum mengirim.');
                return;
            }
            if (!fileInput.files.length) {
                alert('Silahkan upload bukti transfer terlebih dahulu.');
                return;
            }
            
            // Simulasi upload - redirect ke dashboard (beranda)
            alert('Bukti pembayaran berhasil dikirim. Tim kami akan memverifikasi dalam 1x24 jam.');
            
            // Redirect ke dashboard (beranda)
            window.location.href = 'dashboard.php?booking_success=1';
        });
    })();
</script>

</body>
</html>