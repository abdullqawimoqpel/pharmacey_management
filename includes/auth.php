<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

class Auth
{
    private $db;
    private $table_name = "users";

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($username, $password)
    {
        // Sanitize inputs
        $username = trim($username);

        // Check login attempts
        if ($this->isLockedOut($username)) {
            throw new Exception("Too many failed login attempts. Please try again later.");
        }

        $query = "SELECT user_id, username, password, full_name, role, is_active 
                  FROM " . $this->table_name . " 
                  WHERE username = :username 
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user['is_active']) {
                throw new Exception("Your account has been deactivated.");
            }

            if (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                session_regenerate_id(true);

                // Update last login
                $this->updateLastLogin($user['user_id']);

                // Clear failed attempts
                $this->clearFailedAttempts($username);

                return true;
            }
        }

        // Failed login
        $this->recordFailedAttempt($username);
        throw new Exception("Invalid username or password.");
    }

    private function isLockedOut($username)
    {
        $query = "SELECT COUNT(*) as attempts 
                  FROM login_attempts 
                  WHERE username = :username 
                  AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt($username)
    {
        $query = "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
    }

    private function clearFailedAttempts($username)
    {
        $query = "DELETE FROM login_attempts WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
    }

    private function updateLastLogin($user_id)
    {
        $query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
    }

    public function isLoggedIn()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)
        ) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public function redirectIfNotLoggedIn($redirect_url = '../auth/login.php')
    {
        if (!$this->isLoggedIn()) {
            header("Location: $redirect_url");
            exit;
        }
    }

    public function hasRole($required_role)
    {
        if (!isset($_SESSION['role'])) {
            return false;
        }

        $roles_hierarchy = [
            'admin' => 3,
            'pharmacist' => 2,
            'assistant' => 1
        ];

        $user_role_level = $roles_hierarchy[$_SESSION['role']] ?? 0;
        $required_role_level = $roles_hierarchy[$required_role] ?? 0;

        return $user_role_level >= $required_role_level;
    }

    public function requireRole($required_role)
    {
        if (!$this->hasRole($required_role)) {
            http_response_code(403);
            die("Access denied. Insufficient permissions.");
        }
    }

    public function logout()
    {
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    public function changePassword($user_id, $current_password, $new_password)
    {
        $query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect.");
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
        $update_stmt = $this->db->prepare($update_query);

        return $update_stmt->execute([$new_password_hash, $user_id]);
    }
}
