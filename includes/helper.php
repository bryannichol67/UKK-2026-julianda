<?php
// includes/helper.php
// Fungsi bantu yang dipakai bersama oleh banyak halaman:
//   1. badge_status()        -> tampilan Aktif / Tidak Aktif yang SAMA di semua tabel
//   2. validasi_siswa()      -> aturan isian form siswa (tambah & edit)
//   3. validasi_guru()       -> aturan isian form guru (tambah & edit)
//   4. cek_identitas_unik()  -> NIS, NISN, dan NIP tidak boleh sama

// Batas tahun lahir siswa (tidak boleh lebih kecil dari tahun ini)
if (!defined('TAHUN_LAHIR_MIN')) {
    define('TAHUN_LAHIR_MIN', 2000);
}

// Panjang maksimal NIS dan NISN (digit angka)
if (!defined('PANJANG_NIS')) {
    define('PANJANG_NIS', 15);
}
if (!defined('PANJANG_NISN')) {
    define('PANJANG_NISN', 10);
}

// Panjang maksimal NIP guru (digit angka)
if (!defined('PANJANG_NIP')) {
    define('PANJANG_NIP', 18);
}

// Panjang password akun guru
if (!defined('PASSWORD_MIN')) {
    define('PASSWORD_MIN', 8);
}
if (!defined('PASSWORD_MAKS')) {
    define('PASSWORD_MAKS', 72);   // batas bcrypt
}

/* ------------------------------------------------------------------
   1. BADGE STATUS (satu tempat supaya warnanya selalu konsisten)
      Aktif = hijau, Tidak Aktif = abu-abu.
      Merah tidak dipakai karena merah dipakai sebagai penanda tingkat
      pelanggaran.
   ------------------------------------------------------------------ */
function badge_status($nilai)
{
    if ((string) $nilai === '1') {
        return '<span class="badge bg-success">Aktif</span>';
    }
    return '<span class="badge bg-secondary">Tidak Aktif</span>';
}

/* ------------------------------------------------------------------
   2. NIS / NISN / NIP TIDAK BOLEH SAMA
      - Tidak boleh sama satu sama lain di dalam satu isian
        (mis. NIS sama dengan NISN).
      - Tidak boleh sama dengan NIS, NISN, atau NIP milik data lain,
        baik di tabel siswa maupun tabel guru.

      $isian   : ['nis' => '..', 'nisn' => '..', 'nip' => '..']
                 (isi hanya yang ada di form; yang kosong dilewati)
      $tabel   : 't_siswa' atau 't_guru'  (data yang sedang diedit)
      $id      : id data yang sedang diedit (0 jika data baru)

      Hasil    : ['nis' => 'pesan', 'nisn' => 'pesan', ...]
                 kosong = tidak ada masalah
   ------------------------------------------------------------------ */
function cek_identitas_unik($koneksi, array $isian, $tabel = '', $id = 0)
{
    $label  = ['nis' => 'NIS', 'nisn' => 'NISN', 'nip' => 'NIP'];
    $galat  = [];
    $nilai  = [];

    foreach ($isian as $kolom => $v) {
        $v = trim((string) $v);
        if ($v !== '') { $nilai[$kolom] = $v; }
    }

    // a) Sama satu sama lain di dalam isian yang sedang dikirim
    $kunci = array_keys($nilai);
    for ($i = 0; $i < count($kunci); $i++) {
        for ($j = $i + 1; $j < count($kunci); $j++) {
            $a = $kunci[$i];
            $b = $kunci[$j];
            if ($nilai[$a] === $nilai[$b]) {
                $galat[$b] = $label[$b] . ' tidak boleh sama dengan ' . $label[$a] . '.';
            }
        }
    }

    // b) Sama dengan NIS / NISN / NIP milik data lain
    $sumber = [
        ['t_siswa', 'nis',  'NIS'],
        ['t_siswa', 'nisn', 'NISN'],
        ['t_guru',  'nip',  'NIP'],
    ];
    foreach ($nilai as $kolom => $v) {
        if (isset($galat[$kolom])) { continue; }
        foreach ($sumber as $s) {
            list($t, $k, $nama) = $s;
            $sql = "SELECT id FROM `$t` WHERE `$k` = ?";
            $tipe = 's';
            $param = [$v];
            if ($t === $tabel && (int) $id > 0) {
                $sql .= " AND id <> ?";
                $tipe .= 'i';
                $param[] = (int) $id;
            }
            $sql .= " LIMIT 1";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, $tipe, ...$param);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);
            if ($hasil && mysqli_fetch_assoc($hasil)) {
                $galat[$kolom] = ($nama === $label[$kolom])
                    ? $label[$kolom] . ' ' . $v . ' sudah terdaftar.'
                    : $label[$kolom] . ' ' . $v . ' sudah dipakai sebagai ' . $nama . ' pada data lain.';
                break;
            }
        }
    }

    return $galat;
}

