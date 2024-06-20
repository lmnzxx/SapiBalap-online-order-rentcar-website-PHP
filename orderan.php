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
    // Fetch order history for the current user
    $stmtOrderHistory = $mysqli->prepare("SELECT o.orderid, k.merk_type, k.plat_nomor, o.tgl_mulai, o.tgl_selesai, o.total_harga, o.status_order
                                            FROM `order` o
                                            INNER JOIN kendaraan k ON o.plat = k.plat_nomor
                                            WHERE o.custid = ?
                                            ORDER BY o.orderid DESC");


    $stmtOrderHistory->bind_param("i", $customerid);

    if (!$stmtOrderHistory->execute()) {
        die("Error executing the query to fetch order history: " . $stmtOrderHistory->error);
    }

    $resultOrderHistory = $stmtOrderHistory->get_result();

    if ($resultOrderHistory === false) {
        die("Error getting the result set: " . $stmtOrderHistory->error);
    }

    $stmtOrderHistory->close();

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["batalkan_pesanan"])) {
            // Tangani pembatalan pesanan di sini
            $order_id_to_cancel = $_POST["batalkan_pesanan"];
            batalkanPesanan($order_id_to_cancel);
        } elseif (isset($_POST["terima_pesanan"])) {
            // Tangani penerimaan pesanan di sini
            $order_id_to_accept = $_POST["terima_pesanan"];
            terimaPesanan($order_id_to_accept);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pembayaran'])) {
        $orderid = $_POST['orderid'];
    
        $file_name = $_FILES['bukti_bayar']['name'];
        $file_temp = $_FILES['bukti_bayar']['tmp_name'];
        $file_size = $_FILES['bukti_bayar']['size'];
    
        if (empty($file_name) || empty($file_temp)) {
            die("Upload bukti pembayaran gagal. Pastikan file dipilih.");
        }
    
        $upload_folder = 'media/bukti/';
        $upload_path = $upload_folder . $file_name;
    
        date_default_timezone_set('Asia/Makassar');
        $tgl_pembayaran = date('Y-m-d H:i:s');
        $status_bayar = '0';
    
        // Pindahkan file dari temp ke folder upload
        if (move_uploaded_file($file_temp, $upload_path)) {
            // File berhasil diupload, simpan path di database
            $query = "INSERT INTO pembayaran (orderid, tgl_bayar, bukti_bayar, status_bayar) VALUES ('$orderid', '$tgl_pembayaran', '$upload_path', '$status_bayar')";
            $result = mysqli_query($mysqli, $query);
    
            if ($result) {
                // Update status order menjadi Menunggu Konfirmasi
                $update_order_status_query = "UPDATE `order` SET status_order = 'Menunggu Konfirmasi' WHERE orderid = ?";
                $stmt_update_status = $mysqli->prepare($update_order_status_query);
                $stmt_update_status->bind_param("i", $orderid);

                if ($stmt_update_status->execute()) {
                    echo "<script>
                    alert('Bukti pembayaran berhasil disubmit, pembayaran akan segera dikonfirmasi.');
                    document.location='orderan.php';
                    </script>";
                } else {
                    die("Query error: " . mysqli_error($koneksi));
                }
            } else {
                die("Upload bukti pembayaran gagal.");
            }
        }
    }

    function batalkanPesanan($order_id) {
        global $mysqli;
        $update_status_query = "UPDATE `order` SET status_order = 'Dibatalkan' WHERE orderid = ?";
        $stmt = $mysqli->prepare($update_status_query);
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            echo "<script>
            alert('Maaf atas ketidaknyamanannya, semoga kita bertemu lagi di orderan yang lainnya');
            document.location='orderan.php';
            </script>";
            exit;
        } else {
            die("Error: Gagal membatalkan pesanan.");
        }
    }
    
    function terimaPesanan($order_id) {
        global $mysqli;
        $update_status_query = "UPDATE `order` SET status_order = 'Selesai' WHERE orderid = ?";
        $stmt = $mysqli->prepare($update_status_query);
        $stmt->bind_param("i", $order_id);
    
        if ($stmt->execute()) {
            echo "<script>
            alert('Terimakasih sudah menggunakan jasa penyewaan kami! Selamat berlibur dengan kendaraan yang sudah anda sewa, Always Drive Safe!');
            document.location='orderan.php';
            </script>";
            exit;
        } else {
            die("Error: Gagal menerima pesanan.");
        }
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order History</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
        <link rel="icon" href="media/sb.svg" type="image/svg+xml">
        <style>
            body::-webkit-scrollbar {
                width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
            }
            body {
                font-family: 'Montserrat', sans-serif;
            }
            .container h1 {
                font-size: 38px;
                font-family: 'Montserrat', sans-serif;
                font-weight: 600;
            }
            .table {
                margin-top: 20px;
            }
            th {
                font-size: 20px;
            }
        </style>
    </head>

    <body>
        <?php
            include "hal.php";
            menu($nama);
        ?>

        <div class="container tabel-orderan mt-5">
            <h1>Order History</h1>

            <?php if ($resultOrderHistory->num_rows > 0) : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Kendaraan</th>
                            <th>Plat Nomor</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Total Harga</th>
                            <th>Status Order</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resultOrderHistory->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo $row['merk_type']; ?></td>
                                <td><?php echo $row['plat_nomor']; ?></td>
                                <td><?php echo $row['tgl_mulai']; ?></td>
                                <td><?php echo $row['tgl_selesai']; ?></td>
                                <td>IDR <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $row['status_order']; ?></td>
                                <td>
                                    <?php if ($row['status_order'] == 'Dikirim') : ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return confirm('Apakah Anda yakin sudah menerima pesanan anda?');">
                                            <input type="hidden" name="terima_pesanan" value="<?php echo $row['orderid']; ?>">
                                            <button type="submit" class="btn btn-success">Pesanan Diterima</button>
                                        </form>
                                    <?php elseif ($row['status_order'] == 'Menunggu Pembayaran') : ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="return confirm('Apakah Anda yakin untuk membatalkan pesanan anda?');">
                                            <input type="hidden" name="batalkan_pesanan" value="<?php echo $row['orderid']; ?>">
                                            <button type="submit" class="btn btn-danger">Batalkan</button>
                                        </form>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPembayaran<?php echo $row['orderid']; ?>" style="margin-right: 5px; margin-top: 5px">Bayar</button>
                                        <!-- Modal Pembayaran -->
                                        <div class="modal fade" id="modalPembayaran<?php echo $row['orderid']; ?>" tabindex="-1" aria-labelledby="modalPembayaranLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="modalPembayaranLabel">Pembayaran</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h3 style="font-weight: 800;"><?php echo $row['merk_type']?></h3>
                                                        <h5 style="font-weight: 600; margin-bottom: 20px">Total Harga : IDR <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></h5>
                                                        <p>Pembayaran dapat dilakukan dengan transfer ke rekening BCA 91284823122231 A/N CV. MEMBALAP SAPI ABADI 
                                                            <br> atau ke rekening BRI 423842739421098 A/N ADINDA MAHARANI</p>
                                                        <form action="" method="POST" enctype="multipart/form-data">
                                                            <div class="mb-3">  
                                                                <input type="hidden" name="orderid" value="<?php echo $row['orderid']; ?>">
                                                                <label for="bukti_pembayaran">Kemudian Bukti Pembayaran dapat di upload pada form Dibawah ini</label>
                                                            </div>
                                                            <div class="mb-3">
                                                                <input type="file" class="form-control" name="bukti_bayar" required>
                                                                <button type="submit" name="proses_pembayaran" class="btn btn-primary mt-3">Konfirmasi Pembayaran</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <span class="text-muted">No action available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="alert alert-info">Belum ada riwayat order.</div>
            <?php endif; ?>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Ambil nilai parameter dari URL
                const urlParams = new URLSearchParams(window.location.search);
                const successParam = urlParams.get('success');

                // Periksa apakah parameter success=true
                if (successParam === 'true') {
                    // Tampilkan pesan alert
                    alert('Pesanan terkirim, silahkan melakukan pembayaran untuk memproses pesanan');
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    </body>

    </html>