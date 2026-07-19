<?php

class JobModel
{
    private $pdo;

    public function __construct($db)
    {
        $this->pdo = $db;
    }

    public function createJob(
        $employer_id,
        $job_title,
        $company_name,
        $job_category,
        $location,
        $salary,
        $job_type,
        $description,
        $requirements,
        $deadline,
        $max_applications
    ) {
        $sql = "INSERT INTO jobs
(
    employer_id,
    job_title,
    company_name,
    job_category,
    location,
    salary,
    job_type,
    description,
    requirements,
    deadline,
    max_applications,
    current_applications
)
VALUES
(
    :employer_id,
    :job_title,
    :company_name,
    :job_category,
    :location,
    :salary,
    :job_type,
    :description,
    :requirements,
    :deadline,
    :max_applications,
    0
)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':employer_id' => $employer_id,
            ':job_title' => $job_title,
            ':company_name' => $company_name,
            ':job_category' => $job_category,
            ':location' => $location,
            ':salary' => $salary,
            ':job_type' => $job_type,
            ':description' => $description,
            ':requirements' => $requirements,
            ':deadline' => $deadline,
            ':max_applications' => $max_applications,
        ]);
    }


   public function getAllOpenJobs()
{
    $sql = "
        SELECT
            j.job_id,
            j.job_title,
            j.description,
            j.location,
            j.salary,
            j.deadline,
            j.created_at,

            jc.category_name,

            jt.type_name AS job_type,

            u.user_name AS employer

        FROM jobs j

        LEFT JOIN job_categories jc
            ON j.job_category = jc.category_id

        LEFT JOIN job_types jt
            ON j.job_type = jt.type_id

        LEFT JOIN users u
            ON j.employer_id = u.user_id

        WHERE j.status = 'Open'

        ORDER BY j.created_at DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function getJobCategories()
    {
        $stmt = $this->pdo->prepare("
            SELECT category_id, category_name
            FROM job_categories
            ORDER BY category_name ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




 public function createGuestJob(
    $job_reference,
    $company_name,
    $contact_person,
    $email,
    $phone,
    $company_logo,
    $job_title,
    $category_id,
    $job_type,
    $location,
    $salary,
    $description,
    $requirements,
    $deadline,
    $max_applications
) {

    $sql = "INSERT INTO guest_jobs
    (
        job_reference,
        company_name,
        contact_person,
        email,
        phone,
        company_logo,
        category_id,
        job_title,
        job_type,
        location,
        salary,
        description,
        requirements,
        deadline,
        max_applications,
        current_applications
    )
    VALUES
    (
        :job_reference,
        :company_name,
        :contact_person,
        :email,
        :phone,
        :company_logo,
        :category_id,
        :job_title,
        :job_type,
        :location,
        :salary,
        :description,
        :requirements,
        :deadline,
        :max_applications,
        0
    )";


    $stmt = $this->pdo->prepare($sql);


    $success = $stmt->execute([

        ':job_reference'     => $job_reference,
        ':company_name'      => $company_name,
        ':contact_person'    => $contact_person,
        ':email'             => $email,
        ':phone'             => $phone,
        ':company_logo'      => $company_logo,
        ':category_id'       => $category_id,
        ':job_title'         => $job_title,
        ':job_type'          => $job_type,
        ':location'          => $location,
        ':salary'            => $salary,
        ':description'       => $description,
        ':requirements'      => $requirements,
        ':deadline'          => $deadline,
        ':max_applications'  => $max_applications

    ]);


    if ($success) {

        return $this->pdo->lastInsertId();

    }


    return false;
}
public function getAllGuestJobs()
{
    $sql = "
        SELECT
            gj.job_id,
            gj.company_name,
            gj.contact_person,
            gj.email,
            gj.phone,
            gj.company_logo,
            gj.job_title,
            gj.location,
            gj.salary,
            gj.description,
            gj.requirements,
            gj.deadline,
            gj.max_applications,
            gj.current_applications,
            gj.payment_status,
            gj.status,
            gj.created_at,

            jc.category_name,

            jt.type_name AS job_type

        FROM guest_jobs gj

        LEFT JOIN job_categories jc
            ON gj.category_id = jc.category_id

        LEFT JOIN job_types jt
            ON gj.job_type = jt.type_id

        ORDER BY gj.created_at DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getJobTypeName($job_type)
{
    $sql = "
        SELECT type_name
        FROM job_types
        WHERE type_id = ?
    ";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([$job_type]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($result) {
        return $result['type_name'];
    }


    return "Unknown";
}

    /*
    |--------------------------------------------------------------------------
    | Lookups
    |--------------------------------------------------------------------------
    */

    public function getJobTypes()
    {
        $stmt = $this->pdo->query("
            SELECT type_id, type_name
            FROM job_types
            WHERE status = 'active'
            ORDER BY type_name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Approved guest jobs only (public listing)
    |--------------------------------------------------------------------------
    */

    public function getApprovedGuestJobs()
    {
        $sql = "
            SELECT
                gj.job_id,
                gj.job_reference,
                gj.company_name,
                gj.contact_person,
                gj.email,
                gj.phone,
                gj.company_logo,
                gj.job_title,
                gj.location,
                gj.salary,
                gj.description,
                gj.requirements,
                gj.deadline,
                gj.max_applications,
                gj.current_applications,
                gj.status,
                gj.created_at,
                jc.category_name,
                jt.type_name AS job_type
            FROM guest_jobs gj
            LEFT JOIN job_categories jc ON gj.category_id = jc.category_id
            LEFT JOIN job_types jt ON gj.job_type = jt.type_id
            WHERE gj.status = 'approved'
            ORDER BY gj.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Single job lookups (by source)
    |--------------------------------------------------------------------------
    */

    public function getJobById($job_id)
    {
        $sql = "
            SELECT
                j.*,
                jc.category_name,
                jt.type_name AS job_type_name,
                u.user_name AS employer
            FROM jobs j
            LEFT JOIN job_categories jc ON j.job_category = jc.category_id
            LEFT JOIN job_types jt ON j.job_type = jt.type_id
            LEFT JOIN users u ON j.employer_id = u.user_id
            WHERE j.job_id = ?
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$job_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getGuestJobById($job_id)
    {
        $sql = "
            SELECT
                gj.*,
                jc.category_name,
                jt.type_name AS job_type_name
            FROM guest_jobs gj
            LEFT JOIN job_categories jc ON gj.category_id = jc.category_id
            LEFT JOIN job_types jt ON gj.job_type = jt.type_id
            WHERE gj.job_id = ?
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$job_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Applications counter + auto-close
    |--------------------------------------------------------------------------
    */

    public function incrementApplications($source, $job_id)
    {
        if ($source === 'guest') {
            $stmt = $this->pdo->prepare(
                "UPDATE guest_jobs
                 SET current_applications = current_applications + 1
                 WHERE job_id = ?"
            );
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE jobs
                 SET current_applications = current_applications + 1
                 WHERE job_id = ?"
            );
        }

        return $stmt->execute([$job_id]);
    }

    public function closeJobIfFull($source, $job_id)
    {
        if ($source === 'guest') {
            $stmt = $this->pdo->prepare(
                "UPDATE guest_jobs
                 SET status = 'closed'
                 WHERE job_id = ?
                   AND current_applications >= max_applications
                   AND status = 'approved'"
            );
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE jobs
                 SET status = 'Closed'
                 WHERE job_id = ?
                   AND current_applications >= max_applications
                   AND status = 'Open'"
            );
        }

        return $stmt->execute([$job_id]);
    }


    public function updateGuestJobStatus($job_id, $status)
    {
        $allowed = ['pending', 'approved', 'rejected', 'closed'];

        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE guest_jobs SET status = ? WHERE job_id = ?"
        );

        return $stmt->execute([$status, $job_id]);
    }

    public function getAllGuestJobsForAdmin()
    {
        return $this->getAllGuestJobs();
    }

    public function getAllEmployerJobs()
    {
        $sql = "
            SELECT
                j.job_id,
                j.job_title,
                j.company_name,
                j.status,
                j.max_applications,
                j.current_applications,
                j.created_at,
                jc.category_name,
                jt.type_name AS job_type
            FROM jobs j
            LEFT JOIN job_categories jc ON j.job_category = jc.category_id
            LEFT JOIN job_types jt ON j.job_type = jt.type_id
            ORDER BY j.created_at DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

