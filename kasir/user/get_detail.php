<?php
include "../config.php";
$id = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT detailpenjualan.*, produk.NamaProduk 
                                FROM detailpenjualan 
                                JOIN produk ON detailpenjualan.ProdukID = produk.ProdukID 
                                WHERE PenjualanID = '$id'");

while($d = mysqli_fetch_assoc($query)){
    echo "<tr>
            <td>{$d['NamaProduk']}</td>
            <td align='center'>{$d['JumlahProduk']}</td>
            <td align='right'>Rp ".number_format($d['Subtotal'], 0, ',', '.')."</td>
          </tr>";
}
?>