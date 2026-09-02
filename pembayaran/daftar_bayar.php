<?php
// ================== KONFIGURASI ==================
$file_data = "data/pembayaran.json";
// Ganti kata sandi agar hanya sekolah yang bisa akses
$kata_sandi = "sekolah123"; // Ubah sandi ini!
// =================================================

// Proteksi akses
session_start();
if (!isset($_SESSION['sudah_login']) || $_SESSION['sudah_login'] !== true) {
    if ($_POST['sandi'] === $kata_sandi) {
        $_SESSION['sudah_login'] = true;
        header("Location: daftar_bayar.php");
        exit;
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Login Daftar Pembayaran</title>
    <style>
    *{box-sizing:border-box;font-family:Segoe UI,sans-serif}
    body{background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
    .kotak{background:#fff;padding:30px;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.1);width:100%;max-width:400px}
    h2{text-align:center;color:#1e293b;margin-bottom:25px}
    input{width:100%;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;margin-bottom:15px}
    button{width:100%;padding:12px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer}
    button:hover{background:#1d4ed8}
    </style></head><body>
    <div class="kotak">
        <h2>🔒 Akses Daftar Pembayaran</h2>
        <form method="post">
            <input type="password" name="sandi" placeholder="Masukkan Kata Sandi Sekolah" required>
            <button type="submit">Masuk</button>
        </form>
    </div>
    </body></html>';
    exit;
}

// Tandai sebagai sudah diverifikasi
if (isset($_GET['verifikasi'])) {
    $data = json_decode(file_get_contents($file_data), true);
    foreach ($data as &$item) {
        if ($item['id'] === $_GET['verifikasi']) {
            $item['status'] = 'Sudah Diverifikasi';
            break;
        }
    }
    file_put_contents($file_data, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: daftar_bayar.php");
    exit;
}

// Hapus entri
if (isset($_GET['hapus'])) {
    $data = json_decode(file_get_contents($file_data), true);
    $data = array_filter($data, fn($i) => $i['id'] !== $_GET['hapus']);
    file_put_contents($file_data, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: daftar_bayar.php");
    exit;
}

$data = file_exists($file_data) ? json_decode(file_get_contents($file_data), true) ?: [] : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pembayaran Sekolah</title>
    <style>
        * {box-sizing: border-box; font-family: Segoe UI, sans-serif;}
        body {background: #f0f4f8; margin: 0; padding: 20px;}
        .container {max-width: 1000px; margin: 0 auto;}
        .header {display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;}
        h1 {color: #1e293b; margin: 0;}
        .ringkas {display: flex; gap: 15px; margin-bottom: 20px;}
        .kartu {background: #fff; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);}
        .kartu h3 {margin: 0 0 5px 0; font-size: 14px; color: #64748b;}
        .kartu p {margin: 0; font-size: 22px; font-weight: 700; color: #1e293b;}
        table {width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);}
        th, td {padding: 12px 15px; text-align: left; border-bottom: 1px solid #f1f5f9;}
        th {background: #f8fafc; font-weight: 600; color: #475569; font-size: 14px;}
        tr:hover {background: #f8fafc;}
        .waktu {font-size: 12px; color: #94a3b8;}
        .status {padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 500;}
        .status.menunggu {background: #fef3c7; color: #92400e;}
        .status.selesai {background: #dcfce7; color: #166534;}
        .btn {padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-block; margin: 2px;}
        .btn.verifikasi {background: #22c55e; color: #fff;}
        .btn.bukti {background: #3b82f6; color: #fff;}
        .btn.hapus {background: #ef4444; color: #fff;}
        .btn.keluar {background: #f1f5f9; color: #475569; float: right; margin-top: 10px;}
        .kosong {text-align: center; padding: 40px; color: #94a3b8;}
        @media(max-width:768px){th,td{padding:10px 8px;font-size:14px}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Daftar Pembayaran</h1>
        <a href="?keluar" class="btn keluar">Keluar</a>
    </div>

    <div class="ringkas">
        <div class="kartu">
            <h3>Total Pembayaran</h3>
            <p><?php echo count($data); ?></p>
        </div>
        <div class="kartu">
            <h3>Menunggu Verifikasi</h3>
            <p><?php echo count(array_filter($data, fn($i) => $i['status']==='Menunggu')); ?></p>
        </div>
        <div class="kartu">
            <h3>Total Uang Masuk</h3>
            <p>Rp <?php echo number_format(array_sum(array_column($data, 'total')),0,',','.'); ?></p>
        </div>
    </div>

    <?php if (empty($data)): ?>
        <table><tbody><tr><td class="kosong">Belum ada data pembayaran yang masuk.</td></tr></tbody></table>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jenis Pembayaran</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $item): ?>
            <tr>
                <td class="waktu"><?php echo $item['waktu']; ?></td>
                <td><strong><?php echo $item['nama']; ?></strong></td>
                <td><?php echo $item['kelas']; ?></td>
                <td><?php echo is_array($item['jenis']) ? implode(', ', $item['jenis']) : $item['jenis']; ?></td>
                <td>Rp <?php echo number_format($item['total'],0,',','.'); ?></td>
                <td><?php echo $item['metode']; ?></td>
                <td><span class="status <?php echo $item['status']==='Menunggu'?'menunggu':'selesai'; ?>"><?php echo $item['status']; ?></span></td>
                <td>
                    <?php if (!empty($item['bukti'])): ?>
                    <a href="<?php echo $item['bukti']; ?>" target="_blank" class="btn bukti">📎 Bukti</a>
                    <?php endif; ?>
                    <?php if ($item['status']==='Menunggu'): ?>
                    <a href="?verifikasi=<?php echo $item['id']; ?>" class="btn verifikasi" onclick="return confirm('Tandai sudah diverifikasi?')">✅ Verifikasi</a>
                    <?php endif; ?>
                    <a href="?hapus=<?php echo $item['id']; ?>" class="btn hapus" onclick="return confirm('Yakin hapus data ini?')">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
<?php
if (isset($_GET['keluar'])) {
    session_destroy();
    header("Location: daftar_bayar.php");
    exit;
}
