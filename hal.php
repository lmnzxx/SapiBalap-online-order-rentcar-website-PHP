<?php
include "dbconn.php";

function menu($nama = null)
{
    ?>
    <style>
        .navbar-nav .nav-link {
            font-family: 'Montserrat', sans-serif;
            transition: none;
            color: #000;
            font-weight: 600;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 38px;
        }
        .navbar-nav {
            font-size: 20px;
        }
        .nav-item {
            margin-left: 1.2vw;
        }
    </style>

    <nav  class="navbar navbar-expand-lg navbar-light py-4" style="background-color: ffffff;" >
        <div class="container">
            <a class="navbar-brand" href="index.php"">
                <img src="media/sb.svg" alt="Logo" height="70" style="margin-right: .6vw;">
                Sapi Balap</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="carlist.php">CAR LIST</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orderan.php">ORDER</a>
                    </li>
                    <?php if ($nama): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" height="25" width="25" viewBox="0 0 448 512">
                                    <path d="M304 128a80 80 0 1 0 -160 0 80 80 0 1 0 160 0zM96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM49.3 464H398.7c-8.9-63.3-63.3-112-129-112H178.3c-65.7 0-120.1 48.7-129 112zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3z"/>
                                </svg>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <div class="dropdown-item"><?php echo $nama; ?></div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="login.php">Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">LOGIN</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php
}

function menuAdmin()
{
    ?>
    <style>
        .navbar-nav .nav-link {
            font-family: 'Montserrat', sans-serif;
            transition: none;
            color: #000;
            font-weight: 600;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 38px;
        }
        .navbar-nav {
            font-size: 20px;
        }
        .nav-item {
            margin-left: 1.2vw;
        }
    </style>

    <nav  class="navbar navbar-expand-lg navbar-light py-4" style="background-color: #ffffff;" >
        <div class="container">
            <a class="navbar-brand" href="order.php"">
                <img src="../media/sb.svg" alt="Logo" height="70" style="margin-right: .6vw;">
                Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="order.php">ORDER</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="carlist.php">CAR LIST</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pembayaran.php">PAYMENT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pengiriman.php">SHIPPING</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../login.php">LOG OUT</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php
}

function generateFooter() {
    ?>
    <footer class="footer bg-dark text-white text-center py-3">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <small>&copy; 2023 Sapi Balap Rent Car. All rights reserved.</small>
                </div>
                <div class="col-12 mb-2">
                <span class="fw-bold text-light" style="font-size: .85rem;">Managed by Pak Yan Media</span>
            </div>
            </div>
        </div>
    </footer>
    <?php
}
?>