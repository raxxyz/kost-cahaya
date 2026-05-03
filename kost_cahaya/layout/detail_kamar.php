<?php
include "service/database.php";

$query = "SELECT * FROM kamar";
$result = mysqli_query($db, $query);
?>
<html>
<html lang="en">  
<table class="table table-striped mt-4">
  <thead>
    <tr>
      <th scope="id_kamar">#</th>
      <th scope="nama_kamar">Nama Kamar</th>
      <th scope="harga">Harga</th>
      <th scope="fasilitas">Fasilitas</th>
      <th scope='deskripsi'>Deskripsi</th>
      <th scope="col">Info</th>
    </tr>
  </thead>
  <tbody>
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<tr>
    <th scope="row"><?= $row['id_kamar']; ?></th>
    <td><?= $row['nama_kamar']; ?></td>
    <td>Rp <?= number_format($row['harga']); ?>/bulan</td>
    <td><?= $row['fasilitas']; ?></td>
    <td><?= $row['deskripsi']; ?></td>
    <td>
        <a href="kamar.php?id=<?= $row['id_kamar']; ?>" class="badge text-bg-info">
            Info
        </a>
    </td>
</tr>
<?php } ?>
</tbody>

</div>