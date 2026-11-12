<?php
// hapus_catat_pelanggaran.php
include 'includes/cek_session.php';
include 'config/koneksi.php';
cek_role(['guru', 'admin']);

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "UPDATE t_pelanggaran_siswa SET status = 'dihapus', updated_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['pesan_sukses'] = 'Catatan pelanggaran dihapus dari daftar (tetap tersimpan di Riwayat).';
} else {
    $_SESSION['pesan_error'] = 'Catatan pelanggaran gagal dihapus.';
}

header('Location: catat_pelanggaran.php');
exit;
