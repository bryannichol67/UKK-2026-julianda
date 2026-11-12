<?php

$host = "sql308.infinityfree.com";
$user = "if0_43390720";
$password = "7vOD991aDAK96";
$database = "if0_43390720_db_julianda";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");

?>
