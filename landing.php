<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Search Uganda</title>
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
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- end::GXON CSS Stylesheet -->


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background: linear-gradient(135deg,#0d6efd,#6f42c1);
            min-height:100vh;
            display:flex;
            align-items:center;
        }

        .choice-card{
            border:none;
            border-radius:20px;
            transition:.3s;
            cursor:pointer;
        }

        .choice-card:hover{
            transform:translateY(-8px);
            box-shadow:0 20px 40px rgba(0,0,0,.2);
        }

        .icon{
            font-size:70px;
        }

        .hero-title{
            font-size:3rem;
            font-weight:bold;
        }

        .hero-text{
            color:#ddd;
        }
    </style>

</head>
<body>

<div class="container">



    <div class="row justify-content-center">

        <div class="col-lg-10">

            <div class="text-center text-white mb-5">


            
                <h1 class="hero-title">
                    Welcome to Job Search Uganda
                </h1>

                <p class="hero-text fs-5">
                Explore and discover the right job for you! <br> or post a job.
                </p>

            </div>

            <div class="row">

                <!-- Job Seeker -->

                <div class="col-md-6 mb-4">

                    <a href="index.php" class="text-decoration-none">

                        <div class="card choice-card p-5 text-center h-100">

                            <div class="icon mb-3">
                                👨‍💼
                            </div>

                            <h2 class="fw-bold">
                                I'm a Job Seeker
                            </h2>

                            <p class="text-muted mt-3">

                                Browse available jobs, submit applications,
                                upload your CV and track your career.

                            </p>

                            <button class="btn btn-primary btn-lg mt-3">

                                Find Jobs

                            </button>

                        </div>

                    </a>

                </div>

                <!-- Employer -->

                <div class="col-md-6 mb-4">

                    <a href="job_posting.php" class="text-decoration-none">

                        <div class="card choice-card p-5 text-center h-100">

                            <div class="icon mb-3">
                                🏢
                            </div>

                            <h2 class="fw-bold">

                                I'm a Job Owner

                            </h2>

                            <p class="text-muted mt-3">

                                Post vacancies, manage applications,
                                and recruit qualified candidates.

                            </p>

                            <button class="btn btn-success btn-lg mt-3">

                                Post Jobs

                            </button>

                        </div>

                    </a>

                </div>

            </div>

            <div class="text-center mt-5 text-white">

                <p class="mb-1">
                    Connecting Talent with Opportunity
                </p>

                <small>
                    © <?= date('Y') ?> Job Search Uganda
                </small>

            </div>

        </div>

    </div>

</div>

</body>
</html>