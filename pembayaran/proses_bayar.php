<?php
// ================== KONFIGURASI SEKOLAH ==================
$nomor_wa = "6281234567890";       // Ganti nomor WA sekolah (format: 62...)
$email_tujuan = "pembayaran@sekolah.sch.id"; // Email sekolah
$direktori_upload = "bukti_bayar/";
$file_data = "data/pembayaran.json";
$batas_ukuran = 5 * 1024 * 1024; // Maksimal 5MB
$jenis_file_diizinkan = ['jpg', 'jpeg', 'png', 'pdf'];
// =========================================================

// Buat folder jika belum ada
if (!file_exists($direktori_upload)) mkdir($direktori_upload, 0755, true);
if (!file_exists(dirname($file_data))) mkdir(dirname($file_data), 0755, true);
if (!file_exists($file_data)) file_put_contents($file_data, '[]');

function pesan($jenis, $teks) {
    echo "<script>alert('$teks'); history.back();</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
if (empty($_POST['nama']) || empty($_POST['kelas']) || empty($_POST['metode']) || empty($_POST['jenis'])) {
    pesan('error', 'Lengkapi semua data wajib!');
}

$nama = htmlspecialchars(trim($_POST['nama']));
$kelas = htmlspecialchars($_POST['kelas']);
$metode = htmlspecialchars($_POST['metode']);
$total = (int)$_POST['total'];
$total_rupiah = number_format($total, 0, ',', '.');
$jenis_bayar = implode(', ', $_POST['jenis']);

$nama_file = '';
if ($metode !== 'Tunai') {
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        pesan('error', 'Silakan unggah file bukti pembayaran!');
    }
    $info_file = pathinfo($_FILES['bukti']['name']);
    $ekstensi = strtolower($info_file['extension']);
    if (!in_array($ekstensi, $jenis_file_diizinkan)) {
        pesan('error', 'Hanya file JPG, PNG, PDF yang diperbolehkan!');
    }
    if ($_FILES['bukti']['size'] > $batas_ukuran) {
        pesan('error', 'Ukuran file maksimal 5MB!');
    }
    $nama_file = $direktori_upload . date('Ymd-His') . '-' . preg_replace('/[^A-Za-z0-9_-]/', '', $nama) . '.' . $ekstensi;
    if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $nama_file)) {
        pesan('error', 'Gagal menyimpan file bukti!');
    }
}

// === SIMPAN DATA KE DAFTAR PEMBAYARAN ===
$data_lama = json_decode(file_get_contents($file_data), true) ?: [];
$entri_baru = [
    'id'       => uniqid(),
    'waktu'    => date('Y-m-d H:i:s'),
    'nama'     => $nama,
    'kelas'    => $kelas,
    'jenis'    => $_POST['jenis'],
    'total'    => $total,
    'metode'   => $metode,
    'bukti'    => $nama_file,
    'status'   => 'Menunggu'
];
array_unshift($data_lama, $entri_baru);
file_put_contents($file_data, json_encode($data_lama, JSON_PRETTY_PRINT));
// =========================================

$pesan_wa = "📋 LAPORAN PEMBAYARAN\n\n";
$pesan_wa .= "👋 Nama: $nama\n";
$pesan_wa .= "🏫 Kelas: $kelas\n";
$pesan_wa .= "✅ Jenis Bayar: $jenis_bayar\n";
$pesan_wa .= "💰 Total: Rp $total_rupiah\n";
$pesan_wa .= "💳 Metode: $metode\n";
if ($metode === 'Tunai') {
    $pesan_wa .= "📎 Bukti: Diserahkan langsung ke sekolah\n";
} else {
    $pesan_wa .= "📎 Bukti: Telah diunggah\n";
}
$pesan_wa .= "\nTerima kasih, silakan diverifikasi.";

$url_wa = "https://wa.me/$nomor_wa?text=" . urlencode($pesan_wa);
$email_subjek = "Pembayaran - $nama - Kelas $kelas";
$email_isi = $pesan_wa . "\n\nLihat daftar lengkap: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/daftar_bayar.php";
$headers = "From: Pembayaran Sekolah <no-reply@sekolah.sch.id>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
@mail($email_tujuan, $email_subjek, $email_isi, $headers);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Berhasil</title>";
echo "<style>body{font-family:Segoe UI,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0f4f8}.box{background:#fff;padding:40px;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.1);max-width:450px;width:100%;text-align:center}.sukses{color:#16a34a}.btn{display:inline-block;padding:12px 24px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;margin:8px}.btn:hover{background:#1d4ed8}</style></head><body>";
echo "<div class='box'><h2 class='sukses'>✅ Berhasil Terkirim!</h2>";
echo "<p>Terima kasih <strong>$nama</strong> dari kelas <strong>$kelas</strong>.</p>";
echo "<p>Total pembayaran: <strong>Rp $total_rupiah</strong></p>";
if ($metode !== 'Tunai') echo "<p>📎 Bukti pembayaran berhasil diunggah.</p>";
echo "<br><a href='$url_wa' target='_blank' class='btn'>💬 Buka WhatsApp Sekolah</a>";
echo "<br><a href='index.html' class='btn'>📝 Isi Formulir Baru</a>";
echo "<br><a href='daftar_bayar.php' class='btn' style='background:#10b981'>📋 Lihat Daftar Pembayaran</a>";
echo "</div></body></html>";
exit;
