<?php
session_start();
if (!isset($_SESSION['id_pelanggan']) && !isset($_SESSION['user'])) {
    header("Location: login.html");
    exit();
}
$nama_user = $_SESSION['nama'] ?? $_SESSION['user'] ?? 'Pelanggan';
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
}

/* NAVBAR */

.navbar{
    background:#fff;
    height:90px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 40px;
    border-bottom:1px solid #ddd;
}

/* kiri */
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
    height: 60px;
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

/* tengah */
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

.nav-menu li.active{
    color:#2563EB;
    border-bottom:3px solid #2563EB;
    font-weight:600;
}

/* kanan */
.profile{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:180px;
    justify-content:flex-end;
}

.profile img{
    width:45px;
    height:45px;
    border-radius:50%;
    object-fit:cover;
}

.profile span{
    font-size:15px;
    font-weight:500;
}

/* HERO */

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

/* HEADER SECTION */

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

.filter{
    padding:10px 15px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#fff;
    cursor:pointer;
}

/* CARD GRID */

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
}

.btn:hover{
    background:#1e4fc9;
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
}

.hero h1{
    font-size:24px;
}

}

</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">

    <!-- KIRI -->
    <div class="nav-left">

        <div class="logo">
            <img src="upload_foto/logo siremo.png" alt="Logo SIREMO">

            <div class="logo-text">
                <h2>SIREMO</h2>
                <p>RENT A CAR, EASY, & TRUSTED</p>
            </div>
        </div>

    </div>

    <!-- TENGAH -->
    <ul class="nav-menu">

        <li class="active">
            <i class="fa-solid fa-house"></i>
            Beranda
        </li>

        <li>
            <i class="fa-solid fa-car"></i>
            Mobil
        </li>

        <li>
            <i class="fa-solid fa-clock-rotate-left"></i>
            Riwayat
        </li>

        <li>
            <i class="fa-regular fa-circle-question"></i>
            Bantuan
        </li>

    </ul>

    <!-- KANAN -->
    <div class="profile">
        <img src="https://i.pravatar.cc/100" alt="">
        <span>Citro Dewi</span>
    </div>

</nav>

<!-- HERO -->

<section class="hero">

    <h1>
        Temukan Mobil Sewa Terbaik Untuk Perjalanan Anda
    </h1>

    <p>
        Pilih mobil sesuai kebutuhan dan nikmati perjalanan nyaman bersama SIREMO
    </p>

</section>

<!-- HEADER -->

<div class="section-header">

    <h2>Daftar Mobil</h2>

</div>

<!-- CARD GRID -->

<div class="car-grid">

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/brio.jpg">
            <span class="badge available">Tersedia</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Honda Brio</h3>
                <div class="price">Rp300.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                4 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/xenia.jpg">
            <span class="badge available">Tersedia</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Daihatsu Xenia</h3>
                <div class="price">Rp300.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                6 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/innova.jpg">
            <span class="badge rented">Disewa</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Toyota Innova</h3>
                <div class="price">Rp350.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                8 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/xpanter.jpg">
            <span class="badge available">Tersedia</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Mitsubishi Xpander</h3>
                <div class="price">Rp350.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                7 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/ertiga.jpg">
            <span class="badge rented">Disewa</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Suzuki Ertiga</h3>
                <div class="price">Rp300.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                5 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

    <div class="car-card">

        <div class="car-image">
            <img src="upload_foto/avanza.jpg">
            <span class="badge available">Tersedia</span>
        </div>

        <div class="car-info">

            <div class="car-title">
                <h3>Toyota Avanza</h3>
                <div class="price">Rp350.000/hari</div>
            </div>

            <div class="spec">
                <i class="fa-solid fa-user-group"></i>
                6 Kursi
            </div>

            <button class="btn">
                Detail & Sewa
            </button>

        </div>

    </div>

</div>

</body>
</html>