<?php
// Wajib dimulai untuk menyimpan ingatan data server (state) antar-reload
session_start();

// ==========================================
// 1. LOGIKA KONTROL (TOMBOL MENU)
// ==========================================

// Jika tombol Reset ditekan, hapus semua ingatan dan kembalikan ke 0
if (isset($_GET['reset'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Jika tombol Stop ditekan, hentikan simulasi
if (isset($_GET['stop'])) {
    $_SESSION['is_running'] = false;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Jika tombol algoritma dipilih, jalankan simulasi
if (isset($_GET['algo'])) {
    $_SESSION['algo'] = $_GET['algo'];
    $_SESSION['is_running'] = true;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// ==========================================
// 2. INISIALISASI DATA AWAL (Saat web pertama dibuka)
// ==========================================
if (!isset($_SESSION['servers'])) {
    $_SESSION['servers'] = ['Server_A' => 0, 'Server_B' => 0, 'Server_C' => 0];
    $_SESSION['logs'] = [];
    $_SESSION['rr_index'] = 0;
    $_SESSION['request_count'] = 0;
    $_SESSION['is_running'] = false;
    $_SESSION['algo'] = 'round_robin';
}

// ==========================================
// 3. LOGIKA INTI LOAD BALANCER (Hanya dieksekusi jika statusnya "Running")
// ==========================================
if ($_SESSION['is_running']) {
    $_SESSION['request_count']++;
    $terpilih = '';
    $server_keys = array_keys($_SESSION['servers']);

    // Pemilihan Algoritma
    if ($_SESSION['algo'] == 'round_robin') {
        // Logika Statis: Maju satu langkah ke server berikutnya
        $terpilih = $server_keys[$_SESSION['rr_index']];
        $_SESSION['rr_index'] = ($_SESSION['rr_index'] + 1) % count($server_keys);
    } else {
        // Logika Dinamis (Least Conn): Cari angka terkecil di memori server
        $terendah = min($_SESSION['servers']);
        foreach ($_SESSION['servers'] as $nama => $koneksi) {
            if ($koneksi == $terendah) {
                $terpilih = $nama;
                break;
            }
        }
    }

    // Eksekusi: Masukkan pengunjung ke server yang terpilih
    $_SESSION['servers'][$terpilih]++;
    $pesan_masuk = "[+] Masuk #{$_SESSION['request_count']} ➔ $terpilih (Beban sekarang: {$_SESSION['servers'][$terpilih]})";
    array_unshift($_SESSION['logs'], $pesan_masuk); // Masukkan log ke paling atas

    // Kejadian Acak: Simulasi ada siswa yang selesai ujian (Peluang 40%)
    if (rand(1, 100) > 60) {
        $acak = $server_keys[array_rand($server_keys)];
        if ($_SESSION['servers'][$acak] > 0) {
            $_SESSION['servers'][$acak]--;
            $pesan_keluar = "[-] Keluar ⚡: 1 Sesi di $acak selesai (Sisa beban: {$_SESSION['servers'][$acak]})";
            array_unshift($_SESSION['logs'], $pesan_keluar);
        }
    }

    // Batasi log maksimal 15 baris agar tidak memenuhi memori
    if (count($_SESSION['logs']) > 15) {
        array_pop($_SESSION['logs']);
    }
}

// ==========================================
// 4. RENDER TAMPILAN WEB (Tanpa JavaScript)
// ==========================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Mesin PHP Load Balancer</title>
    <?php if ($_SESSION['is_running']): ?>
        <meta http-equiv="refresh" content="1">
    <?php endif; ?>
    
    <style>
        /* Gaya minimalis menyerupai terminal hacker */
        body { font-family: 'Courier New', Courier, monospace; background: #0d0d0d; color: #0f0; padding: 20px; }
        .menu { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #0f0; }
        a { color: #000; background: #0f0; padding: 8px 15px; text-decoration: none; font-weight: bold; margin-right: 10px; }
        a.stop { background: #f00; color: #fff; }
        .container { display: flex; gap: 20px; }
        .box { border: 1px solid #0f0; padding: 15px; width: 250px; }
        .box h2 { margin-top: 0; border-bottom: 1px solid #0f0; padding-bottom: 5px; }
        .angka { font-size: 40px; font-weight: bold; margin: 10px 0; }
        .log-box { margin-top: 20px; border: 1px solid #0f0; padding: 15px; background: #001a00; }
    </style>
</head>
<body>

    <div class="menu">
        <h1>⚙️ ENGINE PHP: LOAD BALANCER</h1>
        <p>Status Mesin: 
            <strong>
                <?php echo $_SESSION['is_running'] ? 'MENYALA (' . strtoupper($_SESSION['algo']) . ')' : 'BERHENTI'; ?>
            </strong>
        </p>
        
        <?php if (!$_SESSION['is_running']): ?>
            <a href="?algo=round_robin">▶ Mulai Round Robin</a>
            <a href="?algo=least_connection">▶ Mulai Least Connection</a>
        <?php else: ?>
            <a href="?stop=1" class="stop">■ Hentikan Mesin</a>
        <?php endif; ?>
        
        <a href="?reset=1" style="background: #ccc;">↻ Reset Ulang Data</a>
    </div>

    <div class="container">
        <?php foreach ($_SESSION['servers'] as $nama => $beban): ?>
            <div class="box">
                <h2><?php echo $nama; ?></h2>
                <div>Koneksi Aktif:</div>
                <div class="angka"><?php echo $beban; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="log-box">
        <h3>Riwayat Aktivitas (Log Server)</h3>
        <?php foreach ($_SESSION['logs'] as $log): ?>
            <div><?php echo htmlspecialchars($log); ?></div>
        <?php endforeach; ?>
        <?php if (empty($_SESSION['logs'])) echo "<div>Belum ada aktivitas. Silakan mulai mesin.</div>"; ?>
    </div>

</body>
</html>