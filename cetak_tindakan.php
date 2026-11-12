<?php
// cetak_tindakan.php?id=...
// Cetak dokumen tindakan untuk SATU baris pelanggaran (dipanggil dari menu Laporan).
session_start();
include "config/koneksi.php";
include_once "includes/dokumen.php";

$wali = wajib_wali_kelas($koneksi);
$id   = (int) ($_GET['id'] ?? 0);

// Hanya data kelas yang diwalikan
$d = ambil_satu($koneksi,
    "SELECT ps.*, s.nis, s.nisn, ta.nama AS tahun_ajaran
     FROM t_pelanggaran_siswa ps
     LEFT JOIN t_siswa s ON s.id = ps.siswa_id
     LEFT JOIN t_tahun_ajaran ta ON ta.id = ps.tahun_ajaran_id
     WHERE ps.id = ? AND ps.kelas_id = ?
     LIMIT 1",
    'ii', $id, (int) $wali['kelas_id']);

if (!$d) {
    http_response_code(404);
    echo "Data tidak ditemukan atau bukan data kelas yang Anda walikan. <a href='laporan.php'>Kembali</a>";
    exit;
}

$ada_tindakan = trim((string) $d['tindakan']) !== '';
$nomor = sprintf('%03d/PLG/%s/%s', (int) $d['id'], date('m', strtotime($d['tanggal'] ?: 'now')), date('Y', strtotime($d['tanggal'] ?: 'now')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara Tindakan - <?= h($d['nama_siswa']) ?></title>
    <?= css_dokumen('portrait') ?>
</head>
<body>
<?= html_toolbar('laporan.php', 'Dokumen tindakan #' . (int) $d['id']) ?>

<div class="kertas portrait">
    <?= html_kop_surat() ?>

    <div class="judul">
        <h1>Berita Acara Tindakan Pelanggaran Siswa</h1>
        <div class="nomor">Nomor: <?= h($nomor) ?></div>
    </div>

    <p>Yang bertanda tangan di bawah ini menerangkan bahwa telah terjadi pelanggaran tata tertib sekolah
       oleh siswa dengan identitas sebagai berikut:</p>

    <table class="identitas">
        <tr><td>Nama Siswa</td><td>:</td><td><?= h($d['nama_siswa']) ?></td></tr>
        <tr><td>NIS / NISN</td><td>:</td><td><?= h($d['nis'] ?: '-') ?> / <?= h($d['nisn'] ?: '-') ?></td></tr>
        <tr><td>Kelas</td><td>:</td><td><?= h($d['nama_kelas']) ?></td></tr>
        <tr><td>Tahun Ajaran</td><td>:</td><td><?= h($d['tahun_ajaran'] ?: $wali['tahun_ajaran']) ?></td></tr>
    </table>

    <p>Adapun rincian pelanggaran dan tindakan yang diberikan adalah sebagai berikut:</p>

    <table class="data">
        <tbody>
            <tr><th style="width:5cm;text-align:left">Hari/Tanggal Kejadian</th><td><?= h(tanggal_indonesia($d['tanggal'])) ?></td></tr>
            <tr><th style="text-align:left">Jenis Pelanggaran</th><td><?= h($d['nama_pelanggaran']) ?></td></tr>
            <tr><th style="text-align:left">Poin Pelanggaran</th><td><?= (int) $d['poin'] ?></td></tr>
            <tr><th style="text-align:left">Keterangan</th><td><?= nl2br(h(trim((string) $d['keterangan']) !== '' ? $d['keterangan'] : '-')) ?></td></tr>
            <tr><th style="text-align:left">Tindakan yang Diberikan</th><td><?= $ada_tindakan ? nl2br(h($d['tindakan'])) : '<i>Belum ada tindakan.</i>' ?></td></tr>
            <tr><th style="text-align:left">Status Penanganan</th><td><?= h(label_status($d['status'])) ?></td></tr>
            <tr><th style="text-align:left">Guru Pencatat/Penindak</th><td><?= h($d['nama_guru'] ?: '-') ?></td></tr>
        </tbody>
    </table>

    <p>Demikian berita acara ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>

    <table class="ttd">
        <tr>
            <td>Mengetahui,<br>Orang Tua/Wali Siswa,<div class="ruang"></div><span class="garis">&nbsp;</span></td>
            <td><?= h(identitas_sekolah()['kota_ttd']) ?>, <?= h(tanggal_indonesia(date('Y-m-d'))) ?><br>Wali Kelas <?= h($d['nama_kelas']) ?>,
                <div class="ruang"></div>
                <span class="nama"><?= h($wali['nama_guru']) ?></span><?= $wali['nip'] ? '<br>NIP/NIK. ' . h($wali['nip']) : '' ?></td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:10px">Mengetahui,<br>Kepala Sekolah,<div class="ruang"></div>
                <?php $ks = kepala_sekolah_ttd(); echo $ks['nama'] !== '' ? '<span class="nama">' . h($ks['nama']) . '</span>' . ($ks['nip'] ? '<br>' . h($ks['nip']) : '') : '<span class="garis">&nbsp;</span>'; ?></td>
        </tr>
    </table>

    <div class="catatan">Dicetak melalui Sistem Pelanggaran Siswa pada <?= h(tanggal_indonesia(date('Y-m-d'))) ?>, pukul <?= date('H.i') ?> WIB.</div>
</div>
</body>
</html>
