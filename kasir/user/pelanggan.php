<?php
session_start();
include "../config.php"; 

if (!isset($_SESSION['userid']) || $_SESSION['role'] != "pelanggan") {
    header("location: ../index.php?pesan=denied");
    exit;
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

if (isset($_POST['tambah_keranjang'])) {
    $id = $_POST['ProdukID'];
    $nama = $_POST['NamaProduk'];
    $harga = $_POST['Harga'];
    $qty_input = intval($_POST['qty_beli']);
    
    $cek_stok = mysqli_query($koneksi, "SELECT Stok, Status FROM produk WHERE ProdukID = '$id'");
    $ds = mysqli_fetch_assoc($cek_stok);
    
    if (!$ds || $ds['Status'] == 'Dihapus') {
        echo "<script>alert('Gagal! Produk sudah tidak tersedia.'); window.location='pelanggan.php';</script>";
        exit;
    }

    if ($qty_input > $ds['Stok']) {
        echo "<script>alert('Gagal! Jumlah melebihi stok.'); window.location='pelanggan.php';</script>";
        exit;
    }

    if (isset($_SESSION['keranjang'][$id])) {
        $total_baru = $_SESSION['keranjang'][$id]['qty'] + $qty_input;
        $_SESSION['keranjang'][$id]['qty'] = ($total_baru > $ds['Stok']) ? $ds['Stok'] : $total_baru;
    } else {
        $_SESSION['keranjang'][$id] = [
            'nama' => $nama,
            'harga' => $harga,
            'qty' => $qty_input
        ];
    }
    header("location: pelanggan.php?pesan=ditambah");
    exit;
}

if (isset($_GET['hapus_item'])) {
    $id = $_GET['hapus_item'];
    unset($_SESSION['keranjang'][$id]);
    header("location: pelanggan.php?open_cart=1");
    exit;
}

if (isset($_POST['proses_checkout'])) {
    $userid = $_SESSION['userid'];
    $tgl = date('Y-m-d');
    $total_bayar = $_POST['total_bayar'];

    $q_pel = mysqli_query($koneksi, "SELECT PelangganID FROM pelanggan WHERE UserID = '$userid'");
    $d_pel = mysqli_fetch_assoc($q_pel);
    $pel_id = $d_pel['PelangganID'];

    $insert_penjualan = mysqli_query($koneksi, "INSERT INTO penjualan (TanggalPenjualan, TotalHarga, PelangganID) VALUES ('$tgl', '$total_bayar', '$pel_id')");
    $penjualan_id = mysqli_insert_id($koneksi);

    if ($insert_penjualan) {
        foreach ($_SESSION['keranjang'] as $produk_id => $item) {
            $res = mysqli_query($koneksi, "SELECT ProdukID FROM produk WHERE ProdukID = '$produk_id'");
            if(mysqli_num_rows($res) > 0) {
                $qty = $item['qty'];
                $sub = $item['harga'] * $qty;
                mysqli_query($koneksi, "INSERT INTO detailpenjualan (PenjualanID, ProdukID, JumlahProduk, Subtotal) VALUES ('$penjualan_id', '$produk_id', '$qty', '$sub')");
                mysqli_query($koneksi, "UPDATE produk SET Stok = Stok - $qty WHERE ProdukID = '$produk_id'");
            }
        }
        unset($_SESSION['keranjang']);
        echo "<script>alert('Checkout Berhasil!'); window.location='pelanggan.php';</script>";
    }
}

$userid = $_SESSION['userid'];
$query_profil = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE UserID = '$userid'");
$data_p = mysqli_fetch_assoc($query_profil);
$nama_tampil = ($data_p) ? $data_p['NamaPelanggan'] : ($_SESSION['nama'] ?? 'User');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KasirPro | Pelanggan</title>
    <link rel="stylesheet" href="style1.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .product-card { background: white; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #e2e8f0; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .qty-input { width: 60px !important; text-align: center; padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        
        .stock-info { font-size: 0.8em; padding: 3px 10px; border-radius: 20px; display: inline-block; margin-bottom: 10px; }
        .stock-available { background: #e6fffa; color: #2c7a7b; }
        .stock-empty { background: #fff5f5; color: #c53030; }

        .cart-float {
            position: fixed; bottom: 20px; right: 20px;
            background: #6366f1; color: white !important;
            padding: 15px 25px; border-radius: 50px;
            font-weight: bold; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); z-index: 99;
            text-decoration: none;
        }

        #toast {
            position: fixed; bottom: 85px; right: 20px;
            background: #10b981; color: white;
            padding: 12px 20px; border-radius: 8px;
            display: none; z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        #area-struk { background: white; padding: 20px; border: 1px solid #eee; }
        .struk-header { text-align: center; border-bottom: 2px dashed #444; margin-bottom: 15px; padding-bottom: 10px; }
        
        @media print {
            body * { visibility: hidden; }
            #area-struk, #area-struk * { visibility: visible; }
            #area-struk { position: absolute; left: 0; top: 0; width: 100%; border: none; }
            .modal { display: block !important; position: static; background: white; }
            .modal-content { box-shadow: none !important; border: none !important; margin: 0; width: 100% !important; max-width: none !important; }
            .no-print { display: none !important; }
        }

        .btn-action { padding: 10px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; flex: 1; }
        .btn-print { background: #6366f1; }
        .btn-img { background: #10b981; }
        .btn-close { background: #94a3b8; }
    </style>
</head>
<body>

<div id="toast">✅ Barang dimasukkan ke keranjang</div>

<div class="navbar no-print">
    <h2>KasirPro <span>| Pelanggan</span></h2>
    <div>👤 <strong><?php echo htmlspecialchars($nama_tampil); ?></strong> | <a href="../logout.php" class="btn-logout">Logout</a></div>
</div>

<div class="main-content no-print">
    <div class="product-grid">
        <?php 
        $query_produk = mysqli_query($koneksi, "SELECT * FROM produk WHERE Status = 'Tersedia' ORDER BY NamaProduk ASC");
        while($p = mysqli_fetch_assoc($query_produk)){ 
            $is_out = ($p['Stok'] <= 0);
        ?>
            <div class="product-card">
                <form method="POST">
                    <div style="font-size: 2.5rem; margin-bottom:10px;">📦</div>
                    <h4 style="margin:0;"><?php echo $p['NamaProduk']; ?></h4>
                    <div class="stock-info <?php echo $is_out ? 'stock-empty' : 'stock-available'; ?>">
                        <?php echo $is_out ? 'Stok Habis' : 'Stok: '.$p['Stok']; ?>
                    </div>
                    <p style="color: #6366f1; font-weight: bold;">Rp <?php echo number_format($p['Harga'], 0, ',', '.'); ?></p>
                    
                    <input type="hidden" name="ProdukID" value="<?php echo $p['ProdukID']; ?>">
                    <input type="hidden" name="NamaProduk" value="<?php echo $p['NamaProduk']; ?>">
                    <input type="hidden" name="Harga" value="<?php echo $p['Harga']; ?>">
                    
                    <?php if (!$is_out): ?>
                        <div style="margin: 10px 0;">
                            <input type="number" name="qty_beli" class="qty-input" value="1" min="1" max="<?php echo $p['Stok']; ?>">
                        </div>
                        <button type="submit" name="tambah_keranjang" class="btn-primary" style="width:100%">+ Keranjang</button>
                    <?php else: ?>
                        <button disabled style="width:100%; opacity:0.5; cursor:not-allowed;" class="btn-primary">Habis</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php } ?>
    </div>
</div>

<a href="javascript:void(0)" class="cart-float no-print" onclick="openModal('cartModal')">
    🛒 Keranjang (<?php echo count($_SESSION['keranjang']); ?>)
</a>

<div id="cartModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div id="area-struk">
            <div class="struk-header">
                <h2 style="margin:0">KASIR PRO</h2>
                <p style="margin:0">Nota Belanja Digital</p>
                <small><?php echo date('d/m/Y H:i'); ?></small>
            </div>
            
            <table width="100%" style="border-collapse: collapse; font-size: 0.9em;">
                <thead>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <th align="left" style="padding: 5px 0;">Item</th>
                        <th align="center">Qty</th>
                        <th align="right">Subtotal</th>
                        <th class="no-print"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($_SESSION['keranjang'] as $id => $item) {
                        $sub = $item['harga'] * $item['qty'];
                        $total += $sub;
                    ?>
                    <tr>
                        <td style="padding: 5px 0;"><?php echo $item['nama']; ?></td>
                        <td align="center"><?php echo $item['qty']; ?></td>
                        <td align="right">Rp<?php echo number_format($sub, 0, ',', '.'); ?></td>
                        <td align="right" class="no-print">
                            <a href="?hapus_item=<?php echo $id; ?>" style="color:red; margin-left:10px; text-decoration:none; font-weight:bold;">&times;</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <div style="border-top: 2px dashed #444; margin-top: 15px; padding-top: 10px; text-align: right;">
                <h3 style="margin:0">Total: Rp <?php echo number_format($total, 0, ',', '.'); ?></h3>
            </div>
        </div>

        <div class="no-print" style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 5px;">
                <button type="button" onclick="window.print()" class="btn-action btn-print">🖨️ Cetak</button>
                <button type="button" onclick="saveImg()" class="btn-action btn-img">🖼️ Gambar</button>
            </div>
            <?php if ($total > 0): ?>
                <form method="POST">
                    <input type="hidden" name="total_bayar" value="<?php echo $total; ?>">
                    <button type="submit" name="proses_checkout" class="btn-primary" style="width: 100%;">✔️ Checkout</button>
                </form>
            <?php endif; ?>
            <button onclick="closeModal('cartModal')" class="btn-action btn-close">Tutup</button>
        </div>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = "flex"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }

    function saveImg() {
        const area = document.getElementById('area-struk');
        html2canvas(area, { scale: 3 }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Struk-KasirPro.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('open_cart')) openModal('cartModal');
        if (urlParams.get('pesan') === 'ditambah') {
            const t = document.getElementById('toast');
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 2500);
        }
    }
</script>
</body>
</html>