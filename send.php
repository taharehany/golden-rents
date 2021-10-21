<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Golden Rose</title>
    <link rel="icon" href="./img/fav.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/over-style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>
  
    <!-- Header Area Start -->
    <header class="header-area">
        <!-- top bar Start -->
        <div class="top-bar d-none d-xl-block">
            <div class="container">
                <div class="row">
                    <div class="col offset-2">
                        <div class="top-bar__inner row justify-content-between align-items-center">
                            <ul class="top-bar__list list-unstyled col">
                                <li class="top-bar__item">
                                    <a class="top-bar__link" href="mailto:support@domain.com">support@domain.com</a>
                                </li>
                                <li class="top-bar__item">Mon to Fri : 9:00am to 6:00pm</li>
                                <li class="top-bar__item">The United Arab Emirates</li>
                            </ul>
                            <a class="btn btn-sm col-auto" href="#">
                                <i class="zmdi zmdi-format-list-bulleted"></i> Add Listings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- top bar end -->
        <div class="classy-nav-container breakpoint-off">
            <div class="container">
                <!-- Classy Menu -->
                <nav class="classy-navbar justify-content-between" id="conferNav">

                    <!-- Logo -->
                    <a class="nav-brand" href="index.html"><img src="img/logo.png" alt=""></a>

                    <div class="col-lg-auto col">
                        <div class="header-contacts d-none d-md-block d-lg-none d-xl-block">
                            <i class="fa fa-phone"></i>
                            <span class="header-contacts__inner">Call Us Now!
                                <a class="header-contacts__number" href="tel:+971 55 913 2338‬">+971 55 913 2338‬
                                </a>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Navbar Toggler -->
                    <div class="classy-navbar-toggler">
                        <span class="navbarToggler"><span></span><span></span><span></span></span>
                    </div>

                    <!-- Menu -->
                    <div class="classy-menu">
                        <!-- Menu Close Button -->
                        <div class="classycloseIcon">
                            <div class="cross-wrap"><span class="top"></span><span class="bottom"></span></div>
                        </div>
                        <!-- Nav Start -->
                        <div class="classynav">
                            <ul id="nav">
                                <li class="active"><a href="index.html">HOME</a></li>
                                <li><a href="index.html#about">ABOUT</a></li>
                                <li><a href="index.html#services">SERVICES</a></li>
                                <li><a href="index.html#clients">CLIENTS</a></li>
                                <li><a href="index.html#contact">CONTACT</a></li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>
    <!-- Header Area End -->


    <style>
        body {
            text-align: center;
            height: auto;
        }
        h3 {
            line-height:2;
            text-align: center
        }
        .header-area {
            background-color: rgba(255, 255, 255, 0.89);
        }
        footer {
            position: absolute;
            width: 100%;
            bottom: 0;
        }
    </style>

    <!-- /menubar -->
    <main id="main">
        <section id="about">
            <div class="container">
                <header class="section-header">
                    <h3>
                    <?php
                    $email= "ismat.adam1777@gmail.com";

                    if (!empty($_POST)){

                        if(empty($_POST['name']) or empty($_POST['tel']) or empty($_POST['email']) or empty($_POST['message'])){
                            echo("لم تقم بتعبئة جميع الخانات <br>");

                        }
                        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
                        if(!preg_match($regex, $_POST['email'])){
                            echo("بريد خاطئ");

                        }
                        else{
                            $message  = "الاسم :\n{$_POST["name"]}\n";
                            $message .= "التليفون :\n{$_POST["tel"]}\n";
                            $message .= "البريد الإلكتروني :\n{$_POST["email"]}\n";
                            $message .= "الرسالة :\n{$_POST["message"]}\n";
                            $message .= "أي بي :\n{$_SERVER['REMOTE_ADDR']}\n";
                            $message = stripslashes($message);
                            $message = wordwrap($message, 70);
                            mail($email,"Golden Rose landing SMS",$message,"From: {$_POST["name"]} <{$_POST["email"]}>");

                            echo("Your message has been received and will be contacted as soon as possible ..");
                        }
                    }
                    ?>
                    </h3>
                </header>
            </div>
        </section><!-- #contact -->
    </main>

    <!-- Footer Area Start -->
    <footer class="footer-area">
        <div class="container">
            <div class="copywrite-content">
                <div class="copywrite-text text-center">
                    <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | made <i class="fa fa-heart-o" aria-hidden="true"></i> by <a href="http://taha-rehany.site/" target="_blank">Alrehany</a></p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Area End -->

    <!-- **** All JS Files ***** -->
    <!-- jQuery 2.2.4 -->
    <script src="js/jquery.min.js"></script>
    <!-- Popper -->
    <script src="js/popper.min.js"></script>
    <!-- Bootstrap -->
    <script src="js/bootstrap.min.js"></script>
    <!-- All Plugins -->
    <script src="js/confer.bundle.js"></script>
    <!-- Active -->
    <script src="js/default-assets/active.js"></script>

</body>

</html>