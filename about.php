<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once __DIR__ . '/admin/config/connection.php';
require_once __DIR__ . '/admin/helpers/functions.php';
require_once __DIR__ . '/admin/models/JobModel.php';
include_once __DIR__ . '/admin/components/Userheader.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search System</title>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->

  

</head>

<body>

<!-- Hero -->
<section class="hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold">About Job Search Uganda</h1>

        <p class="lead mt-4">
            Connecting talented job seekers with trusted employers across Uganda.
            Our mission is to simplify recruitment by providing a fast, secure,
            and reliable online job marketplace.
        </p>
    </div>
</section>

<!-- About -->
<section class="py-5">
    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-6">
                <h2 class="section-title">Who We Are</h2>

                <p class="text-muted">
                    Job Search Uganda is an online recruitment platform designed to
                    connect employers with qualified job seekers throughout Uganda.
                    Whether you're searching for your first job, a career change,
                    or the perfect employee, our platform makes recruitment simple,
                    efficient, and accessible.
                </p>

                <p class="text-muted">
                    We work with registered companies, organizations, government
                    agencies, NGOs, and individual employers to advertise genuine
                    employment opportunities while helping applicants find careers
                    that match their skills and ambitions.
                </p>
            </div>

            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?w=900"
                    class="img-fluid rounded shadow" alt="">
            </div>

        </div>

    </div>
</section>

<!-- Mission Vision -->
<section class="py-5 bg-white">

    <div class="container">

        <div class="row g-4">

            <div class="col-md-6">

                <div class="feature-box">

                    <i class="fas fa-bullseye"></i>

                    <h3>Our Mission</h3>

                    <p class="text-muted">
                        To bridge the gap between employers and job seekers by
                        providing an affordable, transparent, and user-friendly
                        recruitment platform that promotes employment opportunities
                        across Uganda.
                    </p>

                </div>

            </div>

            <div class="col-md-6">

                <div class="feature-box">

                    <i class="fas fa-eye"></i>

                    <h3>Our Vision</h3>

                    <p class="text-muted">
                        To become Uganda's leading digital recruitment platform,
                        empowering businesses to recruit top talent while helping
                        thousands of professionals build successful careers.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- Why Choose Us -->

<section class="py-5">

<div class="container">

<h2 class="text-center section-title">Why Choose Us?</h2>

<div class="row mt-5 g-4">

<div class="col-md-4">

<div class="feature-box text-center">

<i class="fas fa-briefcase"></i>

<h4>Verified Jobs</h4>

<p class="text-muted">
We strive to ensure that every advertised opportunity is genuine and from trusted employers.
</p>

</div>

</div>

<div class="col-md-4">

<div class="feature-box text-center">

<i class="fas fa-users"></i>

<h4>Easy Recruitment</h4>

<p class="text-muted">
Employers can quickly publish vacancies, manage applications, and recruit qualified candidates.
</p>

</div>

</div>

<div class="col-md-4">

<div class="feature-box text-center">

<i class="fas fa-mobile-screen-button"></i>

<h4>Accessible Anywhere</h4>

<p class="text-muted">
Browse and apply for jobs anytime using your phone, tablet, or computer.
</p>

</div>

</div>

</div>

</div>

</section>

<!-- Statistics -->

<section class="stats">

<div class="container">

<div class="row text-center">

<div class="col-md-3 stat-box">

<h2>1000+</h2>

<p>Job Listings</p>

</div>

<div class="col-md-3 stat-box">

<h2>500+</h2>

<p>Employers</p>

</div>

<div class="col-md-3 stat-box">

<h2>10K+</h2>

<p>Job Seekers</p>

</div>

<div class="col-md-3 stat-box">

<h2>24/7</h2>

<p>Support</p>

</div>

</div>

</div>

</section>

<!-- Core Values -->

<section class="py-5 bg-white">

<div class="container">

<h2 class="section-title">Our Core Values</h2>

<div class="row mt-4">

<div class="col-md-6">

<ul class="list-group list-group-flush">

<li class="list-group-item">✔ Integrity and Transparency</li>

<li class="list-group-item">✔ Professionalism</li>

<li class="list-group-item">✔ Innovation</li>

<li class="list-group-item">✔ Equal Employment Opportunities</li>

<li class="list-group-item">✔ Customer Satisfaction</li>

</ul>

</div>

<div class="col-md-6">

<p class="text-muted">
We believe everyone deserves equal access to employment opportunities.
Our commitment is to provide a trusted recruitment environment where
employers can find the right talent and job seekers can achieve their
career aspirations.
</p>

</div>

</div>

</div>

</section>

<!-- CTA -->

<section class="py-5 text-center">

<div class="container">

<h2>Ready to Start Your Career?</h2>

<p class="text-muted">
Browse hundreds of available jobs or post your vacancy today.
</p>

<a href="index.php" class="btn btn-primary btn-lg me-2">
Browse Jobs
</a>

<a href="job_posting.php" class="btn btn-outline-primary btn-lg">
Post a Job
</a>

</div>

</section>

<footer class="text-center">

&copy; <?php echo date('Y'); ?> Job Search Uganda. All Rights Reserved.

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>