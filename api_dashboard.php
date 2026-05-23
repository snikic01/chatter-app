<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
ini_set('display_errors', 0); error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    $action       = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username     = isset($inputData['username']) ? trim($inputData['username']) : '';
    $user_id      = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;
    $post_id      = isset($inputData['post_id']) ? intval($inputData['post_id']) : 0;
    $comment_id   = isset($inputData['comment_id']) ? intval($inputData['comment_id']) : 0;
    $comment_text = isset($inputData['comment_text']) ? trim($inputData['comment_text']) : '';
    $title        = isset($inputData['title']) ? trim($inputData['title']) : '';
    $content      = isset($inputData['content']) ? trim($inputData['content']) : '';
    $board_color  = isset($inputData['board_color']) ? trim($inputData['board_color']) : 'standard';

    // Ako nemamo user_id, a imamo username, pronalazimo id korisnika
    if ($user_id <= 0 && !empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik je obavezan!"]);
        exit;
    }

    // Osvežavamo vreme aktivnosti čim se otvori ili osveži dashboard
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$user_id]);

    // POPRAVLJENO: Pošto kolona 'role' ne postoji, proveravamo da li je ulogovan nalog 'snikic01'
    // Ukoliko tvoj nalog ima administratorska prava, biće označen kao admin
    if (empty($username) && $user_id > 0) {
        $stmtName = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmtName->execute([$user_id]);
        $username = $stmtName->fetchColumn() ?: '';
    }
    
    $is_admin = ($username === 'snikic01');

    // Modularno rutiranje ka fajlovima u folderu dashboard-actions
    switch ($action) {
        case 'list':
            require_once "dashboard-actions/list_posts.php";
            break;

        case 'like_toggle':
            require_once "dashboard-actions/toggle_like.php";
            break;

        case 'comments_list':
            require_once "dashboard-actions/list_comments.php";
            break;

        case 'comment_add':
            require_once "dashboard-actions/add_comment.php";
            break;

        case 'comment_edit':
            require_once "dashboard-actions/edit_comment.php";
            break;

        case 'comment_delete':
            require_once "dashboard-actions/delete_comment.php";
            break;

        case 'admin_logs':
            if (!$is_admin) {
                echo json_encode(["success" => false, "message" => "Nemate administratorska prava!"]);
                exit;
            }
            require_once "dashboard-actions/view_admin_logs.php";
            break;

        case 'post_add':
            if ($username !== 'snikic01' && !$is_admin) {
                echo json_encode(["success" => false, "message" => "Nemate ovlašćenje za kreiranje objava!"]);
                exit;
            }
            require_once "dashboard-actions/add_post.php";
            break;

        case 'post_edit':
            if ($username !== 'snikic01' && !$is_admin) {
                echo json_encode(["success" => false, "message" => "Nemate ovlašćenje za izmenu objava!"]);
                exit;
            }
            require_once "dashboard-actions/edit_post.php";
            break;

        case 'post_delete':
            if ($username !== 'snikic01' && !$is_admin) {
                echo json_encode(["success" => false, "message" => "Nemate ovlašćenje za brisanje objava!"]);
                exit;
            }
            require_once "dashboard-actions/delete_post.php";
            break;

        default:
            echo json_encode(["success" => false, "message" => "Nepoznata akcija!"]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
