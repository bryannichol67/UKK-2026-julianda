<?php
// layout/form_guru.php
// Form dipakai bersama oleh tambah_guru.php dan edit_guru.php.
// Variabel: $v (nilai form), $galat (error per kolom), $label_tombol
$galat = $galat ?? [];
$label_tombol = $label_tombol ?? 'Simpan';
$tampil_password = $tampil_password ?? false;   // true hanya di halaman tambah guru
?>
<?= html_alert_galat($galat); ?>

<form method="POST" id="formGuru">

    <div class="mb-3">
        <label for="nip" class="form-label">NIP</label>
        <input type="text" name="nip" id="nip" class="form-control<?= kelas_invalid($galat, 'nip'); ?>"
               value="<?= htmlspecialchars((string) $v['nip']); ?>"
               maxlength="<?= PANJANG_NIP; ?>" inputmode="numeric" pattern="[0-9]*"
               placeholder="Maksimal <?= PANJANG_NIP; ?> digit angka" required>
        <?= pesan_invalid($galat, 'nip'); ?>
    </div>
    <div class="mb-3">
        <label for="nama" class="form-label">Nama</label>
        <input type="text" name="nama" id="nama" class="form-control<?= kelas_invalid($galat, 'nama'); ?>"
               value="<?= htmlspecialchars((string) $v['nama']); ?>" required>
        <?= pesan_invalid($galat, 'nama'); ?>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control<?= kelas_invalid($galat, 'email'); ?>"
               value="<?= htmlspecialchars((string) $v['email']); ?>" required>
        <?= pesan_invalid($galat, 'email'); ?>
    </div>
    <?php if ($tampil_password) { ?>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control<?= kelas_invalid($galat, 'password'); ?>"
               minlength="<?= PASSWORD_MIN; ?>" maxlength="<?= PASSWORD_MAKS; ?>"
               autocomplete="new-password" placeholder="Minimal <?= PASSWORD_MIN; ?> karakter" required>
        <?= pesan_invalid($galat, 'password'); ?>
    </div>
    <div class="mb-3">
        <label for="password_konfirmasi" class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_konfirmasi" id="password_konfirmasi"
               class="form-control<?= kelas_invalid($galat, 'password_konfirmasi'); ?>"
               maxlength="<?= PASSWORD_MAKS; ?>" autocomplete="new-password"
               placeholder="Ketik ulang password yang sama" required>
        <?= pesan_invalid($galat, 'password_konfirmasi'); ?>
    </div>
    <?php } ?>
    <div class="mb-3">
        <label for="status_aktif" class="form-label">Status</label>
        <?= html_status_select($v['status_aktif']); ?>
    </div>

    <div class="td-actions">
        <button type="submit" name="simpan" class="btn btn-primary"><?= htmlspecialchars($label_tombol); ?></button>
        <a href="kelola_guru.php" class="btn btn-secondary">Batal</a>
    </div>

</form>

<script>
(function () {
    var pesan = { nip: 'NIP wajib diisi.', nama: 'Nama wajib diisi.', email: 'Email wajib diisi dengan format yang benar.' };
    Object.keys(pesan).forEach(function (id) {
        var el = document.getElementById(id);
        el.addEventListener('invalid', function () {
            if (el.validity.valueMissing || el.validity.typeMismatch) { el.setCustomValidity(pesan[id]); }
            else if (el.validity.patternMismatch) { el.setCustomValidity('NIP hanya boleh berisi angka.'); }
        });
        el.addEventListener('input', function () { el.setCustomValidity(''); });
    });

    // Password: harus sama dengan konfirmasinya
    var pw  = document.getElementById('password');
    var pw2 = document.getElementById('password_konfirmasi');
    if (pw && pw2) {
        var cocok = function () {
            pw2.setCustomValidity(pw2.value !== '' && pw.value !== pw2.value
                ? 'Konfirmasi password tidak sama dengan password.' : '');
        };
        pw.addEventListener('input', cocok);
        pw2.addEventListener('input', cocok);
        pw.addEventListener('invalid', function () {
            if (pw.validity.valueMissing) { pw.setCustomValidity('Password wajib diisi.'); }
            else if (pw.validity.tooShort) { pw.setCustomValidity('Password minimal <?= PASSWORD_MIN; ?> karakter.'); }
        });
        pw.addEventListener('input', function () { pw.setCustomValidity(''); });
        pw2.addEventListener('invalid', function () {
            if (pw2.validity.valueMissing) { pw2.setCustomValidity('Ulangi password untuk verifikasi.'); }
        });
    }

    // NIP: huruf dan simbol langsung dihapus saat diketik, hanya angka yang masuk
    var nip = document.getElementById('nip');
    nip.addEventListener('input', function () {
        var bersih = nip.value.replace(/\D/g, '');
        if (nip.value !== bersih) { nip.value = bersih; }
    });
})();
</script>
