<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Jika tidak, arahkan ke halaman login
    header("location: login.php");
    exit;
}

include "dbconn.php";

if (!isset($_SESSION["user_id"])) {
    die("Error: user_id is not set in the session.");
}

$userid = $_SESSION["user_id"];
$stmt = $mysqli->prepare("SELECT custid FROM user WHERE user = ?");
$stmt->bind_param("i", $userid);
if (!$stmt->execute()) {
    die("Error executing the first query: " . $stmt->error);
}
$stmt->bind_result($customerid);
$stmt->fetch();
$stmt->close();

$sqlnama = "SELECT nama FROM cust WHERE custid = ?";
$stmtNama = $mysqli->prepare($sqlnama);
$stmtNama->bind_param("i", $customerid);

if (!$stmtNama->execute()) {
    die("Error executing the second query: " . $stmtNama->error);
}

$resultnama = $stmtNama->get_result();

if ($resultnama === false) {
    die("Error getting the result set: " . $stmtNama->error);
}

if ($resultnama->num_rows > 0) {
    $rownama = $resultnama->fetch_assoc();
    $nama = $rownama['nama'];
}
// echo "user_id: $userid, custid: $customerid, nama: $nama";

$stmtNama->close();

// Handle car selection
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["plat_nomor"]) && isset($_POST["harga"])) {
        $_SESSION["order_plat_nomor"] = $_POST["plat_nomor"];
        $_SESSION["order_harga"] = $_POST["harga"];
        header("location: order.php"); // Redirect to the order page
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SapiBalap Car Rental</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
        <link rel="icon" href="media/sb.svg" type="image/svg+xml">
        <style>
            body {
                font-family: 'Montserrat', sans-serif;
            }
            body::-webkit-scrollbar {
                width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
            }
            h2 {
                font-family: 'Montserrat', cursive;
            }
            .card {
                transition: transform 0.2s;
                margin-bottom: 20px;
                height: 100%;
                position: relative;
            }
            .col {
                margin-top: 30px;
            }
            .card:hover {
                transform: scale(1.05);
            }
            .order-btn {
                width: 88%;
                position: absolute;
                bottom: 20px;
                left: 50%; 
                transform: translateX(-50%);
            }
            .card-body {
                position: relative;
            }
            .card-text.price {
                font-size: 24px; 
                font-weight: 600;
                position: relative;
                bottom: 45px;
            }
            .card-title {
                font-size: 24px;
                font-weight: 800;
            }
            .container h1 {
                font-size: 48px;
                font-family: 'Montserrat', sans-serif;
                font-weight: 800;
            }
            .card-text.plate {
                margin: 0;
            }
            .card-text.category {
                margin-bottom: 1.4rem;
            }
        </style>
    </head>

    <body>
        <?php
        include "hal.php";
        menu($nama);
        ?>

        <div class="container mt-5">
            <h1 style="margin-bottom: 40px;">List Produk Mobil</h1>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Cari mobil..." name="search">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </div>
            </form>

            <div class="row row-cols-1 row-cols-md-4" style="margin-bottom: 75px;">
                <?php
                include "dbconn.php";

                $searchTerm = '';
                if (isset($_GET['search'])) {
                    $searchTerm = $_GET['search'];
                    $searchTerm = mysqli_real_escape_string($mysqli, $searchTerm);

                    $query = "SELECT DISTINCT merk_type, kategori, harga, foto, plat_nomor 
                            FROM kendaraan 
                            WHERE status_kendaraan = true AND (merk_type LIKE '%$searchTerm%' OR kategori LIKE '%$searchTerm%')";
                } else {
                    // Default query without search
                    $query = "SELECT DISTINCT merk_type, kategori, harga, foto, plat_nomor 
                            FROM kendaraan 
                            WHERE status_kendaraan = true";
                }

                $result = $mysqli->query($query);

                // Loop untuk menampilkan data produk
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div class="col">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                                <div class="card">
                                    <img src="<?php echo $row['foto']; ?>" class="card-img-top" alt="Foto Mobil">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $row['merk_type']; ?></h5>
                                        <p class="card-text plate"><?php echo $row['plat_nomor']; ?></p>
                                        <p class="card-text category"><?php echo $row['kategori']; ?></p>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text price">IDR <?php echo number_format($row['harga'], 0, ',', '.'); ?></p>
                                    </div>
                                    <input type="hidden" name="plat_nomor" value="<?php echo $row['plat_nomor']; ?>">
                                    <input type="hidden" name="harga" value="<?php echo $row['harga']; ?>">
                                    <button type="submit" class="btn btn-primary order-btn">Order</button>
                                </div>
                            </form>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col"><p class="text-muted">Tidak ada mobil yang tersedia saat ini.</p></div>';
                }

                // Tutup koneksi
                $mysqli->close();
                ?>
            </div>
        </div>

        <footer>
            <?php
            generateFooter();
            ?>
        </footer>
        <!-- Tambahkan Bootstrap JS dan Popper.js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    </body>

</html>