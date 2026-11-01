<?php
class UserModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }



        public static function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php");   // your login page
            exit;
        }
    }
    public function emailExists(string $email): bool
{
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    return (int) $stmt->fetchColumn() > 0;
}

    public function getAllUsers()
    {
        $stmt = $this->pdo->prepare("
        SELECT  users.user_id,users.user_name, users.email,users.profile,roles.role_name,users.created_at
        FROM users
        LEFT JOIN roles ON users.role_id = roles.role_id
        ORDER BY users.user_id DESC
    ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);


    }

    //   public function createUser($username, $email, $password, $profile)
// {
//     $sql = "INSERT INTO users
//             (user_name,email,password,profile)
//             VALUES (?,?,?,?)";

    //     $stmt = $this->pdo->prepare($sql);

    //     return $stmt->execute([
//         $username,
//         $email,
//         $password,
//         $profile
//     ]);
// }


    public function getUserByUsername(string $username): ?array
    {
        $sql = "
        SELECT 
            u.user_id,
            u.user_name,
            u.email,
            u.password,
            r.role_name
        FROM Users u
        INNER JOIN Roles r ON u.role_id = r.role_id
        WHERE u.user_name = :user_name
        LIMIT 1
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_name', $username, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }


      public function registerUser($username, $email, $password, $profile = 'default.png', $role_id = 0)
    {
        // Role must exist in the roles table, no silent fallback
        if (!$this->roleExists((int) $role_id)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (user_name, email, password, profile, role_id)
            VALUES (:username, :email, :password, :profile, :role_id)
        ");

        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashedPassword,
            ':profile'  => $profile,
            ':role_id'  => (int) $role_id
        ]);
    }

        // Fetch a user with their role name, for login
   

    // Where each role lands after login (paths relative to the project root)
    public function getDashboardPath(?string $roleName): string
    {
        $paths = [
            'admin'    => 'admin/dashboard.php',
            'hr'       => 'hr/dashboard.php',
            'employer' => 'employer/dashboard.php',
            'agent'    => 'agent/dashboard.php',
            'worker'   => 'worker/dashboard.php',
        ];
        return $paths[strtolower((string) $roleName)] ?? 'index.php';
    }

    // Page guard: call at the top of every protected page
    public static function requireRole(array $allowedRoleNames): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $role = strtolower($_SESSION['role_name'] ?? '');
        $allowed = array_map('strtolower', $allowedRoleNames);

        if (empty($_SESSION['user_id']) || !in_array($role, $allowed, true)) {
            header('Location: /job-search-system/login.php');
            exit;
        }
    }

        public function roleExists(int $role_id): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM roles WHERE role_id = ? LIMIT 1");
        $stmt->execute([$role_id]);
        return (bool) $stmt->fetchColumn();
    }
   

    private const PROFILE_UPLOAD_DIR = __DIR__ . '/../../uploads/profile/';

    /**
     * Saves an uploaded profile image.
     * Returns the new filename, null if no file was chosen, or false on failure.
     */
    public function saveProfileImage(array $file)
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return false;
        }

        if (!is_dir(self::PROFILE_UPLOAD_DIR)) {
            mkdir(self::PROFILE_UPLOAD_DIR, 0755, true);
        }

        $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::PROFILE_UPLOAD_DIR . $filename)) {
            return false;
        }
        return $filename;
    }

    /**
     * Deletes an uploaded profile image file (never the default one).
     */
    public function deleteProfileImage(?string $filename): void
    {
        if (empty($filename) || $filename === 'default.png') {
            return;
        }
        $path = self::PROFILE_UPLOAD_DIR . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }
    public function updateUser($user_id, $username, $email, $profile)
    {
        $stmt = $this->pdo->prepare("
        UPDATE `Users` SET 
            `user_name` = :username,
            `email` = :email,
            `profile` = :profile
        WHERE `user_id` = :user_id
    ");

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':profile', $profile, PDO::PARAM_STR);
        return $stmt->execute();
    }





    public function deleteUser($user_id)
    {
        $sql = "DELETE FROM Users WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([':user_id' => $user_id]);



    }
    public function deleteUserById($user_id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Users WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $user_id]);
    }

    public function updatePassword(string $username, string $password, )
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // encrypyt the password
        $stmt = $this->pdo->prepare("UPDATE Users SET password = :password WHERE user_name = :user_name");
        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getRoles()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM Roles");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Handle error
            echo "Error fetching roles: " . $e->getMessage();
            return [];
        }
    }

        public function getAllRoles(): array
    {
        $stmt = $this->pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateRole(string $username, string $role)
    {
        $stmt = $this->pdo->prepare("UPDATE Users SET role = :role WHERE user_name = :user_name");
        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function deleteRole(string $username, string $role)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Users WHERE user_name = :user_name AND role_id = :role_id");
        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function deleteRoleByName(string $username, string $role)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Users WHERE user_name = :user_name AND role = :role");
        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        return $stmt->execute();

    }
    public function userExists($username, $email)
    {
        $sql = "SELECT user_name, email FROM users 
            WHERE user_name = :user_name OR email = :email LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            return false;

        if ($row['user_name'] === $username)
            return 'username';
        if ($row['email'] === $email)
            return 'email';
    }


    public function updateUserPassword(string $username, string $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // encrypt the password

        $stmt = $this->pdo->prepare("UPDATE Users SET password = :password WHERE user_name = :user_name");

        $stmt->bindParam(':user_name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

        return $stmt->execute();

    }

    public function updateProfileImage(int $user_id, ?string $profileImage): bool
    {
        if (!$profileImage) {
            throw new Exception("Profile image is not provided.");
        }

        $stmt = $this->pdo->prepare(
            "UPDATE Users SET profile = :profile WHERE user_id = :user_id"
        );

        $stmt->execute([
            ':profile' => $profileImage,
            ':user_id' => $user_id
        ]);

        return $stmt->rowCount() > 0;
    }
    public function UserExistsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->pdo->prepare("
        SELECT u.*, r.role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.email = ?
        LIMIT 1
    ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function verifyUser($userId)
    {
        $stmt = $this->pdo->prepare("
        SELECT user_id
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userId)
    {
        $stmt = $this->pdo->prepare("
        SELECT
            u.user_name,
            u.email,
            u.profile,
            r.role_name AS role
        FROM users u
        LEFT JOIN roles r
            ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ");

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}








