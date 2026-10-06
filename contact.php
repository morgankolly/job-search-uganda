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

    <title>Contact Us - Job Search Uganda</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="assets/libs/fontawesome/css/all.min.css">

    <style>

        /* =========================================================
           GENERAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fa;
            color: #102a43;
        }

        a {
            text-decoration: none;
        }


        /* =========================================================
           HEADER - MATCHES YOUR HOMEPAGE
        ========================================================= */

        .main-header {
            height: 100px;
            background: #ffffff;
            border-bottom: 1px solid #eeeeee;
            display: flex;
            align-items: center;
        }

        .header-container {
            width: 100%;
            max-width: 984px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 0;
        }

        /* LOGO */

        .logo-area {
            display: flex;
            align-items: center;
        }

        .logo-area img {
            width: 85px;
            height: auto;
            display: block;
        }

        /* NAVIGATION */

        .main-nav {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-left: 70px;
        }

        .main-nav a {
            color: #333333;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s ease;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: #2864f0;
        }

        /* HEADER BUTTONS */

        .header-actions {
            display: flex;
            gap: 9px;
            align-items: center;
        }

        .btn-employer {
            height: 38px;
            padding: 0 20px;
            border: 1px solid #2864f0;
            border-radius: 8px;
            background: #ffffff;
            color: #2864f0;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-admin {
            height: 38px;
            padding: 0 20px;
            border: 1px solid #2864f0;
            border-radius: 8px;
            background: #2864f0;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-employer:hover {
            background: #eef4ff;
        }

        .btn-admin:hover {
            background: #1d55d8;
            color: #fff;
        }


        /* =========================================================
           BLUE HERO - SAME STYLE AS SCREENSHOT
        ========================================================= */

        .contact-hero {
            background: #3267f5;
            min-height: 270px;
            display: flex;
            align-items: center;
        }

        .hero-container {
            width: 100%;
            max-width: 984px;
            margin: auto;
            padding: 38px 0 42px;
        }

        .hero-title {
            margin: 0;
            max-width: 600px;
            color: #092c66;
            font-size: 43px;
            line-height: 1.15;
            font-weight: 750;
            letter-spacing: -1.4px;
        }

        .hero-text {
            margin-top: 17px;
            max-width: 680px;
            color: #ffffff;
            font-size: 16px;
            line-height: 1.65;
        }

        .hero-buttons {
            margin-top: 21px;
            display: flex;
            gap: 11px;
        }

        .hero-btn-primary {
            background: #ffffff;
            color: #111111;
            border: 1px solid #ffffff;
            border-radius: 8px;
            padding: 11px 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .hero-btn-secondary {
            background: transparent;
            color: #ffffff;
            border: 1px solid #ffffff;
            border-radius: 8px;
            padding: 11px 22px;
            font-size: 14px;
            font-weight: 600;
        }

        .hero-btn-primary:hover {
            background: #f2f4f7;
            color: #111111;
        }

        .hero-btn-secondary:hover {
            background: rgba(255,255,255,0.12);
            color: #ffffff;
        }


        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        .contact-section {
            max-width: 984px;
            margin: 0 auto;
            padding: 48px 0 70px;
        }

        .section-line {
            height: 1px;
            background: #dfe4ea;
            margin-bottom: 37px;
        }

        .section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .section-heading h2 {
            margin: 0;
            color: #102a43;
            font-size: 27px;
            font-weight: 700;
        }

        .section-heading p {
            margin: 8px 0 0;
            color: #aabbd7;
            font-size: 13px;
            font-weight: 500;
        }

        .open-badge {
            background: #3267f5;
            color: white;
            padding: 5px 11px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }


        /* =========================================================
           CONTACT CONTENT CARDS
        ========================================================= */

        .contact-grid {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 22px;
            align-items: start;
        }

        .contact-card {
            background: #ffffff;
            border: 1px solid #e3e8ef;
            border-radius: 9px;
            padding: 25px;
            box-shadow: 0 2px 7px rgba(20, 40, 70, 0.05);
        }

        .contact-card h3 {
            margin: 0 0 5px;
            color: #102a43;
            font-size: 21px;
            font-weight: 700;
        }

        .contact-card-subtitle {
            color: #9aaed0;
            font-size: 13px;
            margin-bottom: 23px;
        }


        /* =========================================================
           FORM
        ========================================================= */

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            color: #233b56;
            font-size: 13px;
            font-weight: 600;
        }

        .required {
            color: #3267f5;
        }

        .form-control,
        .form-select {
            width: 100%;
            min-height: 43px;
            border: 1px solid #d8e0eb;
            border-radius: 7px;
            padding: 10px 12px;
            color: #243b53;
            background: #ffffff;
            font-size: 13px;
            box-shadow: none;
        }

        .form-control::placeholder {
            color: #a6b5ca;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3267f5;
            box-shadow: 0 0 0 3px rgba(50,103,245,0.10);
            outline: none;
        }

        textarea.form-control {
            min-height: 125px;
            resize: vertical;
        }

        .submit-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 4px;
        }

        .submit-btn {
            border: 0;
            background: #3267f5;
            color: #ffffff;
            border-radius: 7px;
            padding: 11px 23px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #2256df;
        }

        .submit-hint {
            color: #98a9c2;
            font-size: 11px;
        }

        .form-status {
            display: none;
            margin-top: 15px;
            padding: 11px 13px;
            border-radius: 6px;
            background: #edf7ed;
            color: #27733a;
            font-size: 13px;
        }

        .form-status.show {
            display: block;
        }


        /* =========================================================
           RIGHT SIDE CONTACT OPTIONS
        ========================================================= */

        .contact-option {
            padding: 15px 0;
            border-bottom: 1px solid #edf0f4;
        }

        .contact-option:first-child {
            padding-top: 0;
        }

        .contact-option:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .contact-option-title {
            color: #183b67;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .contact-option-description {
            color: #8496af;
            font-size: 12px;
            line-height: 1.5;
        }

        .contact-option-meta {
            margin-top: 6px;
            color: #3267f5;
            font-size: 11px;
            font-weight: 600;
        }


        /* =========================================================
           CONTACT INFORMATION
        ========================================================= */

        .info-box {
            margin-top: 20px;
            padding: 18px;
            background: #f6f8fb;
            border-radius: 7px;
        }

        .info-box-title {
            color: #183b67;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .info-box p {
            margin: 0;
            color: #8a9bb2;
            font-size: 12px;
            line-height: 1.6;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1050px) {

            .header-container,
            .hero-container,
            .contact-section {
                max-width: calc(100% - 40px);
            }

            .main-nav {
                margin-left: 30px;
                gap: 17px;
            }
        }

        @media (max-width: 800px) {

            .main-header {
                height: auto;
                padding: 15px 0;
            }

            .header-container {
                flex-wrap: wrap;
                gap: 15px;
            }

            .main-nav {
                order: 3;
                width: 100%;
                margin-left: 0;
                justify-content: center;
                flex-wrap: wrap;
            }

            .header-actions {
                margin-left: auto;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 36px;
            }
        }

        @media (max-width: 600px) {

            .header-actions {
                width: 100%;
                margin-left: 0;
            }

            .btn-employer,
            .btn-admin {
                flex: 1;
            }

            .main-nav {
                gap: 13px;
            }

            .main-nav a {
                font-size: 12px;
            }

            .hero-container {
                padding: 35px 0;
            }

            .hero-title {
                font-size: 32px;
            }

            .hero-text {
                font-size: 14px;
            }

            .hero-buttons {
                flex-wrap: wrap;
            }

            .section-heading {
                flex-direction: column;
                gap: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .submit-row {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>
</head>

<body>










    <div class="contact-grid">


        <!-- =====================================================
             CONTACT FORM
        ====================================================== -->

        <div class="contact-card" id="contact-form">

            <h3>
                Send Us a Message
            </h3>

            <div class="contact-card-subtitle">
                Fill in the form below and our team will get back to you.
            </div>


            <form id="contactForm">

                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label" for="name">
                            Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            placeholder="Your full name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label" for="email">
                            Email <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="you@email.com"
                            required
                        >

                    </div>

                </div>



                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label" for="account">
                            Account or Application ID
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="account"
                            name="account"
                            placeholder="e.g. APP-20458"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label" for="topic">
                            Reason for Contact
                            <span class="required">*</span>
                        </label>

                        <select
                            class="form-select"
                            id="topic"
                            name="topic"
                            required
                        >

                            <option value="" selected disabled>
                                Choose one...
                            </option>

                            <option>
                                Application status
                            </option>

                            <option>
                                Resume / profile upload issue
                            </option>

                            <option>
                                Account access or login
                            </option>

                            <option>
                                Billing & Subscription
                            </option>

                            <option>
                                Employer / recruiter inquiry
                            </option>

                            <option>
                                Report a suspicious posting
                            </option>

                            <option>
                                Accessibility
                            </option>

                            <option>
                                Something else
                            </option>

                        </select>

                    </div>

                </div>



                <div class="form-group">

                    <label class="form-label" for="message">
                        Message <span class="required">*</span>
                    </label>

                    <textarea
                        class="form-control"
                        id="message"
                        name="message"
                        placeholder="Tell us how we can help you..."
                        required
                    ></textarea>

                </div>



                <div class="submit-row">

                    <button
                        type="submit"
                        class="submit-btn"
                    >
                        Send Message
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    <span class="submit-hint">
                        Most enquiries receive a reply within one business day.
                    </span>

                </div>


                <div
                    class="form-status"
                    id="formStatus"
                ></div>

            </form>

        </div>



        <!-- =====================================================
             CONTACT OPTIONS
        ====================================================== -->

        <div class="contact-card">

            <h3>
                Other Ways to Reach Us
            </h3>

            <div class="contact-card-subtitle">
                Choose the option that best matches your enquiry.
            </div>


            <div class="contact-option">

                <div class="contact-option-title">
                    <i class="fa-solid fa-comments"></i>
                    Live Support
                </div>

                <div class="contact-option-description">
                    Get help with login, applications and profile issues.
                </div>

                <div class="contact-option-meta">
                    ● Available during support hours
                </div>

            </div>


            <div class="contact-option">

                <div class="contact-option-title">
                    <i class="fa-solid fa-envelope"></i>
                    General Support
                </div>

                <div class="contact-option-description">
                    Applications, accounts and general JobSearch questions.
                </div>

                <div class="contact-option-meta">
                    support@jobsearch.ug
                </div>

            </div>


            <div class="contact-option">

                <div class="contact-option-title">
                    <i class="fa-solid fa-building"></i>
                    Employers
                </div>

                <div class="contact-option-description">
                    Help with posting vacancies, subscriptions and recruitment.
                </div>

                <div class="contact-option-meta">
                    employers@jobsearch.ug
                </div>

            </div>


            <div class="contact-option">

                <div class="contact-option-title">
                    <i class="fa-solid fa-shield-halved"></i>
                    Report a Job
                </div>

                <div class="contact-option-description">
                    Report suspicious, fraudulent or inappropriate job listings.
                </div>

                <div class="contact-option-meta">
                    trust@jobsearch.ug
                </div>

            </div>


            <div class="info-box">

                <div class="info-box-title">
                    Support Hours
                </div>

                <p>
                    Monday – Friday: 9:00 AM – 6:00 PM<br>
                    Saturday: 10:00 AM – 4:00 PM<br>
                    Sunday: Closed
                </p>

            </div>

        </div>

    </div>

</main>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

    const form = document.getElementById('contactForm');
    const status = document.getElementById('formStatus');

    form.addEventListener('submit', function(e) {

        e.preventDefault();

        const caseNumber =
            Math.floor(100000 + Math.random() * 900000);

        status.textContent =
            'Your message has been received successfully. Case #' +
            caseNumber +
            '. We will contact you shortly.';

        status.classList.add('show');

        form.reset();

    });

</script>


</body>
</html>