<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

if (!isset($_SESSION["type_user"])) {
    die("Error: user_id is not set in the session.");
}

// Cek apakah pengguna memiliki peran admin
$role = $_SESSION["type_user"];
if ($role != 1) {
    // Pengguna bukan admin, redirect atau tampilkan pesan warning
    echo "<script>
            alert('Anda tidak memiliki izin untuk mengakses halaman admin.');
            window.location.href='../index.php';
            </script>";
    exit;
}

include "../dbconn.php";

// Fetch order data
$sqlOrder = "SELECT orderid, plat, custid, tgl_mulai, tgl_selesai, alamat_drop, status_order, total_harga
             FROM `order`
             ORDER BY tgl_mulai DESC";
    
$stmtOrder = $mysqli->prepare($sqlOrder);

if (!$stmtOrder->execute()) {
    die("Error executing the query to fetch order data: " . $stmtOrder->error);
}

$resultOrder = $stmtOrder->get_result();

if ($resultOrder === false) {
    die("Error getting the result set: " . $stmtOrder->error);
}

$stmtOrder->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["proses_pesanan"])) {
        $order_id_to_process = $_POST["proses_pesanan"];
        prosesPesanan($order_id_to_process);
    } elseif (isset($_POST["kirimkan_pesanan"])) {
        $order_id_to_ship = $_POST["kirimkan_pesanan"];
        kirimkanPesanan($order_id_to_ship);
    }
}

function prosesPesanan($order_id) {
    global $mysqli;

    // Update the status of the vehicle to 0
    $update_vehicle_status_query = "UPDATE kendaraan SET status_kendaraan = 0 WHERE plat_nomor = (
        SELECT plat FROM `order` WHERE orderid = ?
    )";
    $stmt_update_vehicle_status = $mysqli->prepare($update_vehicle_status_query);
    $stmt_update_vehicle_status->bind_param("i", $order_id);

    if ($stmt_update_vehicle_status->execute()) {
        // Update the status_order of the order to "Diproses"
        $update_order_status_query = "UPDATE `order` SET status_order = 'Diproses' WHERE orderid = ?";
        $stmt_update_order_status = $mysqli->prepare($update_order_status_query);
        $stmt_update_order_status->bind_param("i", $order_id);

        if ($stmt_update_order_status->execute()) {
            echo "<script>
            alert('Pesanan sudah diproses, silahkan siapkan kendaraan untuk selanjutnya dikirimkan.');
            document.location='order.php';
            </script>";
            exit;
        } else {
            die("Error updating order status: " . $stmt_update_order_status->error);
        }
    } else {
        die("Error updating vehicle status: " . $stmt_update_vehicle_status->error);
    }
}

function kirimkanPesanan($order_id) {
    global $mysqli;

    // Insert a new record into the pengiriman table
    $insert_pengiriman_query = "INSERT INTO pengiriman (orderid, status_kirim) VALUES (?, 0)";
    $stmt_insert_pengiriman = $mysqli->prepare($insert_pengiriman_query);
    $stmt_insert_pengiriman->bind_param("i", $order_id);

    if ($stmt_insert_pengiriman->execute()) {
        // Update the status_order of the order to "Dikirim"
        $update_order_status_query = "UPDATE `order` SET status_order = 'Menunggu Pengiriman' WHERE orderid = ?";
        $stmt_update_order_status = $mysqli->prepare($update_order_status_query);
        $stmt_update_order_status->bind_param("i", $order_id);

        if ($stmt_update_order_status->execute()) {
            echo "<script>
            alert('Data pengiriman sudah ditambahkan ke tabel pengiriman, silahkan lanjutkan proses pengiriman pada bagian Pengiriman.');
            document.location='order.php';
            </script>";
            exit;
        } else {
            die("Error updating order status: " . $stmt_update_order_status->error);
        }
    } else {
        die("Error inserting into pengiriman table: " . $stmt_insert_pengiriman->error);
    }
}

