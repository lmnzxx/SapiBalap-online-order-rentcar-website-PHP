<?php
// Sertakan file koneksi ke database
require_once "dbconn.php";

// Variabel untuk menyimpan input dari formulir pendaftaran
$email = $password = $confirm_password = $nama = $nik = $alamat = $notlpn = "";
$email_err = $password_err = $confirm_password_err = $nama_err = $nik_err = $alamat_err = $notlpn_err = "";

// Cek apakah formulir telah dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Masukkan alamat email.";
    } else {
        // Periksa apakah email sudah digunakan
        $sql = "SELECT user FROM user WHERE email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $email_err = "Alamat email ini sudah terdaftar.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Ada yang tidak beres. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }

    // Validasi password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Masukkan kata sandi.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Kata sandi minimal harus terdiri dari 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validasi konfirmasi password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Konfirmasi kata sandi.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Kata sandi tidak sesuai.";
        }
    }

    // Validasi nama
    if (empty(trim($_POST["nama"]))) {
        $nama_err = "Masukkan nama.";
    } else {
        $nama = trim($_POST["nama"]);
    }

    // Validasi NIK
    if (empty(trim($_POST["nik"]))) {
        $nik_err = "Masukkan NIK.";
    } else {
        $nik = trim($_POST["nik"]);
    }

    // Validasi alamat
    if (empty(trim($_POST["alamat"]))) {
        $alamat_err = "Masukkan alamat.";
    } else {
        $alamat = trim($_POST["alamat"]);
    }

    // Validasi nomor telepon
    if (empty(trim($_POST["notlpn"]))) {
        $notlpn_err = "Masukkan nomor telepon.";
    } else {
        $notlpn = trim($_POST["notlpn"]);
    }

    // Validasi pendaftaran
    if (empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($nama_err) && empty($nik_err) && empty($alamat_err) && empty($notlpn_err)) {

        // Masukkan data ke tabel user
        $sql_user = "INSERT INTO user (email, password, role, custid) VALUES (?, ?, '0', ?)";
        if ($stmt_user = $mysqli->prepare($sql_user)) {
            $stmt_user->bind_param("sss", $param_email, $param_password, $param_customerid);

            $param_email = $email;
            $param_password = $password;
            $param_customerid = null; // Customer ID belum diketahui karena belum diinput ke tabel customer

            if ($stmt_user->execute()) {
                // Ambil userid yang baru saja dimasukkan
                $userid = $stmt_user->insert_id;

                // Masukkan data ke tabel customer
                $sql_customer = "INSERT INTO cust (nama, nik, alamat, no_telp) VALUES (?, ?, ?, ?)";
                if ($stmt_customer = $mysqli->prepare($sql_customer)) {
                    $stmt_customer->bind_param("ssss", $param_nama, $param_nik, $param_alamat, $param_notlpn);

                    $param_nama = $nama;
                    $param_nik = $nik;
                    $param_alamat = $alamat;
                    $param_notlpn = $notlpn;

                    if ($stmt_customer->execute()) {
                        // Ambil customerid yang baru saja dimasukkan
                        $customerid = $stmt_customer->insert_id;

                        // Update customerid di tabel user
                        $sql_update_user = "UPDATE user SET custid = ? WHERE user = ?";
                        if ($stmt_update_user = $mysqli->prepare($sql_update_user)) {
                            $stmt_update_user->bind_param("ss", $param_customerid, $param_userid);

                            $param_customerid = $customerid;
                            $param_userid = $userid;

                            $stmt_update_user->execute();

                            $stmt_update_user->close();
                        }
                        
                        // Redirect ke halaman login setelah pendaftaran berhasil
                        header("location: login.php");
                    } else {
                        echo "Oops! Ada yang tidak beres. Silakan coba lagi nanti.";
                    }

                    $stmt_customer->close();
                }
            } else {
                echo "Oops! Ada yang tidak beres. Silakan coba lagi nanti.";
            }

            $stmt_user->close();
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
    <title>Register</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <h2 class="text-center">Register</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="text" id="email" name="email" class="form-control" value="<?php echo $email; ?>">
                <span class="text-danger"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-control" value="<?php echo $password; ?>">
                <span class="text-danger"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Kata Sandi</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
                <span class="text-danger"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" class="form-control" value="<?php echo $nama; ?>">
                <span class="text-danger"><?php echo $nama_err; ?></span>
            </div>
            <div class="form-group">
                <label for="nik">NIK</label>
                <input type="text" id="nik" name="nik" class="form-control" value="<?php echo $nik; ?>">
                <span class="text-danger"><?php echo $nik_err; ?></span>
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <input type="text" id="alamat" name="alamat" class="form-control" value="<?php echo $alamat; ?>">
                <span class="text-danger"><?php echo $alamat_err; ?></span>
            </div>
            <div class="form-group">
                <label for="notlpn">Nomor Telepon</label>
                <input type="text" id="notlpn" name="notlpn" class="form-control" value="<?php echo $notlpn; ?>">
                <span class="text-danger"><?php echo $notlpn_err; ?></span>
            </div>
            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">Daftar</button>
            </div>
        </form>
        <p class="text-center">Sudah punya akun? <a href="login.php">Login disini</a></p>
        <p class="text-center"><a href="index.php">Kembali ke Halaman Utama</a></p>
    </div>    
</body>
</html>