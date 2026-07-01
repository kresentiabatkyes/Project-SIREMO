<?php
session_start();

// ========== CEK LOGIN ADMIN ==========
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin.php");
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

$kolom_tanggal_mulai = in_array('tanggal_mulai', $kolom_list) ? 'tanggal_mulai' : (in_array('tanggal_sewa', $kolom_list) ? 'tanggal_sewa' : 'tanggal_mulai');
$kolom_tanggal_selesai = in_array('tanggal_selesai', $kolom_list) ? 'tanggal_selesai' : (in_array('tanggal_kembali', $kolom_list) ? 'tanggal_kembali' : 'tanggal_selesai');
$kolom_status = in_array('status_pemesanan', $kolom_list) ? 'status_pemesanan' : (in_array('status', $kolom_list) ? 'status' : 'status_pemesanan');

// ========== CEK STRUKTUR TABEL PELANGGAN ==========
$cek_kolom_pel = "SHOW COLUMNS FROM pelanggan";
$result_kolom_pel = mysqli_query($conn, $cek_kolom_pel);
$kolom_pel_list = [];
while ($row_kolom = mysqli_fetch_assoc($result_kolom_pel)) {
    $kolom_pel_list[] = $row_kolom['Field'];
}

// Tentukan kolom yang ada di tabel pelanggan
$kolom_no_telepon = in_array('no_telepon', $kolom_pel_list) ? 'pel.no_telepon' : 'NULL as no_telepon';

// ========== FILTER STATUS ==========
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';
$where = '';
if ($filter_status != 'semua') {
    $where = "WHERE p.$kolom_status = '$filter_status'";
}

// ========== AMBIL SEMUA PEMESANAN ==========
$query = "SELECT 
            p.*, 
            m.merek, 
            m.tipe, 
            m.kapasitas_kursi,
            pel.nama_lengkap as nama_pelanggan,
            pel.email,
            $kolom_no_telepon,
            s.nama_sopir,
            s.id_sopir
          FROM pemesanan p
          JOIN mobil m ON p.id_mobil = m.id_mobil
          JOIN pelanggan pel ON p.id_pelanggan = pel.id_pelanggan
          LEFT JOIN sopir s ON p.id_sopir = s.id_sopir
          $where
          ORDER BY p.id_pemesanan DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

