<?php
session_start();

// ========== CEK LOGIN ADMIN ==========
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php");
    exit();
}

$nama_admin = $_SESSION['nama'] ?? 'Admin';
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

// ========== CEK STRUKTUR TABEL SOPIR ==========
$cek_kolom_sopir = "SHOW COLUMNS FROM sopir";
$result_kolom_sopir = mysqli_query($conn, $cek_kolom_sopir);
$kolom_sopir_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom_sopir)) {
    $kolom_sopir_list[] = $row_kolom['Field'];
}

$kolom_telepon = in_array('nomor_telepon', $kolom_sopir_list) ? 'nomor_telepon' : (in_array('no_telepon', $kolom_sopir_list) ? 'no_telepon' : 'nomor_telepon');
$kolom_biaya = in_array('biaya_per_hari', $kolom_sopir_list) ? 'biaya_per_hari' : 'biaya_per_hari';

// ========== PROSES TAMBAH SOPIR ==========
if (isset($_POST['tambah'])) {
    $nama_sopir = mysqli_real_escape_string($conn, $_POST['nama_sopir']);
    $nomor_telepon = mysqli_real_escape_string($conn, $_POST['nomor_telepon']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $biaya_per_hari = intval($_POST['biaya_per_hari']);
    
    $query = "INSERT INTO sopir (nama_sopir, $kolom_telepon, status, $kolom_biaya) 
              VALUES ('$nama_sopir', '$nomor_telepon', '$status', $biaya_per_hari)";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['admin_message'] = "Sopir berhasil ditambahkan!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menambahkan sopir: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_sopir.php");
    exit();
}

// ========== PROSES EDIT SOPIR ==========
if (isset($_POST['edit'])) {
    $id_sopir = intval($_POST['id_sopir']);
    $nama_sopir = mysqli_real_escape_string($conn, $_POST['nama_sopir']);
    $nomor_telepon = mysqli_real_escape_string($conn, $_POST['nomor_telepon']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $biaya_per_hari = intval($_POST['biaya_per_hari']);
    
    $query = "UPDATE sopir SET 
              nama_sopir = '$nama_sopir', 
              $kolom_telepon = '$nomor_telepon', 
              status = '$status', 
              $kolom_biaya = $biaya_per_hari 
              WHERE id_sopir = $id_sopir";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['admin_message'] = "Sopir berhasil diupdate!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal update sopir: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_sopir.php");
    exit();
}

// ========== PROSES HAPUS SOPIR ==========
if (isset($_GET['hapus'])) {
    $id_sopir = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM sopir WHERE id_sopir = $id_sopir";
    if (mysqli_query($conn, $query_delete)) {
        $_SESSION['admin_message'] = "Sopir berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal hapus sopir: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_sopir.php");
    exit();
}

// ========== PROSES ASSIGN SOPIR KE PEMESANAN ==========
if (isset($_POST['assign'])) {
    $id_pemesanan = intval($_POST['id_pemesanan']);
    $id_sopir = intval($_POST['id_sopir']);
    
    $query_update = "UPDATE pemesanan SET id_sopir = $id_sopir WHERE id_pemesanan = $id_pemesanan";
    if (mysqli_query($conn, $query_update)) {
        $query_sopir = "UPDATE sopir SET status = 'bertugas' WHERE id_sopir = $id_sopir";
        mysqli_query($conn, $query_sopir);
        
        $_SESSION['admin_message'] = "Sopir berhasil ditugaskan ke pemesanan!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menugaskan sopir: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_sopir.php");
    exit();
}

// ========== PROSES LEPAS SOPIR DARI TUGAS ==========
if (isset($_POST['lepas'])) {
    $id_pemesanan = intval($_POST['id_pemesanan']);
    $id_sopir = intval($_POST['id_sopir']);
    
    $query_update = "UPDATE pemesanan SET id_sopir = NULL WHERE id_pemesanan = $id_pemesanan";
    if (mysqli_query($conn, $query_update)) {
        $query_sopir = "UPDATE sopir SET status = 'tersedia' WHERE id_sopir = $id_sopir";
        mysqli_query($conn, $query_sopir);
        
        $_SESSION['admin_message'] = "Sopir berhasil dilepas dari tugas!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal melepas sopir: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_sopir.php");
    exit();
}

// ========== AMBIL DATA SOPIR ==========
$query_sopir = "SELECT * FROM sopir ORDER BY id_sopir DESC";
$result_sopir = mysqli_query($conn, $query_sopir);
if (!$result_sopir) {
    die("Error query sopir: " . mysqli_error($conn));
}

// ========== AMBIL DATA PEMESANAN YANG BELUM ADA SOPIR ==========
$query_pemesanan = "SELECT p.*, m.merek, m.tipe, pel.nama_lengkap as nama_pelanggan
                    FROM pemesanan p
                    JOIN mobil m ON p.id_mobil = m.id_mobil
                    JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
                    WHERE p.id_sopir IS NULL AND p.$kolom_status != 'Selesai' AND p.$kolom_status != 'Ditolak' AND p.$kolom_status != 'Batal'
                    ORDER BY p.id_pemesanan DESC";
$result_pemesanan = mysqli_query($conn, $query_pemesanan);
if (!$result_pemesanan) {
    die("Error query pemesanan: " . mysqli_error($conn));
}

// ========== AMBIL DATA PEMESANAN YANG SUDAH ADA SOPIR ==========
$query_tugas = "SELECT p.*, m.merek, m.tipe, pel.nama_lengkap as nama_pelanggan, s.nama_sopir, s.id_sopir as id_sopir_tugas
                FROM pemesanan p
                JOIN mobil m ON p.id_mobil = m.id_mobil
                JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
                LEFT JOIN sopir s ON p.id_sopir = s.id_sopir
                WHERE p.id_sopir IS NOT NULL AND p.$kolom_status != 'Selesai' AND p.$kolom_status != 'Ditolak' AND p.$kolom_status != 'Batal'
                ORDER BY p.id_pemesanan DESC";
$result_tugas = mysqli_query($conn, $query_tugas);
if (!$result_tugas) {
    die("Error query tugas: " . mysqli_error($conn));
}

$message = $_SESSION['admin_message'] ?? '';
$message_type = $_SESSION['admin_message_type'] ?? '';
unset($_SESSION['admin_message']);
unset($_SESSION['admin_message_type']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sopir - SIREMO Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f4f7fc; display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background: #0b1a33;
            color: #fff;
            min-height: 100vh;
            padding: 25px 20px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 25px;
        }
        .sidebar-brand img { width: 50px; height: 45px; object-fit: contain; }
        .sidebar-brand h2 { font-size: 20px; font-weight: 700; color: #fff; }
        .sidebar-brand h2 span { color: #38b6ff; }
        .sidebar-brand small { display: block; font-size: 9px; font-weight: 300; letter-spacing: 2px; color: #9ca3af; }

        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }
        .sidebar-menu li a i { width: 20px; font-size: 16px; }
        .sidebar-menu li a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .sidebar-menu li.active a { background: #2563EB; color: #fff; }
        .sidebar-menu .menu-label { font-size: 11px; text-transform: uppercase; color: #6b7280; padding: 15px 16px 8px; letter-spacing: 1px; }

        .sidebar-footer {
            position: absolute;
            bottom: 25px;
            left: 20px;
            right: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }
        .sidebar-footer a:hover { background: rgba(239, 68, 68, 0.15); }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 25px 35px;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .main-header h1 { font-size: 24px; font-weight: 600; color: #1f2937; }
        .main-header h1 span { font-size: 14px; font-weight: 400; color: #6b7280; }
        .main-header .admin-info { display: flex; align-items: center; gap: 15px; }
        .main-header .admin-info .avatar {
            width: 40px;
            height: 40px;
            background: #2563EB;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }
        .main-header .admin-info span { font-weight: 500; color: #1f2937; }

        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .btn-tambah {
            background: #2563EB;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }
        .btn-tambah:hover { background: #1e4fc9; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-content h2 { font-size: 20px; margin-bottom: 20px; color: #1f2937; }
        .modal-content .form-group { margin-bottom: 15px; }
        .modal-content .form-group label {
            display: block;
            font-weight: 500;
            font-size: 13px;
            color: #374151;
            margin-bottom: 5px;
        }
        .modal-content .form-group input,
        .modal-content .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        .modal-content .form-group input:focus,
        .modal-content .form-group select:focus { border-color: #2563EB; }
        .modal-content .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-content .btn-group button {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        .modal-content .btn-group .btn-simpan { background: #2563EB; color: white; }
        .modal-content .btn-group .btn-simpan:hover { background: #1e4fc9; }
        .modal-content .btn-group .btn-batal { background: #e5e7eb; color: #374151; }
        .modal-content .btn-group .btn-batal:hover { background: #d1d5db; }

        .table-wrapper {
            background: white;
            border-radius: 14px;
            padding: 20px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
            margin-bottom: 30px;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .table-header h3 { font-size: 18px; font-weight: 600; color: #1f2937; }
        .table-header h3 i { color: #2563EB; margin-right: 8px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }
        tbody tr:hover { background: #fafbfc; }
        tbody td {
            padding: 12px 14px;
            vertical-align: middle;
        }

        .badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .btn-aksi {
            padding: 4px 12px;
            border-radius: 4px;
            border: none;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: 0.2s;
        }
        .btn-edit { background: #dbeafe; color: #1e40af; }
        .btn-edit:hover { background: #bfdbfe; }
        .btn-hapus { background: #fee2e2; color: #991b1b; }
        .btn-hapus:hover { background: #fecaca; }
        .btn-assign { background: #d1fae5; color: #065f46; }
        .btn-assign:hover { background: #a7f3d0; }
        .btn-lepas { background: #fef3c7; color: #92400e; }
        .btn-lepas:hover { background: #fde68a; }

        .assign-form {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .assign-form select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 12px;
            background: white;
        }
        .assign-form button {
            padding: 4px 12px;
            border-radius: 4px;
            border: none;
            background: #2563EB;
            color: white;
            font-size: 11px;
            cursor: pointer;
        }
        .assign-form button:hover { background: #1e4fc9; }
        .assign-form .btn-lepas-form {
            background: #ef4444;
        }
        .assign-form .btn-lepas-form:hover { background: #dc2626; }

        .text-muted { color: #9ca3af; font-size: 12px; }
        .text-success { color: #22c55e; }

        @media (max-width: 992px) {
            .sidebar { width: 70px; padding: 15px 10px; }
            .sidebar-brand h2, .sidebar-brand small, .sidebar-menu li a span, .menu-label, .sidebar-footer a span { display: none; }
            .sidebar-brand img { width: 40px; height: 35px; }
            .sidebar-menu li a { justify-content: center; padding: 12px; }
            .sidebar-footer a { justify-content: center; }
            .main-content { margin-left: 70px; padding: 20px; }
        }
        @media (max-width: 600px) {
            .main-content { padding: 15px; }
            .table-wrapper { padding: 15px; }
            table { font-size: 11px; }
            .main-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .modal-content { margin: 15px; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="upload_foto/logo siremo.png" alt="SIREMO">
        <div>
            <h2>SIREMO <span>ADMIN</span></h2>
            <small>CAR RENTAL SYSTEM</small>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-label">Menu Utama</li>
        <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
        <li><a href="admin_mobil.php"><i class="fa-solid fa-car"></i> <span>Mobil</span></a></li>
        <li><a href="admin_pemesanan.php"><i class="fa-regular fa-receipt"></i> <span>Pemesanan</span></a></li>
        <li class="active"><a href="admin_sopir.php"><i class="fa-solid fa-user-tie"></i> <span>Sopir</span></a></li>
        <li><a href="admin_pelanggan.php"><i class="fa-regular fa-user"></i> <span>Pelanggan</span></a></li>
        <li><a href="admin_ulasan.php"><i class="fa-regular fa-star"></i> <span>Ulasan</span></a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout_admin.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main-content">

    <div class="main-header">
        <h1>Kelola Sopir <span>Data sopir & pemberian tugas</span></h1>
        <div class="admin-info">
            <span><?= htmlspecialchars($nama_admin) ?></span>
            <div class="avatar"><?= substr($nama_admin, 0, 1) ?></div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type == 'success' ? 'success' : 'error' ?>">
            <i class="fa-solid <?= $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- ========== TABEL PEMESANAN BELUM ADA SOPIR ========== -->
    <div class="table-wrapper">
        <div class="table-header">
            <h3><i class="fa-solid fa-user-plus"></i> Pemberian Tugas Sopir</h3>
            <span style="font-size:13px; color:#6b7280;">Pemesanan yang belum memiliki sopir</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Mobil</th>
                    <th>Tanggal Sewa</th>
                    <th>Tanggal Kembali</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_pemesanan) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_pemesanan)): 
                        $tgl_mulai = $row[$kolom_tanggal_mulai] ?? date('Y-m-d');
                        $tgl_selesai = $row[$kolom_tanggal_selesai] ?? date('Y-m-d');
                    ?>
                        <tr>
                            <td>#<?= $row['id_pemesanan'] ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($row['merek'] . ' ' . $row['tipe']) ?></td>
                            <td><?= date('d M Y', strtotime($tgl_mulai)) ?></td>
                            <td><?= date('d M Y', strtotime($tgl_selesai)) ?></td>
                            <td>
                                <form method="POST" class="assign-form" onsubmit="return confirm('Yakin menugaskan sopir ke pemesanan ini?')">
                                    <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                    <select name="id_sopir" required>
                                        <option value="">Pilih Sopir</option>
                                        <?php 
                                        $query_sopir_tersedia = "SELECT * FROM sopir WHERE status = 'tersedia'";
                                        $result_sopir_tersedia = mysqli_query($conn, $query_sopir_tersedia);
                                        while ($sopir = mysqli_fetch_assoc($result_sopir_tersedia)): 
                                        ?>
                                            <option value="<?= $sopir['id_sopir'] ?>">
                                                <?= htmlspecialchars($sopir['nama_sopir']) ?> 
                                                (Rp<?= number_format($sopir[$kolom_biaya], 0, ',', '.') ?>/hari)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" name="assign"><i class="fa-solid fa-check"></i> Tugaskan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">
                            <i class="fa-regular fa-check-circle" style="font-size:20px; display:block; margin-bottom:10px;"></i>
                            Semua pemesanan sudah memiliki sopir
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== TABEL PEMESANAN YANG SUDAH ADA SOPIR ========== -->
    <div class="table-wrapper">
        <div class="table-header">
            <h3><i class="fa-solid fa-user-check"></i> Sopir Bertugas</h3>
            <span style="font-size:13px; color:#6b7280;">Pemesanan yang sudah memiliki sopir</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Mobil</th>
                    <th>Sopir</th>
                    <th>Tanggal Sewa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_tugas) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_tugas)): 
                        $tgl_mulai = $row[$kolom_tanggal_mulai] ?? date('Y-m-d');
                    ?>
                        <tr>
                            <td>#<?= $row['id_pemesanan'] ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td><?= htmlspecialchars($row['merek'] . ' ' . $row['tipe']) ?></td>
                            <td>
                                <span class="text-success"><i class="fa-regular fa-circle-check"></i> <?= htmlspecialchars($row['nama_sopir']) ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($tgl_mulai)) ?></td>
                            <td>
                                <form method="POST" class="assign-form" onsubmit="return confirm('Yakin melepas sopir dari tugas ini?')">
                                    <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                    <input type="hidden" name="id_sopir" value="<?= $row['id_sopir_tugas'] ?>">
                                    <button type="submit" name="lepas" class="btn-lepas-form" style="background:#ef4444;color:white;padding:4px 12px;border:none;border-radius:4px;cursor:pointer;">
                                        <i class="fa-regular fa-user-slash"></i> Lepas
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">
                            <i class="fa-regular fa-user" style="font-size:20px; display:block; margin-bottom:10px;"></i>
                            Belum ada sopir yang bertugas
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ========== TABEL DATA SOPIR ========== -->
    <div class="table-wrapper">
        <div class="table-header">
            <h3><i class="fa-solid fa-users"></i> Daftar Sopir</h3>
            <button class="btn-tambah" onclick="openModal('tambah')">
                <i class="fa-solid fa-plus"></i> Tambah Sopir
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Sopir</th>
                    <th>Nomor Telepon</th>
                    <th>Status</th>
                    <th>Biaya / Hari</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_sopir) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_sopir)): ?>
                        <?php
                        $status = $row['status'];
                        $badge_class = '';
                        switch ($status) {
                            case 'tersedia': $badge_class = 'badge-success'; break;
                            case 'bertugas': $badge_class = 'badge-warning'; break;
                            case 'libur': $badge_class = 'badge-danger'; break;
                            default: $badge_class = 'badge-secondary';
                        }
                        ?>
                        <tr>
                            <td>#<?= $row['id_sopir'] ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_sopir']) ?></strong></td>
                            <td><?= htmlspecialchars($row[$kolom_telepon]) ?></td>
                            <td>
                                <span class="badge <?= $badge_class ?>">
                                    <?= $status == 'tersedia' ? 'Tersedia' : ($status == 'bertugas' ? 'Bertugas' : 'Libur') ?>
                                </span>
                            </td>
                            <td>Rp<?= number_format($row[$kolom_biaya], 0, ',', '.') ?></td>
                            <td>
                                <button class="btn-aksi btn-edit" onclick="openModal('edit', <?= $row['id_sopir'] ?>)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <a href="?hapus=<?= $row['id_sopir'] ?>" class="btn-aksi btn-hapus" onclick="return confirm('Yakin hapus sopir ini?')">
                                    <i class="fa-regular fa-trash-can"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#9ca3af;">
                            <i class="fa-regular fa-user-tie" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                            Belum ada data sopir
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- ========== MODAL ========== -->
<div class="modal" id="modalSopir">
    <div class="modal-content">
        <h2 id="modalTitle">Tambah Sopir</h2>
        <form method="POST">
            <input type="hidden" name="id_sopir" id="editId">
            
            <div class="form-group">
                <label>Nama Sopir</label>
                <input type="text" name="nama_sopir" id="nama_sopir" placeholder="Contoh: Muhammad Ali" required>
            </div>
            <div class="form-group">
                <label>Nomor Telepon</label>
                <input type="text" name="nomor_telepon" id="nomor_telepon" placeholder="Contoh: 081234567890" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status">
                    <option value="tersedia">Tersedia</option>
                    <option value="bertugas">Bertugas</option>
                    <option value="libur">Libur</option>
                </select>
            </div>
            <div class="form-group">
                <label>Biaya per Hari</label>
                <input type="number" name="biaya_per_hari" id="biaya_per_hari" placeholder="Contoh: 150000" required>
            </div>

            <div class="btn-group">
                <button type="submit" name="tambah" id="btnSubmit" class="btn-simpan">Simpan</button>
                <button type="button" class="btn-batal" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(type, id = null) {
        const modal = document.getElementById('modalSopir');
        const title = document.getElementById('modalTitle');
        const btnSubmit = document.getElementById('btnSubmit');
        
        document.getElementById('editId').value = '';
        document.getElementById('nama_sopir').value = '';
        document.getElementById('nomor_telepon').value = '';
        document.getElementById('status').value = 'tersedia';
        document.getElementById('biaya_per_hari').value = '';
        
        if (type === 'tambah') {
            title.textContent = 'Tambah Sopir';
            btnSubmit.name = 'tambah';
        } else if (type === 'edit' && id) {
            title.textContent = 'Edit Sopir';
            btnSubmit.name = 'edit';
            
            fetch('get_sopir.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editId').value = data.id_sopir;
                    document.getElementById('nama_sopir').value = data.nama_sopir;
                    document.getElementById('nomor_telepon').value = data.nomor_telepon;
                    document.getElementById('status').value = data.status;
                    document.getElementById('biaya_per_hari').value = data.biaya_per_hari;
                })
                .catch(error => console.error('Error:', error));
        }
        
        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('modalSopir').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalSopir');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>