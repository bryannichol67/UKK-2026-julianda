<?php
session_start();
include "config/koneksi.php";
include "includes/helper.php";

wajib_admin();

// Nilai awal form
$v = ['nip' => '', 'nama' => '', 'email' => '', 'status_aktif' => 1];
$galat = [];

// Proses simpan
if (isset($_POST['simpan'])) {

    $v = data_guru_dari_post($_POST);
    $password   = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['password_konfirmasi'] ?? '');

    $galat = validasi_guru($koneksi, $v);

    // Password harus diisi dua kali dan sama
    $galat = array_merge($galat, validasi_password_baru($password, $konfirmasi));

    // Email dipakai untuk login, jadi tidak boleh sama dengan akun lain
    if (!isset($galat['email']) && email_user_dipakai($koneksi, $v['email'])) {
        $galat['email'] = 'Email ini sudah dipakai akun lain.';
    }

    if (empty($galat)) {
        mysqli_begin_transaction($koneksi);
        try {
            // 1) Akun login (password disimpan dalam bentuk hash)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO t_users
                (name, email, password, remember_token, role, created_at, updated_at)
                VALUES (?, ?, ?, '', 'guru', NOW(), NOW())");
            mysqli_stmt_bind_param($stmt, 'sss', $v['nama'], $v['email'], $hash);
            if (!mysqli_stmt_execute($stmt)) { throw new Exception('Gagal menyimpan akun.'); }
            $user_id = mysqli_insert_id($koneksi);

            // 2) Data guru yang terhubung ke akun tersebut
            $stmt = mysqli_prepare($koneksi, "INSERT INTO t_guru
                (nip, nama, email, status_aktif, user_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            mysqli_stmt_bind_param($stmt, 'sssii',
                $v['nip'], $v['nama'], $v['email'], $v['status_aktif'], $user_id);
            if (!mysqli_stmt_execute($stmt)) { throw new Exception('Gagal menyimpan guru.'); }

            mysqli_commit($koneksi);

            flash_sukses('Data guru berhasil ditambahkan.');
            header("Location: kelola_guru.php");
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $galat['umum'] = 'Data guru gagal ditambahkan.';
        }
    }
}

$judul           = 'Tambah Guru';
$menu_aktif      = 'kelola_guru.php';
$label_tombol    = 'Simpan';
$tampil_password = true;    // tampilkan kolom password + konfirmasi
include 'layout/tambah_atas.php';

include 'layout/form_guru.php';

include 'layout/tambah_bawah.php';
