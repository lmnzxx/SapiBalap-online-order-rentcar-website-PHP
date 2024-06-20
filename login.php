<?php
// Mematikan sesi jika sudah ada
session_start();
session_destroy();
session_unset();

session_start();
require_once "dbconn.php";

$email = $password = "";
$email_err = $password_err = "";

// Cek apakah formulir telah dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Periksa apakah email dan password kosong
    if (empty(trim($_POST["email"]))) {
        $email_err = "Masukkan alamat email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Masukkan kata sandi.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validasi login
    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT user, email, password, role, custid FROM user WHERE email = ?";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_email);

            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                // Periksa apakah email ada, jika ya, verifikasi kata sandi
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($userid, $email, $db_password, $role, $custid);
                    if ($stmt->fetch()) {
                        if ($password == $db_password) {
                            // Kata sandi benar, inisialisasi sesi
                            session_start();

                            // Simpan data sesi
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $userid;
                            $_SESSION["email"] = $email;
                            $_SESSION["type_user"] = $role;
                            
                            // Arahkan ke halaman yang sesuai
                            if ($role == "1") {
                                header("location: admin/order.php");
                            } else {
                                header("location: index.php");
                            }
                        } else {
                            // Tampilkan pesan kesalahan jika kata sandi tidak valid
                            $password_err = "Kata sandi yang Anda masukkan tidak valid.";
                        }
                    }
                } else {
                    // Tampilkan pesan kesalahan jika email tidak valid
                    $email_err = "Tidak ada akun ditemukan dengan alamat email tersebut.";
                }
            } else {
                echo "Oops! Ada yang tidak beres. Silakan coba lagi nanti.";
            }
            // Tutup pernyataan
            $stmt->close();
        }
    }

    // Tutup koneksi
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="media/sb.svg" type="image/svg+xml">
    <style>
        body { 
            font: 14px sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper { 
            width: 360px; 
            padding: 20px; 
            margin: 100px auto;
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <img src="media/sb.svg" alt="Logo" class="mx-auto d-block mb-3 mt-4" style="max-width: 80px; height: auto;">
        <h2 class="text-center">Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="text" id="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="text-danger"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-control">
                <span class="text-danger"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        <p class="text-center">Belum punya akun? <a href="register.php">Daftar di sini</a>.</p>
        <p class="text-center"><a href="index.php">Kembali ke Halaman Utama</a></p>
    </div>    

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

