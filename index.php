<?php
session_start();

// Cek apakah pengguna sudah login
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    include "dbconn.php";

    if (isset($_SESSION["user_id"])) {
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

        $stmtNama->close();
    }
}

$nama = isset($nama) ? $nama : null;
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
    </head>
    <style>
        #carouselExample img {
            object-fit: cover;
            max-height: 30vw;
            width: 100%;
            margin: auto;
        }

        #carouselExample .carousel-caption {
            top: 50%;
            bottom: initial;
            transform: translateY(-50%);
            padding: 20px; 
            border-radius: 10px; 
            color: #ffffff; 
        }

        .carousel-caption h1 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); 
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
        }

        #description p {
            text-align: justify;
            font-size: 18px;
        }

        body::-webkit-scrollbar {
            width: 0px; /* Sesuaikan dengan lebar scrollbar yang diinginkan */
        }
    </style>
    
    <body>
        <?php
        include 'dbconn.php';
        include 'hal.php';
        menu($nama);
        ?>

        <section id="home">
            <div id="carouselExample" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="media/12.jpg" class="d-block w-100" alt="Slide 1">
                        <div class="carousel-caption d-none d-md-block">
                            <h1 class="text-shadow" style="font-size: 7rem;">Sapi Balap Car Rental</h1>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="description" class="py-5">
            <div class="container" style="max-width: 90vw;">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <h2 class="text-center">Welcome to Sapi Balap Car Rental</h2>
                        <p class="lead text-center">Your trusted partner for high-quality car rental services.</p>
                        <p>SapiBalap Rental is a private luxury and exotic car rental business based in Denpasar, Bali. Providing luxury and exotic cars since 2019, we have served thousands of customers, including locals, domestic tourists, and international tourists.</p>
                        <p>If you prefer to explore Bali independently, we provide the option of car rentals without a driver. Our vehicles are well-maintained, reliable, and equipped to ensure a safe and enjoyable self-driven experience on the enchanting island of Bali.</p>
                        <p>For those seeking a stress-free journey, we also offer car rental packages with a skilled and knowledgeable driver who will be your trusted guide throughout your adventures in Bali. Our drivers are well-versed with the local routes and attractions, ensuring you have a smooth and memorable trip.</p>
                        <p>As part of our commitment to excellent service, we extend complimentary area shuttle services in popular locations such as Seminyak, Kuta, Legian, and the Airport. This added convenience allows you to move around with ease and enjoy the beauty of Bali without any hassle.</p>
                        <p>Choose SapiBalap Rental for your next adventure in Bali and experience the finest car rental service the island has to offer. Let us enhance your journey with our good vehicles, attentive team, and the assurance of a smooth, safe, and memorable experience in the beautiful paradise of Bali.</p>
                    </div>
                </div>
            </div>
        </section>

        <footer>
            <?php
                generateFooter();
            ?>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    </body>
</html>