/* ------------------------------------------------------------------
   3. SISWA
   ------------------------------------------------------------------ */
function data_siswa_dari_post(array $post)
{
    return [
        'nis'           => trim($post['nis'] ?? ''),
        'nisn'          => trim($post['nisn'] ?? ''),
        'nama'          => trim($post['nama'] ?? ''),
        'jenis_kelamin' => $post['jenis_kelamin'] ?? '',
        'tanggal_lahir' => trim($post['tanggal_lahir'] ?? ''),
        'alamat'        => trim($post['alamat'] ?? ''),
        'status_aktif'  => (string) ($post['status_aktif'] ?? '1') === '0' ? 0 : 1,
    ];
}

function validasi_siswa($koneksi, array $v, $id = 0)
{
    $galat = [];

    // NIS maksimal 15 digit, NISN maksimal 10 digit, keduanya hanya angka
    if ($v['nis'] === '') {
        $galat['nis'] = 'NIS wajib diisi.';
    } elseif (!ctype_digit($v['nis'])) {
        $galat['nis'] = 'NIS hanya boleh berisi angka.';
    } elseif (strlen($v['nis']) > PANJANG_NIS) {
        $galat['nis'] = 'NIS maksimal ' . PANJANG_NIS . ' digit.';
    }

    if ($v['nisn'] === '') {
        $galat['nisn'] = 'NISN wajib diisi.';
    } elseif (!ctype_digit($v['nisn'])) {
        $galat['nisn'] = 'NISN hanya boleh berisi angka.';
    } elseif (strlen($v['nisn']) > PANJANG_NISN) {
        $galat['nisn'] = 'NISN maksimal ' . PANJANG_NISN . ' digit.';
    }
    if ($v['nama'] === '') { $galat['nama'] = 'Nama wajib diisi.'; }
    if (!in_array($v['jenis_kelamin'], ['L', 'P'], true)) {
        $galat['jenis_kelamin'] = 'Pilih jenis kelamin.';
    }

    // Tanggal lahir: tahun minimal 2000 dan tidak boleh di masa depan
    $tgl = DateTime::createFromFormat('Y-m-d', $v['tanggal_lahir']);
    if ($v['tanggal_lahir'] === '') {
        $galat['tanggal_lahir'] = 'Tanggal lahir wajib diisi.';
    } elseif (!$tgl || $tgl->format('Y-m-d') !== $v['tanggal_lahir']) {
        $galat['tanggal_lahir'] = 'Format tanggal lahir tidak valid.';
    } elseif ((int) $tgl->format('Y') < TAHUN_LAHIR_MIN) {
        $galat['tanggal_lahir'] = 'Tahun lahir tidak boleh kurang dari ' . TAHUN_LAHIR_MIN . '.';
    } elseif ($v['tanggal_lahir'] > date('Y-m-d')) {
        $galat['tanggal_lahir'] = 'Tanggal lahir tidak boleh melebihi hari ini.';
    }

    // NIS / NISN tidak boleh sama dengan NIS / NISN / NIP lain
    $unik = cek_identitas_unik($koneksi, ['nis' => $v['nis'], 'nisn' => $v['nisn']], 't_siswa', $id);
    foreach ($unik as $kolom => $pesan) {
        if (!isset($galat[$kolom])) { $galat[$kolom] = $pesan; }
    }

    return $galat;
}

