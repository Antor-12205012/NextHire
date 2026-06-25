<?php
// NextHire - Authentication and Async Request Handler

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle Registration
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $company_selection = $_POST['company_selection'] ?? 'new';
        $company_name = trim($_POST['company_name'] ?? '');
        $join_company_id = $_POST['join_company_id'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['auth_error'] = "All fields are required.";
            header('Location: register.php');
            exit;
        }
        
        try {
            // Check if email already registered
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['auth_error'] = "Email address already registered.";
                header('Location: register.php');
                exit;
            }
            
            $company_id = null;
            
            if ($company_selection === 'new') {
                if (empty($company_name)) {
                    $_SESSION['auth_error'] = "Company name is required to register a new organization.";
                    header('Location: register.php');
                    exit;
                }
                // Insert new company
                $stmt = $pdo->prepare("INSERT INTO companies (name) VALUES (?)");
                $stmt->execute([$company_name]);
                $company_id = $pdo->lastInsertId();
            } else {
                if (empty($join_company_id)) {
                    $_SESSION['auth_error'] = "Please select a company to join.";
                    header('Location: register.php');
                    exit;
                }
                $company_id = intval($join_company_id);
            }
            
            // Hash password and insert user
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $role = ($company_selection === 'new') ? 'admin' : 'recruiter';
            
            $stmt = $pdo->prepare("INSERT INTO users (company_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$company_id, $name, $email, $password_hash, $role]);
            
            // Get company details
            $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            
            // Log user in automatically
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $_SESSION['company_id'] = $company_id;
            $_SESSION['company_name'] = $company['name'];
            
            header('Location: dashboard.php');
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['auth_error'] = "Database registration error: " . $e->getMessage();
            header('Location: register.php');
            exit;
        }
    }
    
    // 2. Handle Login
    elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['auth_error'] = "Email and Password are required.";
            header('Location: login.php');
            exit;
        }
        
        try {
            // Find user and join company info
            $stmt = $pdo->prepare("
                SELECT u.*, c.name AS company_name 
                FROM users u 
                JOIN companies c ON u.company_id = c.id 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session details
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['company_name'] = $user['company_name'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $_SESSION['auth_error'] = "Invalid email or password.";
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['auth_error'] = "Database login error: " . $e->getMessage();
            header('Location: login.php');
            exit;
        }
    }
    
    // 3. Handle Posting Internal Message
    elseif ($action === 'post_message') {
        require_auth();
        
        $job_id = intval($_POST['job_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if ($job_id > 0 && !empty($message)) {
            // Verify recruiter owns this job's company
            if (check_job_ownership($pdo, $job_id, $_SESSION['company_id'])) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO messages (job_id, user_id, message) VALUES (?, ?, ?)");
                    $stmt->execute([$job_id, $_SESSION['user_id'], $message]);
                    
                    // Redirect back to job details page with chat active
                    header("Location: job-details.php?id=" . $job_id . "&tab=chat");
                    exit;
                } catch (PDOException $e) {
                    die("Failed to save comment: " . $e->getMessage());
                }
            }
        }
        
        header("Location: dashboard.php");
        exit;
    }
}

// GET Requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // 4. Handle Logout
    if ($action === 'logout') {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // 5. Async Fetch Chat Messages for Polling
    elseif ($action === 'get_messages') {
        header('Content-Type: application/json');
        if (!is_logged_in()) {
            echo json_encode(['error' => 'Unauthenticated']);
            exit;
        }
        
        $job_id = intval($_GET['job_id'] ?? 0);
        $last_id = intval($_GET['last_id'] ?? 0);
        
        if ($job_id > 0) {
            if (check_job_ownership($pdo, $job_id, $_SESSION['company_id'])) {
                try {
                    // Fetch messages along with sender names
                    $stmt = $pdo->prepare("
                        SELECT m.id, m.message, m.created_at, m.user_id, u.name AS sender_name
                        FROM messages m
                        JOIN users u ON m.user_id = u.id
                        WHERE m.job_id = ? AND m.id > ?
                        ORDER BY m.id ASC
                    ");
                    $stmt->execute([$job_id, $last_id]);
                    $messages = $stmt->fetchAll();
                    
                    // Format dates nicely
                    foreach ($messages as &$msg) {
                        $msg['created_at'] = date('M d, g:i a', strtotime($msg['created_at']));
                    }
                    
                    echo json_encode([
                        'messages' => $messages,
                        'current_user_id' => $_SESSION['user_id']
                    ]);
                    exit;
                } catch (PDOException $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            }
        }
        
        echo json_encode(['messages' => []]);
        exit;
    }
}
?>
