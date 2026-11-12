<?php
// export_laporan.php
// Export Excel laporan pelanggaran kelas yang diwalikan (mengikuti filter tanggal).
session_start();
include "config/koneksi.php";
include_once "includes/dokumen.php";

$wali = wajib_wali_kelas($koneksi);

$dari   = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
if (!tanggal_valid_ymd($dari))   { $dari = ''; }
if (!tanggal_valid_ymd($sampai)) { $sampai = ''; }
if ($dari !== '' && $sampai !== '' && $dari > $sampai) { list($dari, $sampai) = [$sampai, $dari]; }

$kondisi = ["ps.kelas_id = ?"];
$tipe = 'i';
$param = [(int) $wali['kelas_id']];
if ($dari !== '')   { $kondisi[] = "ps.tanggal >= ?"; $tipe .= 's'; $param[] = $dari; }
if ($sampai !== '') { $kondisi[] = "ps.tanggal <= ?"; $tipe .= 's'; $param[] = $sampai; }

$data = ambil_semua($koneksi,
    "SELECT ps.tanggal, s.nis, ps.nama_siswa, ps.nama_pelanggaran, ps.poin, ps.keterangan, ps.tindakan, ps.status
     FROM t_pelanggaran_siswa ps
     LEFT JOIN t_siswa s ON s.id = ps.siswa_id
     WHERE " . implode(' AND ', $kondisi) . "
     ORDER BY ps.tanggal ASC, ps.id ASC",
    $tipe, ...$param);

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=laporan_pelanggaran_" . preg_replace('/\W+/', '_', $wali['nama_kelas']) . ".xls");
?>
<meta charset="UTF-8">
<table border="1">
<tr><th colspan="9">LAPORAN PELANGGARAN SISWA - KELAS <?= h($wali['nama_kelas']) ?> - TA <?= h($wali['tahun_ajaran']) ?></th></tr>
<tr>
    <th>No</th><th>Tanggal</th><th>NIS</th><th>Nama Siswa</th><th>Jenis Pelanggaran</th>
    <th>Poin</th><th>Keterangan</th><th>Tindakan</th><th>Status</th>
</tr>
<?php foreach ($data as $i => $r) { ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= h(tanggal_indonesia($r['tanggal'])) ?></td>
    <td style="mso-number-format:'\@'"><?= h($r['nis']) ?></td>
    <td><?= h($r['nama_siswa']) ?></td>
    <td><?= h($r['nama_pelanggaran']) ?></td>
    <td><?= (int) $r['poin'] ?></td>
    <td><?= h($r['keterangan']) ?></td>
    <td><?= h($r['tindakan']) ?></td>
    <td><?= h(label_status($r['status'])) ?></td>
</tr>
<?php } ?>
</table>
