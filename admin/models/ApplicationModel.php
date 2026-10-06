<?php

class ApplicationModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createApplication(
        $job_id,
        $job_source,
        $applicant_name,
        $email,
        $phone,
        $cover_letter,
        $cv_path,
        $education    = null,
        $experience   = null,
        $skills       = null
    ) {
        // Generate a unique application reference.
        $ref = $this->generateReference();

        $sql = "INSERT INTO applications
            (application_reference, job_id, job_source, applicant_name, email, phone,
             education, experience, skills, cover_letter, cv_path)
            VALUES
            (:ref, :job_id, :job_source, :applicant_name, :email, :phone,
             :education, :experience, :skills, :cover_letter, :cv_path)";

        $stmt = $this->pdo->prepare($sql);

        $ok = $stmt->execute([
            ':ref'            => $ref,
            ':job_id'         => $job_id,
            ':job_source'     => $job_source,
            ':applicant_name' => $applicant_name,
            ':email'          => $email,
            ':phone'          => $phone,
            ':education'      => $education,
            ':experience'     => $experience,
            ':skills'         => $skills,
            ':cover_letter'   => $cover_letter,
            ':cv_path'        => $cv_path,
        ]);

        return $ok ? $ref : false;
    }

    public function generateReference(): string
    {
        do {
            $ref = 'APP-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $exists = $this->pdo->prepare(
                "SELECT application_id FROM applications WHERE application_reference = ? LIMIT 1"
            );
            $exists->execute([$ref]);
        } while ($exists->fetch());
        return $ref;
    }

    public function hasApplied($job_id, $job_source, $email): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT application_id
             FROM applications
             WHERE job_id = ? AND job_source = ? AND email = ?
             LIMIT 1"
        );
        $stmt->execute([$job_id, $job_source, $email]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllApplications(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                a.*,
                COALESCE(j.job_title, gj.job_title)       AS job_title,
                COALESCE(j.company_name, gj.company_name) AS company_name
            FROM applications a
            LEFT JOIN jobs j
                ON a.job_source = 'jobs' AND a.job_id = j.job_id
            LEFT JOIN guest_jobs gj
                ON a.job_source = 'guest' AND a.job_id = gj.job_id
            ORDER BY a.created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $application_id, string $status): bool
    {
        $allowed = ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'];

        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE applications SET status = ? WHERE application_id = ?"
        );

        return $stmt->execute([$status, $application_id]);
    }

    public function getApplicationsByStatus()
{
    $sql = "
        SELECT status, COUNT(*) AS cnt
        FROM applications
        GROUP BY status
        ORDER BY cnt DESC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

     public function getRecentApplications()
    {
        $stmt = $this->pdo->query("
            SELECT
                a.application_id,
                a.applicant_name,
                a.email,
                a.status,
                a.created_at,
                a.job_source,

                COALESCE(j.job_title, gj.job_title) AS job_title,

                COALESCE(j.company_name, gj.company_name) AS company_name

            FROM applications a

            LEFT JOIN jobs j
                ON a.job_source = 'jobs'
                AND a.job_id = j.job_id

            LEFT JOIN guest_jobs gj
                ON a.job_source = 'guest'
                AND a.job_id = gj.job_id

            ORDER BY a.created_at DESC

            LIMIT 8
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyApplications()
{
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%b %Y') AS month,
            DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
            COUNT(*) AS cnt
        FROM applications
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY sort_key, month
        ORDER BY sort_key ASC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPendingApplications()
{
    $stmt = $this->pdo->query("
        SELECT COUNT(*)
        FROM applications
        WHERE status='pending'
    ");

    return (int)$stmt->fetchColumn();
}

}
