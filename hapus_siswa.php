<?php
// hapus_siswa.php
// Hapus siswa, lalu tampilkan notifikasi di halaman daftar.
session_start();
include "config/koneksi.php";
include_once "includes/helper.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== "admin") {
    echo "Akses ditolak!";
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

try {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM t_siswa WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        flash_sukses('Data siswa berhasil dihapus.');
    } else {
        flash_error('Data siswa tidak ditemukan.');
    }
} catch (Throwable $e) {
    // Biasanya karena data masih dipakai di tabel lain (foreign key)
    flash_error('Data siswa gagal dihapus karena masih digunakan data lain.');
}

header("Location: kelola_siswa.php");
exit;
