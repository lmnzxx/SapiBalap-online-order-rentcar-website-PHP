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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pengiriman'])) {
    $pengirimanIdToProcess = $_POST['proses_pengiriman'];

    $updatePengiriman = "UPDATE pengiriman SET tgl_kirim = NOW(), status_kirim = 1 WHERE pengirimanid = ?";
    $stmtUpdatePengiriman = $mysqli->prepare($updatePengiriman);
    $stmtUpdatePengiriman->bind_param("i", $pengirimanIdToProcess);

    if (!$stmtUpdatePengiriman->execute()) {
        die("Error updating pengiriman data: " . $stmtUpdatePengiriman->error);
    }

    $stmtUpdatePengiriman->close();

    // Update data pada tabel order
    $updateOrder = "UPDATE `order` SET status_order = 'Dikirim' WHERE orderid = (SELECT orderid FROM pengiriman WHERE pengirimanid = ?)";
    $stmtUpdateOrder = $mysqli->prepare($updateOrder);
    $stmtUpdateOrder->bind_param("i", $pengirimanIdToProcess);

    if ($stmtUpdateOrder->execute()) {
        echo "<script>
        alert('Data pengiriman berhasil dirubah, silahkan lakukan pengiriman segera!');
        document.location='pengiriman.php';
        </script>";
    } else {
        die("Error updating order data: " . $stmtUpdateOrder->error);
    }

    $stmtUpdateOrder->close();
}

$sqlPengiriman = "SELECT p.pengirimanid, p.orderid, p.tgl_kirim, p.status_kirim,
                        o.alamat_drop, o.plat, o.custid, o.tgl_mulai,
                        k.merk_type,
                        c.nama 
                 FROM pengiriman p
                 JOIN `order` o ON p.orderid = o.orderid
                 JOIN kendaraan k ON o.plat = k.plat_nomor
                 JOIN cust c ON o.custid = c.custid
                 ORDER BY o.tgl_mulai DESC";

$resultPengiriman = $mysqli->query($sqlPengiriman);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shipping Page</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
        <link rel="icon" href="../media/sb.svg" type="image/svg+xml">
        <style>
            body::-webkit-scrollbar {
                width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
            }
            .container h1 {
                font-size: 38px;
                font-family: 'Montserrat', sans-serif;
                font-weight: 600;
                margin-bottom: 1.2%;
            }
            .tabel-pengiriman-admin {
                min-height: 700px;
            }
        </style>
</head>
<body>
    <?php
        menuAdmin();
    ?>
        <div class="container mt-5 tabel-pengiriman-admin">
        <h1>Data Pengiriman</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Pengiriman ID</th>
                    <th>Order ID</th>
                    <th>Nama Customer</th>
                    <th>Alamat Drop</th>
                    <th>Plat Nomor</th>
                    <th>Merk Type</th>
                    <th>Tanggal Kirim</th>
                    <th>Status Kirim</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($rowPengiriman = $resultPengiriman->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $rowPengiriman['pengirimanid']; ?></td>
                        <td><?php echo $rowPengiriman['orderid']; ?></td>
                        <td><?php echo $rowPengiriman['nama']; ?></td>
                        <td><?php echo $rowPengiriman['alamat_drop']; ?></td>
                        <td><?php echo $rowPengiriman['plat']; ?></td>
                        <td><?php echo $rowPengiriman['merk_type']; ?></td>
                        <td><?php echo $rowPengiriman['tgl_kirim']; ?></td>
                        <td><?php echo $rowPengiriman['status_kirim'] == 1 ? 'Dikirim' : 'Menunggu Pengiriman'; ?></td>
                        <td>
                            <?php if ($rowPengiriman['status_kirim'] == 0) : ?>
                                <form action="" method="post" onsubmit="return confirm('Apakah Anda yakin ingin melakukan pengiriman untuk order ini?');">
                                    <input type="hidden" name="proses_pengiriman" value="<?php echo $rowPengiriman['pengirimanid']; ?>">
                                    <button type="submit" class="btn btn-primary">Proses Pengiriman</button>
                                </form>
                            <?php else : ?>
                                <span class="text-muted">No action available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php generateFooter(); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>