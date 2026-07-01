<?php
session_start();

// ========== CEK LOGIN ADMIN ==========
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: admin.php");
    exit();
}

$nama_admin = $_SESSION['nama'] ?? 'Admin';
include 'connection.php';

// ========== PROSES TAMBAH MOBIL ==========
if (isset($_POST['tambah'])) {
    $merek = mysqli_real_escape_string($conn, $_POST['merek']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    $harga = intval($_POST['harga']);
    $kapasitas = intval($_POST['kapasitas']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Buat nama file dari tipe mobil dengan ekstensi PNG (sesuai database)
    $foto = strtolower(str_replace(' ', '', $tipe)) . '.PNG';
    $foto_url = 'upload_foto/' . $foto;
    
    // Upload foto jika ada
    if ($_FILES['foto']['name']) {
        $target_file = "upload_foto/" . $foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
    }
    
    $query = "INSERT INTO mobil (merek, tipe, harga_per_hari, kapasitas_kursi, status, foto_url) 
              VALUES ('$merek', '$tipe', $harga, $kapasitas, '$status', '$foto_url')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['admin_message'] = "Mobil berhasil ditambahkan!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal menambahkan mobil: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_mobil.php");
    exit();
}

// ========== PROSES EDIT MOBIL ==========
if (isset($_POST['edit'])) {
    $id_mobil = intval($_POST['id_mobil']);
    $merek = mysqli_real_escape_string($conn, $_POST['merek']);
    $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
    $harga = intval($_POST['harga']);
    $kapasitas = intval($_POST['kapasitas']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Buat nama file dari tipe mobil dengan ekstensi PNG (sesuai database)
    $foto = strtolower(str_replace(' ', '', $tipe)) . '.PNG';
    $foto_url = 'upload_foto/' . $foto;
    
    // Upload foto baru jika ada
    $foto_update = ", foto_url = '$foto_url'";
    if ($_FILES['foto']['name']) {
        $target_file = "upload_foto/" . $foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
    }
    
    $query = "UPDATE mobil SET 
              merek = '$merek', 
              tipe = '$tipe', 
              harga_per_hari = $harga, 
              kapasitas_kursi = $kapasitas, 
              status = '$status'
              $foto_update
              WHERE id_mobil = $id_mobil";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['admin_message'] = "Mobil berhasil diupdate!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal update mobil: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_mobil.php");
    exit();
}

// ========== PROSES HAPUS MOBIL ==========
if (isset($_GET['hapus'])) {
    $id_mobil = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM mobil WHERE id_mobil = $id_mobil";
    if (mysqli_query($conn, $query_delete)) {
        $_SESSION['admin_message'] = "Mobil berhasil dihapus!";
        $_SESSION['admin_message_type'] = "success";
    } else {
        $_SESSION['admin_message'] = "Gagal hapus mobil: " . mysqli_error($conn);
        $_SESSION['admin_message_type'] = "error";
    }
    header("Location: admin_mobil.php");
    exit();
}

// ========== AMBIL DATA MOBIL ==========
$query = "SELECT * FROM mobil ORDER BY id_mobil DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

// Fungsi untuk dapatkan foto dengan ekstensi yang benar
function getFotoMobil($tipe) {
    // Coba dengan PNG dulu (sesuai database)
    $foto_png = strtolower(str_replace(' ', '', $tipe)) . '.PNG';
    if (file_exists('upload_foto/' . $foto_png)) {
        return 'upload_foto/' . $foto_png;
    }
    // Coba dengan JPG
    $foto_jpg = strtolower(str_replace(' ', '', $tipe)) . '.jpg';
    if (file_exists('upload_foto/' . $foto_jpg)) {
        return 'upload_foto/' . $foto_jpg;
    }
    // Coba dengan jpeg
    $foto_jpeg = strtolower(str_replace(' ', '', $tipe)) . '.jpeg';
    if (file_exists('upload_foto/' . $foto_jpeg)) {
        return 'upload_foto/' . $foto_jpeg;
    }
    // Coba dengan png lowercase
    $foto_png_lower = strtolower(str_replace(' ', '', $tipe)) . '.png';
    if (file_exists('upload_foto/' . $foto_png_lower)) {
        return 'upload_foto/' . $foto_png_lower;
    }
    // Jika tidak ada, return placeholder
    return 'upload_foto/placeholder.jpg';
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
    <title>Kelola Mobil - SIREMO Admin</title>
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
            background: #f4f7fc;
            display: flex;
            min-height: 100vh;
        }

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
        .sidebar-brand img {
            width: 50px;
            height: 45px;
            object-fit: contain;
        }
        .sidebar-brand h2 {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }
        .sidebar-brand h2 span {
            color: #38b6ff;
        }
        .sidebar-brand small {
            display: block;
            font-size: 9px;
            font-weight: 300;
            letter-spacing: 2px;
            color: #9ca3af;
        }

        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
        }
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
        .sidebar-menu li a i {
            width: 20px;
            font-size: 16px;
        }
        .sidebar-menu li a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .sidebar-menu li.active a {
            background: #2563EB;
            color: #fff;
        }
        .sidebar-menu .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            padding: 15px 16px 8px;
            letter-spacing: 1px;
        }

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
        .sidebar-footer a:hover {
            background: rgba(239, 68, 68, 0.15);
        }

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
        .main-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }
        .main-header h1 span {
            font-size: 14px;
            font-weight: 400;
            color: #6b7280;
        }
        .main-header .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
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
        .main-header .admin-info span {
            font-weight: 500;
            color: #1f2937;
        }

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
        .btn-tambah:hover {
            background: #1e4fc9;
        }

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
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-content h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .modal-content .form-group {
            margin-bottom: 15px;
        }
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
        .modal-content .form-group select:focus {
            border-color: #2563EB;
        }
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
        .modal-content .btn-group .btn-simpan {
            background: #2563EB;
            color: white;
        }
        .modal-content .btn-group .btn-simpan:hover {
            background: #1e4fc9;
        }
        .modal-content .btn-group .btn-batal {
            background: #e5e7eb;
            color: #374151;
        }
        .modal-content .btn-group .btn-batal:hover {
            background: #d1d5db;
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
        .table-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .table-header h3 i {
            color: #2563EB;
            margin-right: 8px;
        }

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

        .car-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .car-cell img {
            width: 60px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
            border: 1px solid #e5e7eb;
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
        .badge-danger { background: #fee2e2; color: #991b1b; }

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
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        .btn-edit:hover {
            background: #bfdbfe;
        }
        .btn-hapus {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-hapus:hover {
            background: #fecaca;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            .sidebar-brand h2, .sidebar-brand small, .sidebar-menu li a span, .menu-label, .sidebar-footer a span {
                display: none;
            }
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
        <li class="active"><a href="admin_mobil.php"><i class="fa-solid fa-car"></i> <span>Mobil</span></a></li>
        <li><a href="admin_pemesanan.php"><i class="fa-regular fa-receipt"></i> <span>Pemesanan</span></a></li>
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
        <h1>Kelola Mobil <span>Data mobil yang tersedia</span></h1>
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

    <div class="table-wrapper">
        <div class="table-header">
            <h3><i class="fa-solid fa-car"></i> Daftar Mobil</h3>
            <button class="btn-tambah" onclick="openModal('tambah')">
                <i class="fa-solid fa-plus"></i> Tambah Mobil
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mobil</th>
                    <th>Harga / Hari</th>
                    <th>Kapasitas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php 
                        // Cari foto dengan berbagai ekstensi
                        $foto_path = getFotoMobil($row['tipe']);
                        ?>
                        <tr>
                            <td>#<?= $row['id_mobil'] ?></td>
                            <td>
                                <div class="car-cell">
                                    <img src="<?= $foto_path ?>" 
                                         alt="<?= htmlspecialchars($row['tipe']) ?>"
                                         onerror="this.src='upload_foto/placeholder.jpg'">
                                    <div>
                                        <strong><?= htmlspecialchars($row['merek'] . ' ' . $row['tipe']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>Rp<?= number_format($row['harga_per_hari'], 0, ',', '.') ?></td>
                            <td><?= $row['kapasitas_kursi'] ?> Kursi</td>
                            <td>
                                <span class="badge <?= $row['status'] == 'tersedia' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $row['status'] == 'tersedia' ? 'Tersedia' : 'Disewa' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-aksi btn-edit" onclick="openModal('edit', <?= $row['id_mobil'] ?>)">
                                    <i class="fa-regular fa-pen-to-square"></i> Edit
                                </button>
                                <a href="?hapus=<?= $row['id_mobil'] ?>" class="btn-aksi btn-hapus" onclick="return confirm('Yakin hapus mobil ini?')">
                                    <i class="fa-regular fa-trash-can"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#9ca3af;">
                            <i class="fa-regular fa-car" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                            Belum ada data mobil
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- ========== MODAL ========== -->
<div class="modal" id="modalMobil">
    <div class="modal-content">
        <h2 id="modalTitle">Tambah Mobil</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_mobil" id="editId">
            
            <div class="form-group">
                <label>Merek</label>
                <input type="text" name="merek" id="merek" placeholder="Contoh: Toyota" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <input type="text" name="tipe" id="tipe" placeholder="Contoh: Avanza" required>
            </div>
            <div class="form-group">
                <label>Harga per Hari</label>
                <input type="number" name="harga" id="harga" placeholder="Contoh: 300000" required>
            </div>
            <div class="form-group">
                <label>Kapasitas Kursi</label>
                <input type="number" name="kapasitas" id="kapasitas" placeholder="Contoh: 5" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status">
                    <option value="tersedia">Tersedia</option>
                    <option value="disewa">Disewa</option>
                </select>
            </div>
            <div class="form-group">
                <label>Foto Mobil</label>
                <input type="file" name="foto" id="foto" accept="image/*">
                <small style="color:#9ca3af; font-size:12px;">Format: JPG, PNG (kosongkan jika tidak ingin mengganti)</small>
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
        const modal = document.getElementById('modalMobil');
        const title = document.getElementById('modalTitle');
        const btnSubmit = document.getElementById('btnSubmit');
        
        document.getElementById('editId').value = '';
        document.getElementById('merek').value = '';
        document.getElementById('tipe').value = '';
        document.getElementById('harga').value = '';
        document.getElementById('kapasitas').value = '';
        document.getElementById('status').value = 'tersedia';
        document.getElementById('foto').value = '';
        
        if (type === 'tambah') {
            title.textContent = 'Tambah Mobil';
            btnSubmit.name = 'tambah';
        } else if (type === 'edit' && id) {
            title.textContent = 'Edit Mobil';
            btnSubmit.name = 'edit';
            
            fetch('get_mobil.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editId').value = data.id_mobil;
                    document.getElementById('merek').value = data.merek;
                    document.getElementById('tipe').value = data.tipe;
                    document.getElementById('harga').value = data.harga_per_hari;
                    document.getElementById('kapasitas').value = data.kapasitas_kursi;
                    document.getElementById('status').value = data.status;
                })
                .catch(error => console.error('Error:', error));
        }
        
        modal.classList.add('show');
    }

    function closeModal() {
        document.getElementById('modalMobil').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalMobil');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>