/* ------------------------------------------------------------------
   4. GURU
   ------------------------------------------------------------------ */
function data_guru_dari_post(array $post)
{
    return [
        'nip'          => trim($post['nip'] ?? ''),
        'nama'         => trim($post['nama'] ?? ''),
        'email'        => trim($post['email'] ?? ''),
        'status_aktif' => (string) ($post['status_aktif'] ?? '1') === '0' ? 0 : 1,
    ];
}

function validasi_guru($koneksi, array $v, $id = 0)
{
    $galat = [];

    if ($v['nip'] === '') {
        $galat['nip'] = 'NIP wajib diisi.';
    } elseif (!ctype_digit($v['nip'])) {
        $galat['nip'] = 'NIP hanya boleh berisi angka.';
    } elseif (strlen($v['nip']) > PANJANG_NIP) {
        $galat['nip'] = 'NIP maksimal ' . PANJANG_NIP . ' digit.';
    }
    if ($v['nama'] === '') { $galat['nama'] = 'Nama wajib diisi.'; }
    if ($v['email'] === '') {
        $galat['email'] = 'Email wajib diisi.';
    } elseif (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $galat['email'] = 'Format email tidak valid.';
    }

    // NIP tidak boleh sama dengan NIP / NIS / NISN lain
    $unik = cek_identitas_unik($koneksi, ['nip' => $v['nip']], 't_guru', $id);
    foreach ($unik as $kolom => $pesan) {
        if (!isset($galat[$kolom])) { $galat[$kolom] = $pesan; }
    }

    return $galat;
}

/* ------------------------------------------------------------------
   4b. AKUN LOGIN GURU: password diisi dua kali (password + konfirmasi)
       dan email tidak boleh dipakai akun lain di t_users
   ------------------------------------------------------------------ */