?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Page</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
        <link rel="icon" href="../media/sb.svg" type="image/svg+xml">
        <style>
            .tabel-order {
                min-height: 700px;
            }
            body::-webkit-scrollbar {
                width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
            }
            .container h1 {
                font-size: 38px;
                font-family: 'Montserrat', sans-serif;
                font-weight: 600;
                margin-bottom: 1.2%;
            }
            .table {
                margin-bottom: 3rem;
            }
        </style>
    </head>
    <body>
        <?php
        // Sertakan file koneksi dan fungsi mahasiswa
        include '../dbconn.php';
        include '../hal.php';
        
        // Panggil fungsi mahasiswa
        menuAdmin();
        ?>

        <div class="container mt-5 tabel-order">
            <h1>Order List</h1>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Plat Nomor</th>
                        <th>Nama Kendaraan</th>
                        <th>Nama Customer</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Alamat Drop</th>
                        <th>Status Order</th>
                        <th>Total Harga</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rowOrder = $resultOrder->fetch_assoc()) : ?>
                        <?php
                        // Fetch kendaraan data
                        $sqlKendaraan = "SELECT merk_type FROM kendaraan WHERE plat_nomor = ?";
                        $stmtKendaraan = $mysqli->prepare($sqlKendaraan);
                        $stmtKendaraan->bind_param("s", $rowOrder['plat']);

                        if ($stmtKendaraan->execute()) {
                            $resultKendaraan = $stmtKendaraan->get_result();

                            if ($resultKendaraan->num_rows > 0) {
                                $rowKendaraan = $resultKendaraan->fetch_assoc();
                                $namaKendaraan = $rowKendaraan['merk_type'];
                            } else {
                                $namaKendaraan = "N/A";
                            }
                        }

                        $stmtKendaraan->close();

                        // Fetch customer data
                        $sqlCustomer = "SELECT nama FROM cust WHERE custid = ?";
                        $stmtCustomer = $mysqli->prepare($sqlCustomer);
                        $stmtCustomer->bind_param("i", $rowOrder['custid']);

                        if ($stmtCustomer->execute()) {
                            $resultCustomer = $stmtCustomer->get_result();

                            if ($resultCustomer->num_rows > 0) {
                                $rowCustomer = $resultCustomer->fetch_assoc();
                                $namaCustomer = $rowCustomer['nama'];
                            } else {
                                $namaCustomer = "N/A";
                            }
                        }

                        $stmtCustomer->close();
                        ?>

                        <tr>
                            <td><?php echo $rowOrder['orderid']; ?></td>
                            <td><?php echo $rowOrder['plat']; ?></td>
                            <td><?php echo $namaKendaraan; ?></td>
                            <td><?php echo $namaCustomer; ?></td>
                            <td><?php echo $rowOrder['tgl_mulai']; ?></td>
                            <td><?php echo $rowOrder['tgl_selesai']; ?></td>
                            <td><?php echo $rowOrder['alamat_drop']; ?></td>
                            <td><?php echo $rowOrder['status_order']; ?></td>
                            <td><?php echo number_format($rowOrder['total_harga'], 0, ',', '.'); ?></td>
                            <td>
                            <?php
                                // Check status order
                                $statusOrder = $rowOrder['status_order'];

                                // Display "No action available" if status is "Diproses", "Dibatalkan", or "Selesai"
                                if($statusOrder === "Lunas") { ?>
                                    <form action="" method="post" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin ingin memproses orderan ini?');">
                                        <input type="hidden" name="proses_pesanan" value="<?php echo $rowOrder['orderid']; ?>">
                                        <button type="submit" class="btn btn-primary">Proses Orderan</button>
                                    </form>                                
                                <?php } elseif($statusOrder === "Diproses") { ?>
                                    <form action="" method="post" enctype="multipart/form-data" onsubmit="return confirm('Apakah Anda yakin kendaraan sudah siap untuk dikirimkan?');">
                                        <input type="hidden" name="kirimkan_pesanan" value="<?php echo $rowOrder['orderid']; ?>">
                                        <button type="submit" class="btn btn-primary">Proses Pengiriman</button>
                                    </form>                                  
                                <?php } else { ?>
                                    <span class="text-muted">No action available</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <footer>
            <?php
                generateFooter();
            ?>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    </body>
</html>