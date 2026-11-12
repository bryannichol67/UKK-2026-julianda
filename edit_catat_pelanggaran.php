<?php
// edit_catat_pelanggaran.php
include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/proses_pelanggaran.php';
cek_role(['guru', 'admin']);

$id = (int) ($_GET['id'] ?? 0);

// Ambil data lama
$row = cari_satu($koneksi, "SELECT * FROM t_pelanggaran_siswa WHERE id = ?", $id);

if (!$row) {
    $_SESSION['pesan_error'] = 'Catatan pelanggaran tidak ditemukan.';
    header('Location: catat_pelanggaran.php');
    exit;
}


$v = [
    'siswa_id'       => $row['siswa_id'],
    'pelanggaran_id' => $row['pelanggaran_id'],
    'tanggal'        => $row['tanggal'],
    'keterangan'     => $row['keterangan'],
    'guru_id'        => $row['guru_id'],
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['siswa_id']       = (int) ($_POST['siswa_id'] ?? 0);
    $v['pelanggaran_id'] = (int) ($_POST['pelanggaran_id'] ?? 0);
    $v['tanggal']        = $_POST['tanggal'] ?? '';
    $v['keterangan']     = trim($_POST['keterangan'] ?? '');
    if ($admin_pilih_guru) {
        $v['guru_id'] = (int) ($_POST['guru_id'] ?? 0);
    }

    $tahun_id = (int) $row['tahun_ajaran_id'];
    $kelas_id = (int) $row['kelas_id'];
    $poin     = (int) $row['poin'];
    $ok       = true;

    // Siswa diganti -> kelas & tahun ajaran dari penempatan aktif siswa baru
    if ($v['siswa_id'] !== (int) $row['siswa_id']) {
        $tempat = cari_satu($koneksi,
            "SELECT kelas_id, tahun_ajaran_id FROM t_kelas_siswa
             WHERE siswa_id = ? AND status_aktif = 1 ORDER BY id DESC LIMIT 1",
            $v['siswa_id']);
        if (!$tempat) {
            $error = 'Siswa ini belum ditempatkan di kelas, jadi belum bisa dicatat.';
            $ok = false;
        } else {
            $tahun_id = (int) $tempat['tahun_ajaran_id'];
            $kelas_id = (int) $tempat['kelas_id'];
        }
    }

    // Pelanggaran diganti -> poin mengikuti jenis pelanggaran baru
    if ($ok) {
        $pel = cari_satu($koneksi, "SELECT poin FROM t_pelanggaran WHERE id = ?", $v['pelanggaran_id']);
        if (!$pel) {
            $error = 'Jenis pelanggaran tidak valid.';
            $ok = false;
        } elseif ($v['pelanggaran_id'] !== (int) $row['pelanggaran_id']) {
            $poin = (int) $pel['poin'];
        }
    }

    if ($ok) {
        $data = [
            'siswa_id'        => $v['siswa_id'],
            'tahun_ajaran_id' => $tahun_id,
            'kelas_id'        => $kelas_id,
            'pelanggaran_id'  => $v['pelanggaran_id'],
            'guru_id'         => (int) $v['guru_id'],
            'tanggal'         => $v['tanggal'],
            'poin'            => $poin,
            'status'          => $row['status'],
            'keterangan'      => $v['keterangan'],
            'tindakan'        => $row['tindakan'],
        ];
        $hasil = simpan_pelanggaran($koneksi, $data, $id);

        if ($hasil === true) {
            $_SESSION['pesan_sukses'] = 'Catatan pelanggaran berhasil diperbarui.';
            header('Location: catat_pelanggaran.php');
            exit;
        }
        $error = $hasil;
    }
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = data_dari_post();
    $hasil = simpan_pelanggaran($koneksi, $v, $id);

    if ($hasil === true) {
        $_SESSION['pesan_sukses'] = 'Catatan pelanggaran berhasil diperbarui.';
        header('Location: catat_pelanggaran.php');
        exit;
    }
    $error = $hasil;
}


// Pilihan dropdown (semua data, supaya pilihan lama tetap muncul)
$siswa_lama = (int) $row['siswa_id'];
$pel_lama   = (int) $row['pelanggaran_id'];
$siswa = mysqli_query($koneksi,
    "SELECT s.id, s.nis, s.nama, COALESCE(k.nama, '-') AS kelas
     FROM t_siswa s
     LEFT JOIN t_kelas_siswa ks ON ks.siswa_id = s.id AND ks.status_aktif = 1
     LEFT JOIN t_kelas k ON k.id = ks.kelas_id
     WHERE s.id = $siswa_lama OR (s.status_aktif = 1 AND ks.id IS NOT NULL)
     GROUP BY s.id
     ORDER BY s.nama ASC");
$pelanggaran = mysqli_query($koneksi,
    "SELECT id, nama, poin FROM t_pelanggaran
     WHERE status_aktif = 1 OR id = $pel_lama ORDER BY nama ASC");
$guru = $admin_pilih_guru ? mysqli_query($koneksi, "SELECT id, nama FROM t_guru WHERE status_aktif = 1 OR id = " . (int) $row['guru_id'] . " ORDER BY nama ASC") : null;

$judul      = 'Edit Catatan Pelanggaran Siswa';
$menu_aktif = 'catat_pelanggaran.php';
$deskripsi  = 'Ubah siswa, pelanggaran, tanggal, atau catatan.';
include 'layout/tambah_atas.php';


?>
<?php if ($error) { ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
<?php } ?>


<form method="POST">

    <div class="mb-3">
        <label class="form-label">Siswa</label>
        <select name="siswa_id" class="form-select" required>
            <option value="">-- Pilih Siswa --</option>
            <?php while ($s = mysqli_fetch_assoc($siswa)) { ?>
                <option value="<?= (int) $s['id']; ?>" <?= ($v['siswa_id'] == $s['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($s['nama'] . ' (' . $s['kelas'] . ')'); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Pelanggaran</label>
        <select name="pelanggaran_id" class="form-select" required>
            <option value="">-- Pilih Pelanggaran --</option>
            <?php while ($p = mysqli_fetch_assoc($pelanggaran)) { ?>
                <option value="<?= (int) $p['id']; ?>" <?= ($v['pelanggaran_id'] == $p['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($p['nama'] . ' (' . $p['poin'] . ' poin)'); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <?php if ($guru) { ?>
        <div class="mb-3">
            <label class="form-label">Guru Pelapor</label>
            <select name="guru_id" class="form-select" required>
                <option value="">-- Pilih Guru --</option>
                <?php while ($g = mysqli_fetch_assoc($guru)) { ?>
                    <option value="<?= (int) $g['id']; ?>" <?= ($v['guru_id'] == $g['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($g['nama']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    <?php } ?>

    <div class="mb-3">
        <label class="form-label">Tanggal</label>
        <input type="date" name="tanggal" class="form-control"
               value="<?= htmlspecialchars($v['tanggal']); ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Catatan <span class="text-muted">(boleh dikosongkan)</span></label>
        <textarea name="keterangan" class="form-control" rows="3"
                  placeholder="Contoh: terlambat 15 menit"><?= htmlspecialchars($v['keterangan']); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="catat_pelanggaran.php" class="btn btn-secondary">Batal</a>
</form>

<!-- Dropdown bisa dicari (Tom Select) -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.querySelectorAll('form select').forEach(function (el) {
        new TomSelect(el, { allowEmptyOption: true, maxOptions: null });
    });
</script>



<?php include 'layout/tambah_bawah.php'; ?>