// ========== PROSES UPDATE STATUS ==========
if (isset($_POST['update_status'])) {
    $id_pemesanan = intval($_POST['id_pemesanan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    
    $query_update = "UPDATE pemesanan SET $kolom_status = '$status_baru' WHERE id_pemesanan = $id_pemesanan";
    if (mysqli_query($conn, $query_update)) {
        $_SESSION['admin_message'] = "Status pemesanan berhasil diupdate!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal update status: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_pemesanan.php?status=" . $filter_status);
    exit();
}

// ========== PROSES HAPUS PEMESANAN ==========
if (isset($_GET['hapus'])) {
    $id_pemesanan = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM pemesanan WHERE id_pemesanan = $id_pemesanan";
    if (mysqli_query($conn, $query_delete)) {
        $_SESSION['admin_message'] = "Pemesanan berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal hapus pemesanan: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_pemesanan.php?status=" . $filter_status);
    exit();
}

// ========== AMBIL MESSAGE ==========
$message = $_SESSION['admin_message'] ?? '';
$message_type = $_SESSION['admin_message_type'] ?? '';
unset($_SESSION['admin_message']);
unset($_SESSION['admin_message_type']);

// ========== HITUNG STATISTIK ==========
$total_pemesanan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan"))['total'] ?? 0;
$stat_menunggu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE $kolom_status = 'Menunggu'"))['total'] ?? 0;
$stat_dikonfirmasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE $kolom_status = 'Dikonfirmasi'"))['total'] ?? 0;
$stat_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE $kolom_status = 'Selesai'"))['total'] ?? 0;
$stat_ditolak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pemesanan WHERE $kolom_status = 'Ditolak'"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - SIREMO Admin</title>
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

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 15px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid #2563EB;
        }
        .stat-card .number { font-size: 24px; font-weight: 700; }
        .stat-card .label { font-size: 12px; color: #6b7280; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.warning .number { color: #f59e0b; }
        .stat-card.success { border-left-color: #22c55e; }
        .stat-card.success .number { color: #22c55e; }
        .stat-card.info { border-left-color: #3b82f6; }
        .stat-card.info .number { color: #3b82f6; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.danger .number { color: #ef4444; }
        .stat-card.total { border-left-color: #1f2937; }
        .stat-card.total .number { color: #1f2937; }

        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-bar a {
            padding: 6px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            background: #e5e7eb;
            color: #374151;
            transition: 0.2s;
        }
        .filter-bar a:hover { background: #d1d5db; }
        .filter-bar a.active {
            background: #2563EB;
            color: white;
        }

        .table-wrapper {
            background: white;
            border-radius: 14px;
            padding: 20px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
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
            padding: 10px 12px;
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
            padding: 10px 12px;
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
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #e5e7eb; color: #374151; }

        .form-status {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        .form-status select {
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 11px;
            background: white;
            outline: none;
        }
        .form-status select:focus { border-color: #2563EB; }
        .form-status button {
            padding: 3px 10px;
            border-radius: 4px;
            border: none;
            background: #2563EB;
            color: white;
            font-size: 11px;
            cursor: pointer;
            transition: 0.2s;
        }
        .form-status button:hover { background: #1e4fc9; }

        .btn-hapus {
            color: #ef4444;
            text-decoration: none;
            font-size: 13px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: 0.2s;
        }
        .btn-hapus:hover { background: #fee2e2; }

        .text-muted { color: #9ca3af; font-size: 12px; }
        .text-success { color: #22c55e; }

        @media (max-width: 992px) {
            .sidebar { width: 70px; padding: 15px 10px; }
            .sidebar-brand h2, .sidebar-brand small, .sidebar-menu li a span, .menu-label, .sidebar-footer a span { display: none; }
            .sidebar-brand img { width: 40px; height: 35px; }
            .sidebar-menu li a { justify-content: center; padding: 12px; }
            .sidebar-footer a { justify-content: center; }
            .main-content { margin-left: 70px; padding: 20px; }
            .stats { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 600px) {
            .main-content { padding: 15px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .table-wrapper { padding: 15px; }
            table { font-size: 11px; }
            .form-status { flex-wrap: wrap; }
            .main-header { flex-direction: column; align-items: flex-start; gap: 10px; }
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
        <li class="active"><a href="admin_pemesanan.php"><i class="fa-regular fa-receipt"></i> <span>Pemesanan</span></a></li>
        <li><a href="admin_sopir.php"><i class="fa-solid fa-user-tie"></i> <span>Sopir</span></a></li>
        <li><a href="admin_pelanggan.php"><i class="fa-regular fa-user"></i> <span>Pelanggan</span></a></li>
        <li><a href="admin_laporan.php"><i class="fa-regular fa-file-lines"></i> <span>Laporan</span></a></li>
        <li><a href="admin_ulasan.php"><i class="fa-regular fa-star"></i> <span>Ulasan</span></a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout_admin.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main-content">

    <div class="main-header">
        <h1>Kelola Pemesanan <span>Semua data pemesanan</span></h1>
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

    <!-- STATISTIK -->
    <div class="stats">
        <div class="stat-card total">
            <div class="number"><?= $total_pemesanan ?></div>
            <div class="label">Total Pesanan</div>
        </div>
        <div class="stat-card warning">
            <div class="number"><?= $stat_menunggu ?></div>
            <div class="label">Menunggu</div>
        </div>
        <div class="stat-card success">
            <div class="number"><?= $stat_dikonfirmasi ?></div>
            <div class="label">Dikonfirmasi</div>
        </div>
        <div class="stat-card info">
            <div class="number"><?= $stat_selesai ?></div>
            <div class="label">Selesai</div>
        </div>
        <div class="stat-card danger">
            <div class="number"><?= $stat_ditolak ?></div>
            <div class="label">Ditolak</div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-bar">
        <a href="admin_pemesanan.php?status=semua" class="<?= $filter_status == 'semua' ? 'active' : '' ?>">Semua</a>
        <a href="admin_pemesanan.php?status=Menunggu" class="<?= $filter_status == 'Menunggu' ? 'active' : '' ?>">Menunggu</a>
        <a href="admin_pemesanan.php?status=Dikonfirmasi" class="<?= $filter_status == 'Dikonfirmasi' ? 'active' : '' ?>">Dikonfirmasi</a>
        <a href="admin_pemesanan.php?status=Selesai" class="<?= $filter_status == 'Selesai' ? 'active' : '' ?>">Selesai</a>
        <a href="admin_pemesanan.php?status=Ditolak" class="<?= $filter_status == 'Ditolak' ? 'active' : '' ?>">Ditolak</a>
        <a href="admin_pemesanan.php?status=Batal" class="<?= $filter_status == 'Batal' ? 'active' : '' ?>">Batal</a>
    </div>

    <!-- TABLE -->
    <div class="table-wrapper">
        <div class="table-header">
            <h3><i class="fa-regular fa-receipt"></i> Daftar Pemesanan</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pelanggan</th>
                    <th>Mobil</th>
                    <th>Sopir</th>
                    <th>Tanggal Sewa</th>
                    <th>Tanggal Kembali</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                        $tanggal_mulai = $row[$kolom_tanggal_mulai] ?? date('Y-m-d');
                        $tanggal_selesai = $row[$kolom_tanggal_selesai] ?? date('Y-m-d');
                        $status = $row[$kolom_status] ?? 'Menunggu';
                        
                        $badge_class = '';
                        switch ($status) {
                            case 'Menunggu': $badge_class = 'badge-warning'; break;
                            case 'Dikonfirmasi': $badge_class = 'badge-success'; break;
                            case 'Selesai': $badge_class = 'badge-info'; break;
                            case 'Ditolak': $badge_class = 'badge-danger'; break;
                            case 'Batal': $badge_class = 'badge-secondary'; break;
                            default: $badge_class = 'badge-secondary';
                        }
                        ?>
                        <tr>
                            <td>#<?= $row['id_pemesanan'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                                <span class="text-muted"><?= htmlspecialchars($row['email']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($row['merek'] . ' ' . $row['tipe']) ?></td>
                            <td>
                                <?php if ($row['nama_sopir']): ?>
                                    <span class="text-success"><i class="fa-regular fa-circle-check"></i> <?= htmlspecialchars($row['nama_sopir']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($tanggal_mulai)) ?></td>
                            <td><?= date('d M Y', strtotime($tanggal_selesai)) ?></td>
                            <td><strong>Rp<?= number_format($row['total_harga'], 0, ',', '.') ?></strong></td>
                            <td><span class="badge <?= $badge_class ?>"><?= $status ?></span></td>
                            <td>
                                <form method="POST" class="form-status" onsubmit="return confirm('Update status pemesanan ini?')">
                                    <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                    <select name="status_baru">
                                        <option value="Menunggu" <?= $status == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                        <option value="Dikonfirmasi" <?= $status == 'Dikonfirmasi' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                        <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                        <option value="Ditolak" <?= $status == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                        <option value="Batal" <?= $status == 'Batal' ? 'selected' : '' ?>>Batal</option>
                                    </select>
                                    <button type="submit" name="update_status"><i class="fa-solid fa-check"></i></button>
                                </form>
                                <a href="?hapus=<?= $row['id_pemesanan'] ?>&status=<?= $filter_status ?>" class="btn-hapus" onclick="return confirm('Yakin hapus pemesanan ini?')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:40px; color:#9ca3af;">
                            <i class="fa-regular fa-receipt" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                            Belum ada pemesanan
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>