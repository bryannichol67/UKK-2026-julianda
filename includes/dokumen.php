<?php
// includes/dokumen.php
// Pembantu untuk fitur Laporan & dokumen cetak berstandar dokumen sekolah
// (kop surat, judul, isi, kolom tanda tangan), serta pembatasan akses wali kelas.

require_once __DIR__ . '/helper.php';

// ------------------------------------------------------------------
// Akses: hanya guru yang sedang menjadi wali kelas (tahun ajaran aktif)
// Mengembalikan ['guru_id','nama_guru','nip','kelas_id','nama_kelas','tahun_ajaran'].
// Selain itu -> 403 dan berhenti.
// ------------------------------------------------------------------
function wajib_wali_kelas($koneksi)
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $wali = null;
    if (($_SESSION['role'] ?? '') === 'guru') {
        // Dicek ulang ke database agar perubahan penugasan wali kelas langsung berlaku
        $wali = ambil_satu($koneksi,
            "SELECT g.id AS guru_id, g.nama AS nama_guru, g.nip,
                    k.id AS kelas_id, k.nama AS nama_kelas, ta.nama AS tahun_ajaran
             FROM t_guru g
             JOIN t_wali_kelas wk ON wk.guru_id = g.id AND wk.status_aktif = 1
             JOIN t_tahun_ajaran ta ON ta.id = wk.tahun_ajaran_id AND ta.status_aktif = 1
             JOIN t_kelas k ON k.id = wk.kelas_id
             WHERE g.user_id = ?
             LIMIT 1",
            'i', (int) $_SESSION['user_id']);
    }

    if (!$wali) {
        $_SESSION['is_wali_kelas'] = false;
        http_response_code(403);
        echo "Akses ditolak. Menu Laporan hanya untuk guru yang menjadi wali kelas. "
           . "<a href='dashboard.php'>Kembali ke Dashboard</a>";
        exit;
    }

    $_SESSION['is_wali_kelas']  = true;
    $_SESSION['guru_id']        = (int) $wali['guru_id'];
    $_SESSION['kelas_wali_id']  = (int) $wali['kelas_id'];
    return $wali;
}

// ------------------------------------------------------------------
// Tanggal & teks
// ------------------------------------------------------------------
function tanggal_indonesia($ymd, $kosong = '-')
{
    $t = $ymd ? strtotime($ymd) : false;
    if (!$t) { return $kosong; }
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
              'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return (int) date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function label_status($status)
{
    $s = strtolower(trim((string) $status));
    $peta = ['belum' => 'Belum Ditindak', 'belum ditindak' => 'Belum Ditindak',
             'proses' => 'Dalam Proses', 'selesai' => 'Selesai', 'dihapus' => 'Dihapus'];
    return $peta[$s] ?? ($status !== '' ? ucfirst($status) : '-');
}

function h($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function identitas_sekolah()
{
    static $cfg = null;
    if ($cfg === null) { $cfg = require __DIR__ . '/../config/sekolah.php'; }
    return $cfg;
}

// Query string filter tanggal valid (Y-m-d)
function tanggal_valid_ymd($t)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $t);
    return $d && $d->format('Y-m-d') === $t;
}

