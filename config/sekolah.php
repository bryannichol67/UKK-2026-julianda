<?php
// config/sekolah.php
// Identitas sekolah untuk KOP SURAT pada dokumen cetak.
// Ubah nilai di bawah sesuai data sekolah. Isian yang dikosongkan tidak dicetak.
return [
    'yayasan'        => 'MAJELIS PENDIDIKAN DASAR DAN MENENGAH PIMPINAN DAERAH MUHAMMADIYAH',
    'nama'           => 'SMK MUHAMMADIYAH TASIKMALAYA',
    'alamat'         => '',   // contoh: 'Jl. Contoh No. 1, Kota Tasikmalaya, Jawa Barat 46xxx'
    'kontak'         => '',   // contoh: 'Telp. (0265) xxxxxx | Email: info@sekolah.sch.id'
    'kota_ttd'       => 'Tasikmalaya',   // kota pada baris tanggal tanda tangan
    'kepala_sekolah' => '',   // nama kepala sekolah (kosong = garis titik-titik)
    'nip_kepsek'     => '',   // NIP/NBM kepala sekolah (boleh kosong)
    'logo'           => 'assets/img/logo-sekolah.png',  // taruh file logo di sini (opsional)
];
