<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';
require_once __DIR__ . '/admin/components/Userheader.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | Job Search Uganda</title>

    <link rel="stylesheet" href="assets/libs/flaticon/css/all/all.css">
    <link rel="stylesheet" href="assets/libs/lucide/lucide.css">
    <link rel="stylesheet" href="assets/libs/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/libs/simplebar/simplebar.css">
    <link rel="stylesheet" href="assets/libs/node-waves/waves.css">
    <link rel="stylesheet" href="assets/libs/bootstrap-select/css/bootstrap-select.min.css">
    <!-- end::GXON Required Stylesheet -->

    <!-- begin::GXON CSS Stylesheet -->
    <link rel="stylesheet" href="assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="assets/libs/datatables/datatables.min.css">

    <link rel="stylesheet" href="assets/css/custom.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->


</head>

<body>





<div class="about-page">


    <!-- HERO -->

    <section class="about-hero">

        <div class="about-hero-content">

            <div class="about-badge">

                <i class="fas fa-briefcase"></i>

                Job Search Uganda

            </div>

            <h1>

                Connecting Talent
                <br>

                <span>With Opportunity</span>

            </h1>

            <p>

                Connecting talented job seekers with trusted employers
                across Uganda. Our mission is to simplify recruitment by
                providing a fast, secure, and reliable online job marketplace.

            </p>

        </div>

    </section>


    <!-- WHO WE ARE -->

    <section class="about-section">

        <div class="container">

            <div class="row align-items-center g-5">

                <div class="col-lg-6">

                    <div class="section-label">
                        <i class="fas fa-building"></i>
                        About Us
                    </div>

                    <h2 class="about-section-title">
                        Who We Are
                    </h2>

                    <p class="about-text mt-4">

                        Job Search Uganda is an online recruitment platform
                        designed to connect employers with qualified job
                        seekers throughout Uganda.

                    </p>

                    <p class="about-text">

                        Whether you're searching for your first job, a career
                        change, or the perfect employee, our platform makes
                        recruitment simple, efficient, and accessible.

                    </p>

                    <p class="about-text">

                        We work with registered companies, organizations,
                        government agencies, NGOs, and individual employers
                        to advertise genuine employment opportunities.

                    </p>

                </div>


                <div class="col-lg-6">

                    <div class="about-image-wrapper">

                        <img
                            src="https://images.unsplash.com/photo-1521791136064-7986c2920216?w=1200"
                            class="about-image"
                            alt="Job interview"
                        >

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- MISSION / VISION -->

    <section class="about-section bg-white">

        <div class="container">

            <div class="text-center mb-5">

                <div class="section-label">
                    <i class="fas fa-compass"></i>
                    Our Direction
                </div>

                <h2 class="about-section-title">
                    Mission & Vision
                </h2>

            </div>


            <div class="row g-4">

                <div class="col-md-6">

                    <div class="about-card">

                        <div class="about-icon">

                            <i class="fas fa-bullseye"></i>

                        </div>

                        <h3>
                            Our Mission
                        </h3>

                        <p>

                            To bridge the gap between employers and job seekers
                            by providing an affordable, transparent, and
                            user-friendly recruitment platform that promotes
                            employment opportunities across Uganda.

                        </p>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="about-card">

                        <div class="about-icon">

                            <i class="fas fa-eye"></i>

                        </div>

                        <h3>
                            Our Vision
                        </h3>

                        <p>

                            To become Uganda's leading digital recruitment
                            platform, empowering businesses to recruit top
                            talent while helping thousands of professionals
                            build successful careers.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- WHY CHOOSE US -->

    <section class="about-section">

        <div class="container">

            <div class="text-center mb-5">

                <div class="section-label">
                    <i class="fas fa-star"></i>
                    Why Us
                </div>

                <h2 class="about-section-title">
                    Why Choose Us?
                </h2>

            </div>


            <div class="row g-4">

                <div class="col-md-4">

                    <div class="about-card text-center">

                        <div class="about-icon mx-auto">

                            <i class="fas fa-shield-halved"></i>

                        </div>

                        <h4>
                            Verified Jobs
                        </h4>

                        <p>

                            We strive to ensure that every advertised
                            opportunity is genuine and from trusted employers.

                        </p>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="about-card text-center">

                        <div class="about-icon mx-auto">

                            <i class="fas fa-users"></i>

                        </div>

                        <h4>
                            Easy Recruitment
                        </h4>

                        <p>

                            Employers can quickly publish vacancies, manage
                            applications, and recruit qualified candidates.

                        </p>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="about-card text-center">

                        <div class="about-icon mx-auto">

                            <i class="fas fa-mobile-screen-button"></i>

                        </div>

                        <h4>
                            Accessible Anywhere
                        </h4>

                        <p>

                            Browse and apply for jobs anytime using your
                            phone, tablet, or computer.

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- STATISTICS -->

    <section class="about-stats">

        <div class="container">

            <div class="row text-center">

                <div class="col-6 col-md-3 about-stat">

                    <div class="about-stat-number">
                        1000+
                    </div>

                    <p class="about-stat-label">
                        Job Listings
                    </p>

                </div>


                <div class="col-6 col-md-3 about-stat">

                    <div class="about-stat-number">
                        500+
                    </div>

                    <p class="about-stat-label">
                        Employers
                    </p>

                </div>


                <div class="col-6 col-md-3 about-stat">

                    <div class="about-stat-number">
                        10K+
                    </div>

                    <p class="about-stat-label">
                        Job Seekers
                    </p>

                </div>


                <div class="col-6 col-md-3 about-stat">

                    <div class="about-stat-number">
                        24/7
                    </div>

                    <p class="about-stat-label">
                        Support
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- CTA -->

    <section class="about-cta">

        <div class="container">

            <div class="about-cta-box">

                <h2>
                    Ready to Start Your Career?
                </h2>

                <p>
                    Browse available jobs or post your vacancy today.
                </p>

                <a
                    href="index.php"
                    class="about-cta-btn about-cta-primary"
                >
                    <i class="fas fa-search"></i>
                    Browse Jobs
                </a>

                <a
                    href="job_posting.php"
                    class="about-cta-btn about-cta-outline"
                >
                    <i class="fas fa-plus"></i>
                    Post a Job
                </a>

            </div>

        </div>

    </section>


    <!-- FOOTER -->

    <footer class="about-footer">

        &copy; <?= date('Y') ?>

        <strong>
            Job Search Uganda
        </strong>

        . All Rights Reserved.

    </footer>

</div>


<script>

function toggleMobileMenu() {

    const menu = document.getElementById("mobileMenu");

    if (menu.style.display === "none" || menu.style.display === "") {

        menu.style.display = "block";

    } else {

        menu.style.display = "none";

    }

}

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>