function validasi_password_baru($password, $konfirmasi)
{
    $galat = [];

    if ($password === '') {
        $galat['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < PASSWORD_MIN) {
        $galat['password'] = 'Password minimal ' . PASSWORD_MIN . ' karakter.';
    } elseif (strlen($password) > PASSWORD_MAKS) {
        $galat['password'] = 'Password maksimal ' . PASSWORD_MAKS . ' karakter.';
    }

    if ($konfirmasi === '') {
        $galat['password_konfirmasi'] = 'Ulangi password untuk verifikasi.';
    } elseif ($password !== $konfirmasi) {
        $galat['password_konfirmasi'] = 'Konfirmasi password tidak sama dengan password.';
    }

    return $galat;
}

// Apakah email sudah dipakai akun login lain? ($user_id = akun yang dikecualikan)
function email_user_dipakai($koneksi, $email, $user_id = 0)
{
    return (bool) ambil_satu($koneksi,
        "SELECT id FROM t_users WHERE email = ? AND id <> ? LIMIT 1",
        'si', $email, (int) $user_id);
}

/* ------------------------------------------------------------------
   Potongan HTML kecil untuk form: kelas "is-invalid" + pesan error
   ------------------------------------------------------------------ */
function kelas_invalid(array $galat, $kolom)
{
    return isset($galat[$kolom]) ? ' is-invalid' : '';
}

function pesan_invalid(array $galat, $kolom)
{
    if (!isset($galat[$kolom])) { return ''; }
    return '<div class="invalid-feedback">' . htmlspecialchars($galat[$kolom]) . '</div>';
}

/* ------------------------------------------------------------------
   Pembantu form / query
   ------------------------------------------------------------------ */

// Pilihan Aktif / Tidak Aktif di form (sama di semua halaman tambah & edit)
function html_status_select($nilai, $nama = 'status_aktif', $invalid = false)
{
    $n = ((string) $nilai === '0') ? '0' : '1';
    return '<select name="' . $nama . '" id="' . $nama . '" class="form-select' . ($invalid ? ' is-invalid' : '') . '">'
         . '<option value="1"' . ($n === '1' ? ' selected' : '') . '>Aktif</option>'
         . '<option value="0"' . ($n === '0' ? ' selected' : '') . '>Tidak Aktif</option>'
         . '</select>';
}

// Kotak pesan error di atas form
function html_alert_galat(array $galat)
{
    if (empty($galat)) { return ''; }
    $pesan = $galat['umum'] ?? 'Data belum bisa disimpan. Periksa kembali isian yang ditandai merah.';
    return '<div class="alert alert-danger">' . htmlspecialchars($pesan) . '</div>';
}

// Ambil banyak baris sekaligus (untuk isi dropdown)
function ambil_semua($koneksi, $sql, $tipe = '', ...$param)
{
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($tipe !== '') { mysqli_stmt_bind_param($stmt, $tipe, ...$param); }
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $baris = [];
    while ($hasil && ($r = mysqli_fetch_assoc($hasil))) { $baris[] = $r; }
    return $baris;
}

// Ambil satu baris
function ambil_satu($koneksi, $sql, $tipe = '', ...$param)
{
    $semua = ambil_semua($koneksi, $sql, $tipe, ...$param);
    return $semua[0] ?? null;
}

// ID guru (t_guru.id) dari akun yang sedang login. Hasil 0 jika bukan guru.
// Dipakai untuk membatasi data: Riwayat (guru penindak) dan Laporan guru biasa.
function guru_id_login($koneksi)
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (($_SESSION['role'] ?? '') !== 'guru') { return 0; }

    if (!empty($_SESSION['guru_id'])) { return (int) $_SESSION['guru_id']; }

    $row = ambil_satu($koneksi,
        "SELECT id FROM t_guru WHERE user_id = ? LIMIT 1",
        'i', (int) ($_SESSION['user_id'] ?? 0));
    $id = (int) ($row['id'] ?? 0);
    if ($id > 0) { $_SESSION['guru_id'] = $id; }   // simpan agar tidak query ulang
    return $id;
}

// Cek login + admin (dipakai di halaman tambah & edit)
function wajib_admin()
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if (($_SESSION['role'] ?? '') != "admin") {
        echo "Akses ditolak!";
        exit;
    }
}

/* ------------------------------------------------------------------
   5. KELAS: tingkat & jurusan dipilih lewat dropdown
   ------------------------------------------------------------------ */
function daftar_tingkat() { return ['X', 'XI', 'XII']; }
function daftar_jurusan() { return ['RPL', 'TKJ', 'TKR', 'TSM', 'BD']; }

// Isi <option> dropdown. Nilai lama yang tidak ada di daftar tetap ditampilkan
// supaya data lama tidak hilang saat diedit.
function html_opsi(array $daftar, $nilai)
{
    $nilai = (string) $nilai;
    if ($nilai !== '' && !in_array($nilai, $daftar, true)) { $daftar[] = $nilai; }

    $html = '';
    foreach ($daftar as $d) {
        $html .= '<option value="' . htmlspecialchars($d) . '"' . ($d === $nilai ? ' selected' : '') . '>'
               . htmlspecialchars($d) . '</option>';
    }
    return $html;
}

// $asli = data kelas sebelum diedit (nilai lamanya boleh tetap dipakai)
function validasi_kelas(array $v, array $asli = [])
{
    $galat = [];

    if ($v['nama'] === '') { $galat['nama'] = 'Nama kelas wajib diisi.'; }

    foreach (['tingkat' => 'Tingkat', 'jurusan' => 'Jurusan'] as $kolom => $label) {
        $daftar = ($kolom === 'tingkat') ? daftar_tingkat() : daftar_jurusan();
        if (!empty($asli[$kolom])) { $daftar[] = $asli[$kolom]; }

        if ($v[$kolom] === '') {
            $galat[$kolom] = $label . ' wajib dipilih.';
        } elseif (!in_array($v[$kolom], $daftar, true)) {
            $galat[$kolom] = $label . ' tidak valid, pilih dari daftar.';
        }
    }
    return $galat;
}

