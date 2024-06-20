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

include '../dbconn.php';
include '../hal.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $paymentIdToConfirm = $_POST['confirm_payment'];

    // Update the payment status to 'Lunas' (1)
    $updatePaymentStatus = "UPDATE pembayaran SET status_bayar = 1 WHERE pembayaranid = ?";
    $stmtUpdatePayment = $mysqli->prepare($updatePaymentStatus);
    $stmtUpdatePayment->bind_param("i", $paymentIdToConfirm);

    if ($stmtUpdatePayment->execute()) {
        // Fetch the associated order ID
        $sqlGetOrderID = "SELECT orderid FROM pembayaran WHERE pembayaranid = ?";
        $stmtGetOrderID = $mysqli->prepare($sqlGetOrderID);
        $stmtGetOrderID->bind_param("i", $paymentIdToConfirm);
        $stmtGetOrderID->execute();
        $resultOrderID = $stmtGetOrderID->get_result();

        if ($resultOrderID->num_rows > 0) {
            $rowOrderID = $resultOrderID->fetch_assoc();
            $orderIdToUpdate = $rowOrderID['orderid'];

            // Update the associated order status to 'Lunas'
            $updateOrderStatus = "UPDATE `order` SET status_order = 'Lunas' WHERE orderid = ?";
            $stmtUpdateOrder = $mysqli->prepare($updateOrderStatus);
            $stmtUpdateOrder->bind_param("i", $orderIdToUpdate);

            if ($stmtUpdateOrder->execute()) {
                echo "<script>
                alert('Pembayaran berhasil dikonfirmasi, silahkan proses orderan pada halaman order.');
                document.location='pembayaran.php';
                </script>";
            } else {
                die("Error updating order status: " . $stmtUpdateOrder->error);
            }


            $stmtUpdateOrder->close();
        }

        $stmtGetOrderID->close();
    } else {
        // Handle the error if needed
        die("Error updating payment status: " . $stmtUpdatePayment->error);
    }

    $stmtUpdatePayment->close();
}

// Fetch payment data using a join operation
$sqlPayment = "SELECT p.pembayaranid, p.orderid, p.tgl_bayar, p.bukti_bayar, p.status_bayar, o.total_harga
               FROM pembayaran p
               JOIN `order` o ON p.orderid = o.orderid
               ORDER BY p.tgl_bayar DESC";

$resultPayment = $mysqli->query($sqlPayment);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Page</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
        <link rel="icon" href="../media/sb.svg" type="image/svg+xml">
        <style>
            body::-webkit-scrollbar {
                width: 0px; 
            }
            .thumbnail {
                max-width: 100px; 
                max-height: 100px; 
                cursor: pointer;
            }
            .modal-img {
                width: 100%;
                height: auto;
            }
            .tabel-pembayaran-admin {
                min-height: 700px;
            }
            .container h1 {
                font-size: 38px;
                font-family: 'Montserrat', sans-serif;
                font-weight: 600;
                margin-bottom: 1.2%;
            }
        </style>
</head>
<body>
    <?php
        menuAdmin();
    ?>

    <div class="container mt-5 tabel-pembayaran-admin">
        <h1>Daftar Pembayaran</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Pembayaran</th>
                    <th>ID Order</th>
                    <th>Tanggal Bayar</th>
                    <th>Jumlah Tagihan</th>
                    <th>Bukti Bayar</th>
                    <th>Status Bayar</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowPayment = $resultPayment->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $rowPayment['pembayaranid']; ?></td>
                        <td><?php echo $rowPayment['orderid']; ?></td>
                        <td><?php echo $rowPayment['tgl_bayar']; ?></td>
                        <td>IDR <?php echo number_format($rowPayment['total_harga'], 0, ',', '.'); ?></td>
                        <td>
                            <img src="../<?php echo $rowPayment['bukti_bayar']; ?>" alt="Bukti Bayar" class="thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal<?php echo $rowPayment['pembayaranid']; ?>">
                        </td>
                        <td><?php echo $rowPayment['status_bayar'] == 1 ? 'Lunas' : 'Menunggu Konfirmasi'; ?></td>
                        <td>
                            <?php if ($rowPayment['status_bayar'] == 0) : ?>
                                <form action="" method="post" onsubmit="return confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran ini?');">
                                    <input type="hidden" name="confirm_payment" value="<?php echo $rowPayment['pembayaranid']; ?>">
                                    <button type="submit" class="btn btn-success">Konfirmasi Pembayaran</button>
                                </form>
                            <?php else : ?>
                                <span class="text-muted">No action available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <div class="modal fade" id="imageModal<?php echo $rowPayment['pembayaranid']; ?>" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <img src="../<?php echo $rowPayment['bukti_bayar']; ?>" alt="Bukti Bayar" class="modal-img">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php generateFooter(); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>