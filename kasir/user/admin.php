<?php
session_start();
include "../config.php"; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("location: ../index.php?pesan=denied");
    exit;
}

$session_username = $_SESSION['username'] ?? $_SESSION['Username'] ?? '';

if (isset($_GET['hapus_produk'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_produk']);
    mysqli_query($koneksi, "UPDATE produk SET Status='Dihapus' WHERE ProdukID='$id'");
    header("location:admin.php");
    exit;
}

if (isset($_POST['proses_produk'])) {
    $nama  = mysqli_real_escape_string($koneksi, $_POST['NamaProduk']);
    $harga = $_POST['Harga'];
    $stok  = $_POST['Stok'];
    $id    = $_POST['ProdukID'];

    if (empty($id)) {
        $cek_lama = mysqli_query($koneksi, "SELECT * FROM produk WHERE NamaProduk = '$nama'");
        if (mysqli_num_rows($cek_lama) > 0) {
            mysqli_query($koneksi, "UPDATE produk SET Harga='$harga', Stok='$stok', Status='Tersedia' WHERE NamaProduk='$nama'");
        } else {
            mysqli_query($koneksi, "INSERT INTO produk (NamaProduk, Harga, Stok, Status) VALUES ('$nama', '$harga', '$stok', 'Tersedia')");
        }
    } else {
        mysqli_query($koneksi, "UPDATE produk SET NamaProduk='$nama', Harga='$harga', Stok='$stok', Status='Tersedia' WHERE ProdukID='$id'");
    }
    header("location:admin.php");
    exit;
}

if (isset($_GET['hapus_history'])) {
    $id_penjualan = mysqli_real_escape_string($koneksi, $_GET['hapus_history']);
    mysqli_query($koneksi, "DELETE FROM detailpenjualan WHERE PenjualanID='$id_penjualan'");
    mysqli_query($koneksi, "DELETE FROM penjualan WHERE PenjualanID='$id_penjualan'");
    header("location:admin.php");
    exit;
}

if (isset($_GET['hapus_user'])) {
    $id = mysqli_real_escape_string($koneksi, $_GET['hapus_user']);
    $q_info = mysqli_query($koneksi, "SELECT u.Username, p.PelangganID FROM user u LEFT JOIN pelanggan p ON u.UserID = p.UserID WHERE u.UserID='$id'");
    $data_check = mysqli_fetch_assoc($q_info);

    if ($data_check && $data_check['Username'] !== $session_username) {
        $p_id = $data_check['PelangganID'];
        if (!empty($p_id)) {
            mysqli_query($koneksi, "UPDATE penjualan SET PelangganID = NULL WHERE PelangganID = '$p_id'");
            mysqli_query($koneksi, "DELETE FROM pelanggan WHERE PelangganID = '$p_id'");
        }
        mysqli_query($koneksi, "DELETE FROM user WHERE UserID = '$id'");
    }
    header("location:admin.php");
    exit;
}

if (isset($_POST['update_user'])) {
    $id     = $_POST['UserID'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['Nama']);
    $user   = mysqli_real_escape_string($koneksi, $_POST['Username']);
    $role   = $_POST['Role'];
    $alamat = mysqli_real_escape_string($koneksi, $_POST['Alamat']);
    
    $sql = "UPDATE user SET Nama='$nama', Username='$user', Role='$role', Alamat='$alamat'";
    if (!empty($_POST['Password'])) {
        $pass = $_POST['Password'];
        $sql .= ", Password='$pass'";
    }
    $sql .= " WHERE UserID='$id'";
    mysqli_query($koneksi, $sql);
    header("location:admin.php");
    exit;
}

if (isset($_POST['simpan_registrasi'])) {
    $role   = $_POST['Role'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['Nama']);
    $user   = mysqli_real_escape_string($koneksi, $_POST['Username']);
    $pass   = $_POST['Password'];
    $alamat = mysqli_real_escape_string($koneksi, $_POST['Alamat']);

    $insertUser = mysqli_query($koneksi, "INSERT INTO user (Username, Password, Nama, Alamat, Role) VALUES ('$user', '$pass', '$nama', '$alamat', '$role')");

    if ($insertUser && $role == "pelanggan") {
        $last_id = mysqli_insert_id($koneksi);
        $telp = mysqli_real_escape_string($koneksi, $_POST['NomorTelepon']);
        mysqli_query($koneksi, "INSERT INTO pelanggan (UserID, NamaPelanggan, Alamat, NomorTelepon) VALUES ('$last_id', '$nama', '$alamat', '$telp')");
    }
    echo "<script>alert('Berhasil disimpan!'); window.location='admin.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | KasirPro</title>
    <link rel="stylesheet" href="style1.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        #tabelDetail { width: 100%; border-collapse: collapse; margin-top: 10px; }
        #tabelDetail th { background: #f8fafc; padding: 10px; text-align: left; border-bottom: 2px solid #edf2f7; font-size: 0.9em; }
        #tabelDetail td { padding: 10px; border-bottom: 1px solid #edf2f7; font-size: 0.9em; }
        .deleted-user { color: #ef4444; font-style: italic; }
        .status-dihapus { color: #94a3b8; text-decoration: line-through; }
        #laporan-header { display: none; }
    </style>
</head>
<body>

<div class="navbar no-print">
    <h2>KasirPro <span>| Admin Panel</span></h2>
    <div>
        <span style="margin-right:15px">👤 <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></span>
        <a href="../logout.php" class="btn-logout" onclick="return confirm('Keluar?')">Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="tab-menu no-print">
        <button class="tab-btn active" onclick="openTab(event, 'inventory')">📦 Inventaris</button>
        <button class="tab-btn" onclick="openTab(event, 'history')">📜 Riwayat Transaksi</button>
        <button class="tab-btn" onclick="openTab(event, 'users')">👥 User</button>
        <button class="tab-btn" onclick="openTab(event, 'registration')">➕ Registrasi</button>
    </div>

    <div id="inventory" class="tab-content active">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3>Stok Barang</h3>
                <button onclick="openModal('produkModal')" class="btn-primary">+ Barang Baru</button>
            </div>
            <table>
                <thead>
                    <tr><th>No</th><th>Produk</th><th>Harga</th><th>Stok</th><th class="aksi-kolom">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $data = mysqli_query($koneksi, "SELECT * FROM produk WHERE Status='Tersedia' ORDER BY ProdukID DESC");
                    while($r = mysqli_fetch_assoc($data)){ ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $r['NamaProduk']; ?></strong></td>
                        <td>Rp <?php echo number_format($r['Harga'], 0,',','.'); ?></td>
                        <td><?php echo $r['Stok']; ?></td>
                        <td class="aksi-kolom">
                            <button onclick="editBarang(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="btn-warning-small">Edit</button>
                            <a href="?hapus_produk=<?php echo $r['ProdukID']; ?>" class="btn-danger-small" onclick="return confirm('Yakin ingin menghapus produk ini? Produk tidak akan benar-benar hilang dari database riwayat.')">Hapus</a>
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
                <h2 style="margin:0;">LAPORAN PENJUALAN KASIRPRO</h2>
                <p style="margin:5px 0;">Data Seluruh Transaksi Pelanggan</p>
                <small>Dicetak pada: <?php echo date('d/m/Y H:i'); ?></small>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;" class="btn-action-laporan">
                <h3>Riwayat Penjualan</h3>
                <div style="display:flex; gap:10px;">
                    <button onclick="window.print()" class="btn-primary" style="background:#6366f1;">🖨️ Cetak</button>
                    <button onclick="simpanGambar()" class="btn-primary" style="background:#10b981;">🖼️ Simpan PNG</button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th style="text-align:right;">Total Bayar</th>
                        <th style="text-align:center;" class="aksi-kolom">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    $q_hist = mysqli_query($koneksi, "SELECT penjualan.*, pelanggan.NamaPelanggan FROM penjualan LEFT JOIN pelanggan ON penjualan.PelangganID = pelanggan.PelangganID ORDER BY PenjualanID DESC");
                    while($h = mysqli_fetch_assoc($q_hist)){
                        $grand_total += $h['TotalHarga'];
                        $nama_user = $h['NamaPelanggan'] ?? "<span class='deleted-user'>Akun Terhapus</span>";
                        $nama_label = $h['NamaPelanggan'] ?? "Akun Terhapus";
                    ?>
                    <tr>
                        <td>#<?php echo $h['PenjualanID']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($h['TanggalPenjualan'])); ?></td>
                        <td><?php echo $nama_user; ?></td>
                        <td style="text-align:right;"><strong>Rp <?php echo number_format($h['TotalHarga'], 0, ',', '.'); ?></strong></td>
                        <td style="text-align:center;" class="aksi-kolom">
                            <button onclick="lihatDetail('<?php echo $h['PenjualanID']; ?>', '<?php echo $nama_label; ?>', '<?php echo number_format($h['TotalHarga'], 0, ',', '.'); ?>')" class="btn-warning-small" style="background:#6366f1">Detail</button>
                            <a href="?hapus_history=<?php echo $h['PenjualanID']; ?>" class="btn-danger-small" onclick="return confirm('Hapus riwayat?')">Hapus</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:bold;">
                        <td colspan="3" style="text-align:right;">TOTAL OMZET :</td>
                        <td style="text-align:right; color:#16a34a;">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                        <td class="aksi-kolom"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div id="users" class="tab-content">
        <div class="card">
            <h3>Daftar Pengguna</h3>
            <table>
                <thead>
                    <tr><th>Nama</th><th>Username</th><th>Role</th><th class="aksi-kolom">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $du = mysqli_query($koneksi, "SELECT * FROM user");
                    while($u = mysqli_fetch_assoc($du)){ 
                        $is_me = ($u['Username'] === $session_username);
                    ?>
                    <tr>
                        <td><?php echo $u['Nama']; ?> <?php echo $is_me ? "<strong>(Anda)</strong>" : ""; ?></td>
                        <td><?php echo $u['Username']; ?></td>
                        <td><span class="status-badge" style="background:<?php echo ($u['Role']=='admin') ? 'var(--primary-color)' : 'var(--accent-color)'; ?>; color:white;"><?php echo strtoupper($u['Role']); ?></span></td>
                        <td class="aksi-kolom">
                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="btn-warning-small">Edit</button>
                            <?php if(!$is_me){ ?>
                                <a href="?hapus_user=<?php echo $u['UserID']; ?>" class="btn-danger-small" onclick="return confirm('Hapus user?')">Hapus</a>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="registration" class="tab-content">
        <div class="card">
            <h3>Tambah User Baru</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="full-width">
                        <label>Pilih Role</label>
                        <select name="Role" id="roleSelector" onchange="updateForm()" required>
                            <option value="petugas">Petugas</option>
                            <option value="pelanggan">Pelanggan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="full-width"><input type="text" name="Nama" placeholder="Nama Lengkap" required></div>
                    <input type="text" name="Username" placeholder="Username" required>
                    <input type="password" name="Password" placeholder="Password" required>
                    <div id="groupPelanggan" class="full-width force-hidden">
                        <input type="text" name="NomorTelepon" placeholder="Nomor Telepon">
                    </div>
                    <div class="full-width"><textarea name="Alamat" placeholder="Alamat Lengkap" rows="2" required></textarea></div>
                    <button type="submit" name="simpan_registrasi" class="btn-primary full-width">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="detailModal" class="modal">
    <div class="modal-content">
        <div style="border-bottom: 2px solid #eee; padding-bottom: 10px; display:flex; justify-content: space-between;">
            <h3 style="margin:0">Detail Transaksi <span id="det_id"></span></h3>
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
            <h4 style="margin:0">Total: Rp <span id="det_total"></span></h4>
        </div>
    </div>
</div>

<div id="produkModal" class="modal">
    <div class="modal-content">
        <h3>Form Produk</h3>
        <form method="POST">
            <input type="hidden" name="ProdukID" id="edit_id">
            <div class="form-grid">
                <div class="full-width"><label>Nama Produk</label><input type="text" name="NamaProduk" id="edit_nama" required></div>
                <div><label>Harga</label><input type="number" name="Harga" id="edit_harga" required></div>
                <div><label>Stok</label><input type="number" name="Stok" id="edit_stok" required></div>
                <div class="full-width" style="display:flex; gap:10px;">
                    <button type="submit" name="proses_produk" class="btn-primary" style="flex:1;">Simpan</button>
                    <button type="button" onclick="closeModal('produkModal')" class="btn-logout" style="background:var(--border-color); color:#333 !important;">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <h3>Edit User</h3>
        <form method="POST">
            <input type="hidden" name="UserID" id="u_id">
            <div class="form-grid">
                <div class="full-width"><label>Nama</label><input type="text" name="Nama" id="u_nama" required></div>
                <div><label>Username</label><input type="text" name="Username" id="u_user" required></div>
                <div>
                    <label>Role</label>
                    <select name="Role" id="u_role">
                        <option value="admin">Admin</option>
                        <option value="petugas">Petugas</option>
                        <option value="pelanggan">Pelanggan</option>
                    </select>
                </div>
                <div class="full-width"><label>Password (Kosongkan jika tidak ganti)</label><input type="password" name="Password"></div>
                <div class="full-width"><label>Alamat</label><textarea name="Alamat" id="u_alamat"></textarea></div>
                <div class="full-width" style="display:flex; gap:10px;">
                    <button type="submit" name="update_user" class="btn-primary" style="flex:1;">Update</button>
                    <button type="button" onclick="closeModal('userModal')" class="btn-logout" style="background:var(--border-color); color:#333 !important;">Batal</button>
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

    function updateForm() {
        const role = document.getElementById('roleSelector').value;
        const group = document.getElementById('groupPelanggan');
        if(role === 'pelanggan') group.classList.remove('force-hidden');
        else group.classList.add('force-hidden');
    }

    function openModal(id) { document.getElementById(id).style.display = "flex"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }

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

    function editBarang(data) {
        document.getElementById("edit_id").value = data.ProdukID;
        document.getElementById("edit_nama").value = data.NamaProduk;
        document.getElementById("edit_harga").value = data.Harga;
        document.getElementById("edit_stok").value = data.Stok;
        openModal('produkModal');
    }

    function editUser(data) {
        document.getElementById("u_id").value = data.UserID;
        document.getElementById("u_nama").value = data.Nama;
        document.getElementById("u_user").value = data.Username;
        document.getElementById("u_role").value = data.Role;
        document.getElementById("u_alamat").value = data.Alamat;
        openModal('userModal');
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
            link.download = 'Laporan-' + Date.now() + '.png';
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