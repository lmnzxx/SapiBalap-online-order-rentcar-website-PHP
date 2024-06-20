<?php
session_start();

include "dbconn.php";

// Redirect to home page if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Redirect to home page if user_id is not set in the session
if (!isset($_SESSION["user_id"])) {
    die("Error: user_id is not set in the session.");
}

$userid = $_SESSION["user_id"];

// Fetch customer id based on user id
$stmtCustomerId = $mysqli->prepare("SELECT custid FROM user WHERE user = ?");
$stmtCustomerId->bind_param("i", $userid);

if (!$stmtCustomerId->execute()) {
    die("Error executing the query to fetch customer id: " . $stmtCustomerId->error);
}

$resultCustomerId = $stmtCustomerId->get_result();

if ($resultCustomerId === false) {
    die("Error getting the result set: " . $stmtCustomerId->error);
}

$customerid = ($resultCustomerId->num_rows > 0) ? $resultCustomerId->fetch_assoc()['custid'] : "";

$stmtCustomerId->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plat_nomor = $_SESSION["order_plat_nomor"];
    $harga_per_hari = $_SESSION["order_harga"];

    // Check if the form fields are set
    $alamat = isset($_POST["alamat"]) ? $_POST["alamat"] : "";
    $tanggal_mulai = isset($_POST["tanggal_mulai"]) ? $_POST["tanggal_mulai"] : "";
    $tanggal_selesai = isset($_POST["tanggal_selesai"]) ? $_POST["tanggal_selesai"] : "";

    // Validate form fields
    if (empty($alamat) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $error_message = "Harap lengkapi semua bidang.";
    } else {
        // Calculate the number of days with a minimum of 1 day
        $start_date = new DateTime($tanggal_mulai);
        $end_date = new DateTime($tanggal_selesai);
        $interval = $start_date->diff($end_date);
        $number_of_days = max(1, $interval->days);

        // Perform checkout or store order data in the database
        $status_order = "Menunggu Pembayaran";

        // Ensure correct date format for MySQL DATETIME
        $formatted_start_date = $start_date->format('Y-m-d H:i:s');
        $formatted_end_date = $end_date->format('Y-m-d H:i:s');

        // Calculate total price
        $total_price = $number_of_days * $harga_per_hari;

        // Insert data into the 'order' table
        $insert_order_query = "INSERT INTO `order` (plat, custid, tgl_mulai, tgl_selesai, alamat_drop, status_order, total_harga) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($insert_order_query);
        $stmt->bind_param("sissssd", $plat_nomor, $customerid, $formatted_start_date, $formatted_end_date, $alamat, $status_order, $total_price);

        if ($stmt->execute()) {
            // Redirect to the thank you page
            header("location: orderan.php?success=true");
            exit;
        } else {
            $error_message = "Gagal melakukan order. Silakan coba lagi.";
        }

        $stmt->close();
    }
}

$selected_car_details = "";
if (isset($_SESSION["order_plat_nomor"]) && isset($_SESSION["order_harga"])) {
    $plat_nomor = $_SESSION["order_plat_nomor"];
    $harga = number_format($_SESSION["order_harga"], 0, ',', '.');

    // Fetch the name of the car based on the plate number
    $stmtCarName = $mysqli->prepare("SELECT merk_type FROM kendaraan WHERE plat_nomor = ?");
    $stmtCarName->bind_param("s", $plat_nomor);

    if ($stmtCarName->execute()) {
        $resultCarName = $stmtCarName->get_result();

        if ($resultCarName->num_rows > 0) {
            $carName = $resultCarName->fetch_assoc()['merk_type'];
            $selected_car_details = [
                'Nama Kendaraan' => $carName,
                'Plat Nomor' => $plat_nomor,
                'Harga per Hari' => $harga
            ];
        }
    }

    $stmtCarName->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
    <link rel="icon" href="media/sb.svg" type="image/svg+xml">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }

        .container h1 {
            font-size: 38px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .container {
            max-width: 800px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
        }

        .btn-order {
            width: 100%;
            margin-top: 20px;
        }
        .alert-info {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
        }
        .card-title {
            font-size: 28px;
            font-weight: 800;
        }

        .card-text {
            font-size: 16px;
            margin-bottom: 0.5rem;
        }
        .plate {
            font-size: 18px;
        }
        .price {
            font-size: 22px;
            font-weight: 600;
        }
        .info-kendaraan {
            position: absolute;
            top: 24px;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <h1>Checkout</h1>

        <?php if (!empty($selected_car_details)) : ?>
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <?php
                    // Fetch image data from the database
                    $stmtImage = $mysqli->prepare("SELECT foto FROM kendaraan WHERE plat_nomor = ?");
                    $stmtImage->bind_param("s", $plat_nomor);

                    if ($stmtImage->execute()) {
                        $stmtImage->store_result();

                        if ($stmtImage->num_rows > 0) {
                            $stmtImage->bind_result($imageData);
                            $stmtImage->fetch();

                            // Display the image
                            echo '<img src="' . $imageData . '" alt="Car Image" style="width: 250px; height: auto; margin-right: 24px;">';
                        }
                    }

                    $stmtImage->close();
                    ?>

                    <div>
                        <div class="info-kendaraan">
                            <h5 class="card-title"><?php echo $carName; ?></h5>
                            <p class="card-text plate"><?php echo $plat_nomor; ?></p>
                            <p class="card-text price">IDR <?php echo $harga; ?> /Day</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="alamat">Alamat Drop:</label>
                <input type="text" class="form-control" id="alamat" name="alamat" required>
            </div>

            <div class="form-group">
                <label for="tanggal_mulai">Tanggal Mulai Sewa:</label>
                <input type="datetime-local" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
            </div>

            <div class="form-group">
                <label for="tanggal_selesai">Tanggal Selesai Sewa:</label>
                <input type="datetime-local" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
            </div>

            <?php if (isset($error_message)) : ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <input type="submit" class="btn btn-primary btn-order" value="Order Sekarang">
        </form>
    </div>
    <!-- Tambahkan Bootstrap JS dan Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
