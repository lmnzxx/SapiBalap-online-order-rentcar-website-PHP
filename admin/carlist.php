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
    include "../hal.php";

    //kondisi jika tombol simpan pada input ditekan
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kendaraan'])) {
        //kondisi jika task yang dilakukan adalah edit data
        if(isset($_GET['hal']) && $_GET['hal'] == "edit") {
            $plat = $_POST['plat'];
            $nama = $_POST['nama'];
            $status = $_POST['status'];
            $kategori = $_POST['kategori'];
            $harga = $_POST['harga'];
            // Proses update kendaraan
            $sqledit = "UPDATE kendaraan 
                        SET merk_type = '$nama', kategori = '$kategori', 
                        status_kendaraan = '$status', harga = '$harga'
                        WHERE plat_nomor = '$plat'";
            //konfirmasi berhasil atau gagal update data
            if($mysqli->query($sqledit)){
                echo "<script>
                        alert('Edit data kendaraan kamu berhasil!');
                        document.location='carlist.php';
                    </script>";
                     
            }
            else {
                echo "<script>
                alert('Edit data kendaraan kamu gagal nih, coba di cek lagi ya?');
                document.location='carlist.php';
            </script>";
            }
        }
        //kondisi jika task yang dilakukan bukan edit data
        //(task tambah data)
        else {
            // Ambil data dari formulir
            $plat = $_POST['plat'];
            $nama = $_POST['nama'];
            $status = $_POST['status'];
            $kategori = $_POST['kategori'];
            $harga = $_POST['harga'];
            $namaFile = 'media/'.$_FILES['foto']['name'];
            // Proses tambah data kendaraan
            $sql = "INSERT INTO kendaraan (plat_nomor, merk_type, kategori, status_kendaraan, harga, foto) 
                    VALUES ('$plat', '$nama', '$kategori', '$status', '$harga', '$namaFile')";
            if($mysqli->query($sql)){
                move_uploaded_file($_FILES['foto']['tmp_name'], '../media/' . $_FILES['foto']['name']);
                //jika tambah data berhasil
                echo "<script>
                        alert('Simpan data kendaraan kamu berhasil!');
                        document.location='carlist.php';
                    </script>";
            }
            else {
                //jika tambah data gagal
                echo "<script>
                alert('Error nih, kayanya kamu ada salah masukin data!?');
                document.location='carlist.php';
            </script>";
            }
        }
    }

    //kondisi jika button edit ditekan, akan menambah data kedalam field form untuk kemudian di edit
    $vstatus = 1;
    $vkategori = 'Sedan';
    if(isset($_GET['hal'])) {
        if($_GET['hal'] == "edit") {
            $tampil = mysqli_query($mysqli, "SELECT * FROM kendaraan where plat_nomor = '$_GET[plat]' ");
            $row = mysqli_fetch_array($tampil);
            if($row) {
                $vplat = $row['plat_nomor'];
                $vnama = $row['merk_type'];
                $vkategori = $row['kategori'];
                $vstatus = $row['status_kendaraan'];
                $vharga = $row['harga'];
            }
        }
        //kondisi jika yang ditekan adalah button delete atau hapus
        else if($_GET['hal'] == "hapus") {
            $hapus = mysqli_query($mysqli, "DELETE FROM kendaraan where plat_nomor = '$_GET[plat]' ");
            if($hapus) {
                echo "<script>
                alert('Data berhasil dihapus, Yang ga berhasil itu hubungan kamu sama dia');
                alert('Now playing : Misellia - Akhir Tak Bahagia');
                document.location='carlist.php';
            </script>";
            }
        }
    } 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kendaraan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800;900&family=Qwigley&display=swap" rel="stylesheet">
    <link rel="icon" href="../media/sb.svg" type="image/svg+xml">
    <style>
        body::-webkit-scrollbar {
                width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
            }
    </style>
</head>
<body>
    <!-- memanggil function menu untuk menampilkan taskbar pada website -->
    <?php
        menuAdmin()
    ?>

<!-- form masukan data kendaraan -->
    <div class="container mt-5 my-4">
        <h1 class="mb-4">Masukan Data Kendaraan</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="plat" class="form-label">Plat Nomor</label>
                <input type="text" class="form-control" id="plat" name="plat" value="<?=@$vplat?>" placeholder="Plat nomor kendaraan" required>
            </div>
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Kendaraan</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?=@$vnama?>" placeholder="Merk dan type kendaraan" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status Kendaraan</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="0" <?= ($vstatus == '0') ? 'selected' : ''; ?>>Disewakan</option>
                    <option value="1" <?= ($vstatus == '1') ? 'selected' : ''; ?>>Ready</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Kategori Kendaraan</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="Sedan" <?= ($vkategori == 'Sedan') ? 'selected' : ''; ?>>Sedan</option>
                    <option value="Sportcar" <?= ($vkategori == 'Sportcar') ? 'selected' : ''; ?>>Sportcar</option>
                    <option value="Minibus" <?= ($vkategori == 'Minibus') ? 'selected' : ''; ?>>Minibus</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Harga</label>
                <input type="text" class="form-control" id="harga" name="harga" value="<?=@$vharga?>" placeholder="Harga sewa untuk 1 hari" required>
            </div>
            <div class="mb-3">
                <label for="foto" class="form-label">Foto Kendaraan</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
            </div>
            <button type="submit" name="tambah_kendaraan" class="btn btn-primary">Simpan</button>
            <button type="reset" name="reset_kendaraan" class="btn btn-danger">Kosongkan</button>
        </form>
    </div>

    <div class="container my-5">
        <h1 class="mb-3";>Data Kendaraan</h1>
        <form class="mb-4" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" style="max-width: 440px;">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Cari kendaraan..." name="search">
                <button class="btn btn-primary" type="submit">Cari</button>
            </div>
        </form>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Plat Nomor</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Status Kendaraan</th>
                    <th>Harga</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $searchTerm = '';
                if (isset($_GET['search'])) {
                    $searchTerm = $_GET['search'];
                    $searchTerm = mysqli_real_escape_string($mysqli, $searchTerm);

                    $query = "SELECT * FROM kendaraan 
                            WHERE (plat_nomor LIKE '%$searchTerm%' OR merk_type LIKE '%$searchTerm%' OR kategori LIKE '%$searchTerm%');";
                } else {
                    // Default query without search
                    $query = "SELECT * FROM kendaraan;";
                }
                $result = $mysqli->query($query);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr>   
                            <td><img src="../<?= $row['foto'] ?>" alt="Foto Kendaraan" style="max-width: 200px; max-height: 200px;"></td>
                            <td><?= $row['plat_nomor'] ?></td>
                            <td><?= $row['merk_type'] ?></td>
                            <td><?= $row['kategori'] ?></td>
                            <?php $status = ($row['status_kendaraan'] == "0") ? 'Disewakan' : 'Ready'; ?>
                            <td><?= $status ?></td>
                            <td>IDR <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <a href="carlist.php?hal=edit&plat=<?=$row['plat_nomor']?>" class="btn btn-primary">Edit</a>
                                <a href="carlist.php?hal=hapus&plat=<?=$row['plat_nomor']?>" onclick="return confirm('Yakin mau dihapus?')" class="btn btn-danger">Hapus</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>Tidak ada data</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <?php generateFooter(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>