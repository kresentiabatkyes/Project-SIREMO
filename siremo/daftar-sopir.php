<?php
include 'connection.php';
?>

<html>
<head>
    <style>
        table {
            border-collapse: separate;
            border-spacing: 0 10px;
            width: 100%;
            margin-bottom: 25px;
        }
        th {
            border: 1px solid;
            padding: 12px 16px;
            text-align: center;
        }
        td {
            border: 1px solid;
            padding: 12px 16px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
<body>
    <table border="1">
        <tr>
            <th>Nama Sopir</th>
            <th>No. Telepon Sopir</th>
            <th>Status</th>
            <th>Biaya per Hari</th>
        </tr>
        
        <?php
        $query = "SELECT * FROM sopir";
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['nama_sopir'] . "</td>";
                echo "<td>" . $row['nomor_telepon'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['biaya_per_hari'] . "</td>";
            }
        } else {
            "<tr><td colspan='3'>Tidak ada data</td></tr>";
        }
?>
    </table>
</body>
</html>

body {
      width: 100%;
      min-height: 100vh;
      background: #f4f4f4;
      display: flex;
      justify-content: center; /* Memastikan pembungkus utama selalu di tengah layar */
      align-items: center;
      overflow-x: hidden; /* Mencegah horizontal scrollbar saat zoom-in besar */
      position: relative;
      padding: 20px;
    }

    /* KUNCI UTAMA: Menjaga proporsi layout kiri dan kanan agar tetap konstan saat di-zoom */
    .main-content {
      width: 100%;
      max-width: 1200px; /* Batas lebar konten utama */
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
      z-index: 10; /* Berada di atas background mobil */
    }