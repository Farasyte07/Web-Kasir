<?php
session_start();
include 'config.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM user WHERE Username='$username' AND Password='$password'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        
        $_SESSION['userid'] = $data['UserID']; 
        $_SESSION['role']   = $data['Role'];
        $_SESSION['nama']   = $data['Nama'];

        if ($data['Role'] == 'admin') {
            header("location:user/admin.php");
        } elseif ($data['Role'] == 'petugas') {
            header("location:user/petugas.php");
        } else {
            header("location:user/pelanggan.php");
        }
        exit;
    } else {
        header("location:index.php?pesan=gagal");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir | KasirPro</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

    <div class="login-container">
        <form method="POST" action="">
            <h2>Login Web Kasir</h2>

            <?php if (isset($_GET['pesan'])): ?>
                <div id="alert-message" class="alert" 
                    <?php if ($_GET['pesan'] == "logout"): ?> 
                        style="background-color: #c6f6d5; color: #228b22; border: 1px solid #2f855a;" 
                    <?php endif; ?>>
                    
                    <?php 
                        if ($_GET['pesan'] == "gagal") echo "Username atau Password salah!";
                        elseif ($_GET['pesan'] == "logout") echo "Berhasil Logout.";
                        elseif ($_GET['pesan'] == "denied") echo "Akses ditolak! Login dahulu.";
                    ?>
                </div>
            <?php endif; ?>

            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Masuk</button>
        </form>
    </div>

    <script>
        const alertBox = document.getElementById('alert-message');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = "opacity 0.6s ease, transform 0.6s ease";
                alertBox.style.opacity = "0";
                alertBox.style.transform = "translateY(-10px)";

                setTimeout(() => {
                    alertBox.style.display = "none";
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 600);
            }, 3000);
        }
    </script>

</body>
</html>