<?php 

$hostname = "localhost";
$nama = "root";
$password = "";
$database = "kost_cahaya";    

$db = mysqli_connect($hostname, $nama, $password, $database);

if($db->connect_error) {
    echo "Koneksi Gagal: ";
    die("error!");
}

?>