/* ------------------------------------------------------------------
   5b. VALIDASI TANGGAL (format Y-m-d dari input type="date")
       Hasil: '' jika valid, selain itu berisi pesan galat.
   ------------------------------------------------------------------ */
if (!function_exists('validasi_tanggal')) {
    function validasi_tanggal($tanggal, $label = 'Tanggal')
    {
        $d = DateTime::createFromFormat('Y-m-d', (string) $tanggal);

        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            return $label . ' tidak valid.';
        }
        if ((int) $d->format('Y') < TAHUN_LAHIR_MIN) {
            return $label . ' minimal tahun ' . TAHUN_LAHIR_MIN . '.';
        }
        return '';
    }
}

/* ------------------------------------------------------------------
   6. TAHUN AJARAN: hanya boleh ada satu yang aktif
   ------------------------------------------------------------------ */
// Tahun ajaran LAIN yang sedang aktif ($id = tahun yang sedang diedit, 0 jika baru)
function tahun_aktif_lain($koneksi, $id = 0)
{
    return ambil_satu($koneksi,
        "SELECT id, nama FROM t_tahun_ajaran WHERE status_aktif = 1 AND id <> ? ORDER BY id LIMIT 1",
        'i', (int) $id);
}

function pesan_tahun_aktif_ganda(array $aktif)
{
    return 'Sudah ada tahun ajaran aktif (' . $aktif['nama'] . '). '
         . 'Nonaktifkan dulu tahun ajaran tersebut, atau ubah status ini menjadi Tidak Aktif.';
}

// Apakah $id adalah tahun ajaran yang berstatus aktif?
function tahun_ajaran_aktif($koneksi, $id)
{
    return (bool) ambil_satu($koneksi,
        "SELECT id FROM t_tahun_ajaran WHERE id = ? AND status_aktif = 1", 'i', (int) $id);
}

/* ------------------------------------------------------------------
   7. WALI KELAS: tahun ajaran harus aktif, kelas dan guru belum
      punya / jadi wali kelas pada tahun ajaran tersebut
   ------------------------------------------------------------------ */
function validasi_wali_kelas_unik($koneksi, array $v, $id = 0)
{
    $galat = [];

    if (!tahun_ajaran_aktif($koneksi, $v['tahun_ajaran_id'])) {
        $galat['tahun_ajaran_id'] = 'Pilih tahun ajaran yang berstatus aktif.';
        return $galat;
    }

    // Data berstatus Tidak Aktif tidak menghalangi kelas / guru lain
    if ((int) $v['status_aktif'] !== 1) { return $galat; }

    $kelas_ada = ambil_satu($koneksi,
        "SELECT id FROM t_wali_kelas WHERE status_aktif = 1 AND tahun_ajaran_id = ? AND kelas_id = ? AND id <> ? LIMIT 1",
        'iii', (int) $v['tahun_ajaran_id'], (int) $v['kelas_id'], (int) $id);
    if ($kelas_ada) { $galat['kelas_id'] = 'Kelas ini sudah memiliki wali kelas.'; }

    $guru_ada = ambil_satu($koneksi,
        "SELECT id FROM t_wali_kelas WHERE status_aktif = 1 AND tahun_ajaran_id = ? AND guru_id = ? AND id <> ? LIMIT 1",
        'iii', (int) $v['tahun_ajaran_id'], (int) $v['guru_id'], (int) $id);
    if ($guru_ada) { $galat['guru_id'] = 'Guru ini sudah menjadi wali kelas.'; }

    return $galat;
}

/* ------------------------------------------------------------------
   8. PENEMPATAN SISWA: satu siswa hanya satu kelas per tahun ajaran
   ------------------------------------------------------------------ */
