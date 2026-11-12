<?php
session_start();
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

include "config/koneksi.php";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_pelanggaran.xls");

// Filter tanggal (sama dengan halaman laporan.php)
function tanggal_laporan_valid($t)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $t);
    return $d && $d->format('Y-m-d') === $t;
}
$dari   = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
if (!tanggal_laporan_valid($dari))   { $dari = ''; }
if (!tanggal_laporan_valid($sampai)) { $sampai = ''; }
if ($dari !== '' && $sampai !== '' && $dari > $sampai) {
    list($dari, $sampai) = [$sampai, $dari];
}
$kondisi = [];
if ($dari !== '')   { $kondisi[] = "ps.tanggal >= '$dari'"; }
if ($sampai !== '') { $kondisi[] = "ps.tanggal <= '$sampai'"; }
$filter = $kondisi ? 'WHERE ' . implode(' AND ', $kondisi) : '';

$data = mysqli_query($koneksi, "
    SELECT
        s.nis,
        s.nama,
        pk.nama AS pelanggaran
    FROM t_pelanggaran_siswa ps
    JOIN t_siswa s ON ps.siswa_id = s.id
    JOIN t_pelanggaran pk ON ps.pelanggaran_id = pk.id
    $filter
    ORDER BY ps.id DESC
");

?>

<table border="1">

<tr>
    <th>No</th>
    <th>NIS</th>
    <th>Nama Siswa</th>
    <th>Jenis Pelanggaran</th>
</tr>

<?php

$no = 1;

while ($row = mysqli_fetch_assoc($data)) {

?>

<tr>
    <td><?php echo $no; ?></td>
    <td><?php echo $row['nis']; ?></td>
    <td><?php echo $row['nama']; ?></td>
    <td><?php echo $row['pelanggaran']; ?></td>
</tr>

<?php

$no++;

}

?>

</table>