// ------------------------------------------------------------------
// Tampilan dokumen cetak (A4, Times New Roman 12 pt, hitam putih)
// ------------------------------------------------------------------
function css_dokumen($orientasi = 'portrait')
{
    $margin = $orientasi === 'landscape' ? '2cm 2cm 2cm 2.5cm' : '2cm 2cm 2cm 2.5cm';
    return <<<CSS
<style>
    @page { size: A4 $orientasi; margin: $margin; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #e5e7eb; }
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.4; color: #000; }

    .toolbar { position: sticky; top: 0; z-index: 10; background: #1f2937; color: #fff; padding: 10px 16px;
               display: flex; gap: 8px; align-items: center; font-family: system-ui, sans-serif; font-size: 14px; }
    .toolbar button, .toolbar a { background: #4f46e5; color: #fff; border: 0; border-radius: 6px; padding: 7px 14px;
               font-size: 14px; text-decoration: none; cursor: pointer; }
    .toolbar a.abu { background: #6b7280; }
    .toolbar .info { margin-left: auto; opacity: .8; }

    .kertas { background: #fff; margin: 16px auto; box-shadow: 0 2px 12px rgba(0,0,0,.25); padding: 2cm 2cm 2cm 2.5cm; }
    .kertas.portrait  { width: 21cm;   min-height: 29.7cm; }
    .kertas.landscape { width: 29.7cm; min-height: 21cm; }

    /* KOP SURAT */
    .kop { display: flex; align-items: center; gap: 14px; padding-bottom: 6px; border-bottom: 4px double #000; margin-bottom: 14px; }
    .kop img { width: 2.3cm; height: 2.3cm; object-fit: contain; }
    .kop .teks { flex: 1; text-align: center; line-height: 1.25; }
    .kop .yayasan { font-size: 11pt; font-weight: bold; }
    .kop .nama { font-size: 16pt; font-weight: bold; letter-spacing: .5px; }
    .kop .alamat { font-size: 10pt; }
    .kop.tanpa-logo { padding-left: 0; }

    /* JUDUL */
    .judul { text-align: center; margin: 4px 0 14px; }
    .judul h1 { font-size: 13pt; margin: 0; text-decoration: underline; text-transform: uppercase; }
    .judul .nomor { font-size: 11pt; margin-top: 2px; }

    p { margin: 0 0 8px; text-align: justify; }

    /* Tabel identitas (tanpa garis) */
    table.identitas { border-collapse: collapse; margin: 4px 0 10px; width: 100%; }
    table.identitas td { padding: 2px 4px; vertical-align: top; }
    table.identitas td:first-child { width: 4.6cm; white-space: nowrap; }
    table.identitas td:nth-child(2) { width: .5cm; }

    /* Tabel data (bergaris) */
    table.data { width: 100%; border-collapse: collapse; margin: 6px 0 12px; font-size: 11pt; }
    table.data th, table.data td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
    table.data th { background: #e5e5e5; text-align: center; font-weight: bold; }
    table.data td.c { text-align: center; }
    table.data thead { display: table-header-group; }
    table.data tr { page-break-inside: avoid; }

    /* Tanda tangan */
    table.ttd { width: 100%; margin-top: 18px; border-collapse: collapse; page-break-inside: avoid; }
    table.ttd td { width: 50%; text-align: center; vertical-align: top; padding: 0 8px; }
    table.ttd td.kiri { text-align: center; }
    table.ttd .ruang { height: 1.8cm; }
    table.ttd .nama { font-weight: bold; text-decoration: underline; }
    table.ttd .garis { display: inline-block; min-width: 5cm; border-bottom: 1px dotted #000; }

    .catatan { font-size: 9pt; margin-top: 8px; }

    @media print {
        html, body { background: #fff; }
        .toolbar { display: none !important; }
        .kertas { margin: 0; box-shadow: none; padding: 0; width: auto !important; min-height: 0 !important; }
    }
</style>
CSS;
}

function html_kop_surat()
{
    $s = identitas_sekolah();
    $logo = (!empty($s['logo']) && is_file(__DIR__ . '/../' . $s['logo'])) ? $s['logo'] : '';

    $o  = '<div class="kop' . ($logo ? '' : ' tanpa-logo') . '">';
    if ($logo) { $o .= '<img src="' . h($logo) . '" alt="Logo">'; }
    $o .= '<div class="teks">';
    if (!empty($s['yayasan'])) { $o .= '<div class="yayasan">' . h($s['yayasan']) . '</div>'; }
    $o .= '<div class="nama">' . h($s['nama']) . '</div>';
    if (!empty($s['alamat']))  { $o .= '<div class="alamat">' . h($s['alamat']) . '</div>'; }
    if (!empty($s['kontak']))  { $o .= '<div class="alamat">' . h($s['kontak']) . '</div>'; }
    $o .= '</div>';
    if ($logo) { $o .= '<div style="width:2.3cm"></div>'; }   // penyeimbang agar teks tetap di tengah
    $o .= '</div>';
    return $o;
}

// Blok tanda tangan. $kiri/$kanan: ['jabatan'=>..., 'nama'=>..., 'nip'=>...]
function html_tanda_tangan(array $kiri, array $kanan, $tanggal = null)
{
    $s = identitas_sekolah();
    $tanggal = $tanggal ?: date('Y-m-d');

    $sel = function (array $p, $atas) {
        $o  = '<div>' . $atas . '</div><div>' . h($p['jabatan']) . ',</div><div class="ruang"></div>';
        $o .= ($p['nama'] !== '' && $p['nama'] !== null)
            ? '<div class="nama">' . h($p['nama']) . '</div>'
            : '<div><span class="garis">&nbsp;</span></div>';
        if (!empty($p['nip'])) { $o .= '<div>' . h($p['nip']) . '</div>'; }
        return $o;
    };

    return '<table class="ttd"><tr>'
         . '<td class="kiri">' . $sel($kiri, 'Mengetahui,') . '</td>'
         . '<td>' . $sel($kanan, h($s['kota_ttd']) . ', ' . tanggal_indonesia($tanggal)) . '</td>'
         . '</tr></table>';
}

function html_toolbar($kembali = 'laporan.php', $info = '')
{
    return '<div class="toolbar">'
         . '<button type="button" onclick="window.print()">Cetak / Simpan PDF</button>'
         . '<a class="abu" href="' . h($kembali) . '">Kembali ke Laporan</a>'
         . ($info ? '<span class="info">' . h($info) . '</span>' : '')
         . '</div>';
}

function kepala_sekolah_ttd()
{
    $s = identitas_sekolah();
    return ['jabatan' => 'Kepala Sekolah', 'nama' => $s['kepala_sekolah'] ?? '', 'nip' => $s['nip_kepsek'] ?? ''];
}