// $id = id penempatan yang sedang diedit (0 jika baru)
function siswa_sudah_ditempatkan($koneksi, $siswa_id, $tahun_id, $id = 0)
{
    return (bool) ambil_satu($koneksi,
        "SELECT id FROM t_kelas_siswa WHERE status_aktif = 1 AND siswa_id = ? AND tahun_ajaran_id = ? AND id <> ? LIMIT 1",
        'iii', (int) $siswa_id, (int) $tahun_id, (int) $id);
}

/* ------------------------------------------------------------------
   NOTIFIKASI (toast) setelah tambah / ubah / hapus data
   ------------------------------------------------------------------
   Pakai di halaman proses:
       flash_sukses('Data siswa berhasil ditambahkan.');
       flash_error('Data siswa gagal dihapus.');
       header('Location: kelola_siswa.php'); exit;
   Tampilannya otomatis muncul di pojok kanan atas lewat layout/sidebar.php
   (dibaca dari $_SESSION['pesan_sukses'] / ['pesan_error'], lalu dihapus).
   ------------------------------------------------------------------ */
function flash_sukses($pesan)
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['pesan_sukses'] = (string) $pesan;
}

function flash_error($pesan)
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['pesan_error'] = (string) $pesan;
}

// Cetak notifikasi yang menunggu (sekali tampil), lalu hapus dari sesi.
function tampil_notifikasi()
{
    if (session_status() === PHP_SESSION_NONE) { return; }

    $daftar = [];
    foreach (['pesan_sukses' => 'sukses', 'pesan_error' => 'error'] as $kunci => $tipe) {
        if (!empty($_SESSION[$kunci])) {
            $daftar[] = [$tipe, (string) $_SESSION[$kunci]];
        }
        unset($_SESSION[$kunci]);
    }
    if (empty($daftar)) { return; }

    echo '<style>
    #ntf-wadah{position:fixed;top:16px;right:16px;z-index:2000;display:flex;flex-direction:column;gap:10px;max-width:calc(100vw - 32px)}
    .ntf{display:flex;align-items:flex-start;gap:10px;min-width:280px;max-width:380px;padding:12px 14px;border-radius:12px;
         background:#fff;color:#1e293b;box-shadow:0 10px 30px rgba(15,23,42,.18);border-left:5px solid #16a34a;
         font:500 .95rem/1.4 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
         animation:ntf-masuk .25s ease-out;transition:opacity .3s,transform .3s}
    .ntf.ntf-error{border-left-color:#dc2626}
    .ntf-ikon{flex:none;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;
              color:#fff;font-size:13px;font-weight:700;background:#16a34a}
    .ntf-error .ntf-ikon{background:#dc2626}
    .ntf-teks{flex:1;word-break:break-word}
    .ntf-tutup{flex:none;border:0;background:none;color:#64748b;font-size:20px;line-height:1;cursor:pointer;padding:0 2px}
    .ntf-tutup:hover{color:#0f172a}
    .ntf.ntf-hilang{opacity:0;transform:translateX(20px)}
    @keyframes ntf-masuk{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:none}}
    </style><div id="ntf-wadah" role="status" aria-live="polite">';

    foreach ($daftar as [$tipe, $teks]) {
        $err = ($tipe === 'error');
        echo '<div class="ntf' . ($err ? ' ntf-error' : '') . '">'
           . '<span class="ntf-ikon">' . ($err ? '!' : '&#10003;') . '</span>'
           . '<span class="ntf-teks">' . htmlspecialchars($teks) . '</span>'
           . '<button type="button" class="ntf-tutup" aria-label="Tutup">&times;</button></div>';
    }

    echo '</div><script>
    (function () {
        document.querySelectorAll("#ntf-wadah .ntf").forEach(function (el) {
            function tutup() {
                el.classList.add("ntf-hilang");
                setTimeout(function () { el.remove(); }, 300);
            }
            el.querySelector(".ntf-tutup").addEventListener("click", tutup);
            setTimeout(tutup, 4500);
        });
    })();
    </script>';
}