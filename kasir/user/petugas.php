<?php
session_start();
include "../config.php"; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != "petugas") {
    header("location: ../index.php?pesan=denied");
    exit;
}

$session_username = $_SESSION['username'] ?? $_SESSION['Username'] ?? '';

// --- LOGIKA PRODUK (DIROMBAK: Konsisten dengan Admin) ---

// 1. Hapus Produk (Soft Delete)
if (isset($_GET['hapus_produk'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_produk']);
    // Mengubah status menjadi 'Dihapus' agar data transaksi aman
    mysqli_query($koneksi, "UPDATE produk SET Status='Dihapus' WHERE ProdukID='$id'");
    header("location:petugas.php");
    exit;
}

// 2. Tambah/Edit Produk (Upsert Logic)
if (isset($_POST['proses_produk'])) {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['NamaProduk']);
    $harga = $_POST['Harga'];
    $stok  = $_POST['Stok'];
    $id    = $_POST['ProdukID'];

    if (empty($id)) {
        // Cek apakah produk dengan nama yang sama pernah ada (termasuk yang statusnya 'Dihapus')
        $cek_lama = mysqli_query($koneksi, "SELECT * FROM produk WHERE NamaProduk = '$nama'");
        if (mysqli_num_rows($cek_lama) > 0) {
            // Aktifkan kembali dan update stok/harga
            mysqli_query($koneksi, "UPDATE produk SET Harga='$harga', Stok='$stok', Status='Tersedia' WHERE NamaProduk='$nama'");
        } else {
            // Tambah baru murni
            mysqli_query($koneksi, "INSERT INTO produk (NamaProduk, Harga, Stok, Status) VALUES ('$nama', '$harga', '$stok', 'Tersedia')");
        }
    } else {
        // Update data produk yang sudah ada
        mysqli_query($koneksi, "UPDATE produk SET NamaProduk='$nama', Harga='$harga', Stok='$stok', Status='Tersedia' WHERE ProdukID='$id'");
    }
    header("location:petugas.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petugas Dashboard | KasirPro</title>
    <link rel="stylesheet" href="style1.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        #tabelDetail { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #tabelDetail th, #tabelDetail td { padding: 10px; border-bottom: 1px solid #edf2f7; font-size: 0.9em; }
        .deleted-user { color: #ef4444; font-style: italic; }
        #laporan-header { display: none; }
        
        @media print {
            .no-print, .aksi-kolom, .btn-action-laporan { display: none !important; }
            #laporan-header { display: block !important; text-align: center; margin-bottom: 20px; }
            .card { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body>

<div class="navbar no-print">
    <h2>KasirPro <span>| Staff Access</span></h2>
    <div>
        <span style="margin-right:15px">👷 <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Petugas'); ?></span>
        <a href="../logout.php" class="btn-logout" onclick="return confirm('Keluar?')">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="tab-menu no-print">
        <button class="tab-btn active" onclick="openTab(event, 'inventory')">📦 Inventaris</button>
        <button class="tab-btn" onclick="openTab(event, 'history')">📜 Riwayat Transaksi</button>
    </div>

    <div id="inventory" class="tab-content active">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3>Manajemen Stok Barang</h3>
                <button onclick="openModal('produkModal')" class="btn-primary">+ Tambah Barang</button>
            </div>
            <table>
                <thead>
                    <tr><th>No</th><th>Produk</th><th>Harga</th><th>Stok</th><th class="aksi-kolom">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE Status='Tersedia' ORDER BY NamaProduk ASC");
                    while($r = mysqli_fetch_assoc($data)){ ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $r['NamaProduk']; ?></strong></td>
                        <td>Rp <?php echo number_format($r['Harga'], 0,',','.'); ?></td>
                        <td><?php echo $r['Stok']; ?></td>
                        <td class="aksi-kolom">
                            <button onclick="editBarang(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="btn-warning-small">Edit</button>
                            <a href="?hapus_produk=<?php echo $r['ProdukID']; ?>" class="btn-danger-small" onclick="return confirm('Yakin menghapus produk? Data transaksi lama tetap aman.')">Hapus</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="history" class="tab-content">
        <div class="card" id="area-capture">
            <div id="laporan-header">
                <h2 style="margin:0;">LAPORAN TRANSAKSI PETUGAS</h2>
                <p style="margin:5px 0;">Oleh: <?php echo $_SESSION['nama']; ?></p>
                <small>Dicetak pada: <?php echo date('d/m/Y H:i'); ?></small>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;" class="btn-action-laporan">
                <h3>Riwayat Penjualan</h3>
                <div style="display:flex; gap:10px;">
                    <button onclick="window.print()" class="btn-primary" style="background:#6366f1;">🖨️ Cetak</button>
                    <button onclick="simpanGambar()" class="btn-primary" style="background:#10b981;">🖼️ Gambar</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;" class="aksi-kolom">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $q_hist = mysqli_query($koneksi, "SELECT penjualan.*, pelanggan.NamaPelanggan 
                                                      FROM penjualan 
                                                      LEFT JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID 
                                                      ORDER BY PenjualanID DESC");
                    while($h = mysqli_fetch_assoc($q_hist)){ 
                        $nama_user = $h['NamaPelanggan'] ?? "<span class='deleted-user'>Akun Terhapus</span>";
                        $nama_label = $h['NamaPelanggan'] ?? "Akun Terhapus";
                    ?>
                    <tr>
                        <td>#<?php echo $h['PenjualanID']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($h['TanggalPenjualan'])); ?></td>
                        <td><?php echo $nama_user; ?></td>
                        <td style="text-align:right;"><strong>Rp <?php echo number_format($h['TotalHarga'], 0, ',', '.'); ?></strong></td>
                        <td style="text-align:center;" class="aksi-kolom">
                            <button onclick="lihatDetail('<?php echo $h['PenjualanID']; ?>', '<?php echo $nama_label; ?>', '<?php echo number_format($h['TotalHarga'], 0, ',', '.'); ?>')" class="btn-warning-small">Detail</button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; display:flex; justify-content: space-between;">
            <h3 style="margin:0">Transaksi <span id="det_id"></span></h3>
            <button onclick="closeModal('detailModal')" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <p style="margin: 10px 0;">Pelanggan: <strong id="det_nama"></strong></p>
        <table id="tabelDetail">
            <thead>
                <tr><th>Produk</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Subtotal</th></tr>
            </thead>
            <tbody id="isiDetail"></tbody>
        </table>
        <div style="text-align: right; margin-top: 15px; border-top: 2px dashed #eee; padding-top: 10px;">
            <h4 style="margin:0">Total Bayar: Rp <span id="det_total"></span></h4>
        </div>
    </div>
</div>

<div id="produkModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Form Barang</h3>
        <form method="POST">
            <input type="hidden" name="ProdukID" id="edit_id">
            <div class="form-grid">
                <div class="full-width"><label>Nama Produk</label><input type="text" name="NamaProduk" id="edit_nama" required></div>
                <div><label>Harga</label><input type="number" name="Harga" id="edit_harga" required></div>
                <div><label>Stok</label><input type="number" name="Stok" id="edit_stok" required></div>
                <div class="full-width" style="display:flex; gap:10px;">
                    <button type="submit" name="proses_produk" class="btn-primary" style="flex:1;">Simpan</button>
                    <button type="button" onclick="closeModal('produkModal')" class="btn-logout" style="flex:1; background:#94a3b8; color:white !important;">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        let contents = document.getElementsByClassName("tab-content");
        let buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < contents.length; i++) contents[i].classList.remove("active");
        for (let i = 0; i < buttons.length; i++) buttons[i].classList.remove("active");
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    function openModal(id) { document.getElementById(id).style.display = "flex"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }

    function editBarang(data) {
        document.getElementById("modalTitle").innerText = "Update Barang";
        document.getElementById("edit_id").value = data.ProdukID;
        document.getElementById("edit_nama").value = data.NamaProduk;
        document.getElementById("edit_harga").value = data.Harga;
        document.getElementById("edit_stok").value = data.Stok;
        openModal('produkModal');
    }

    function lihatDetail(id, nama, total) {
        document.getElementById("det_id").innerText = "#" + id;
        document.getElementById("det_nama").innerText = nama;
        document.getElementById("det_total").innerText = total;
        fetch('get_detail.php?id=' + id)
            .then(res => res.text())
            .then(data => {
                document.getElementById("isiDetail").innerHTML = data;
                openModal('detailModal');
            });
    }

    function simpanGambar() {
        const area = document.getElementById('area-capture');
        const header = document.getElementById('laporan-header');
        const aksi = document.querySelectorAll('.aksi-kolom');
        const btnLaporan = document.querySelector('.btn-action-laporan');
        const semuaModal = document.querySelectorAll('.modal');

        header.style.display = 'block';
        btnLaporan.classList.add('force-hidden');
        semuaModal.forEach(m => m.classList.add('force-hidden'));
        aksi.forEach(el => el.classList.add('force-hidden'));

        html2canvas(area, { scale: 2, backgroundColor: "#ffffff" }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Laporan-Staff-' + Date.now() + '.png';
            link.href = canvas.toDataURL("image/png");
            link.click();
            
            header.style.display = 'none';
            btnLaporan.classList.remove('force-hidden');
            semuaModal.forEach(m => {
                m.classList.remove('force-hidden');
                m.style.display = 'none';
            });
            aksi.forEach(el => el.classList.remove('force-hidden'));
        });
    }

    window.onclick = function(e) { if (e.target.className === 'modal') e.target.style.display = "none"; }
</script>
</body>
</html>