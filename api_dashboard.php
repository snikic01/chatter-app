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

    // POPRAVLJENO: Ako podaci ne stignu kroz JSON body (POST), skripta ih bezbedno čita iz URL-a (GET)
    $action       = isset($inputData['action']) ? trim($inputData['action']) : (isset($_GET['action']) ? trim($_GET['action']) : 'list');
    $username     = isset($inputData['username']) ? trim($inputData['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
    $user_id      = isset($inputData['user_id']) ? intval($inputData['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    $post_id      = isset($inputData['post_id']) ? intval($inputData['post_id']) : (isset($_GET['post_id']) ? intval($_GET['post_id']) : 0);
    $comment_id   = isset($inputData['comment_id']) ? intval($inputData['comment_id']) : (isset($_GET['comment_id']) ? intval($_GET['comment_id']) : 0);
    $comment_text = isset($inputData['comment_text']) ? trim($inputData['comment_text']) : (isset($_GET['comment_text']) ? trim($_GET['comment_text']) : '');
    $title        = isset($inputData['title']) ? trim($inputData['title']) : (isset($_GET['title']) ? trim($_GET['title']) : '');
    $content      = isset($inputData['content']) ? trim($inputData['content']) : (isset($_GET['content']) ? trim($_GET['content']) : '');
    $board_color  = isset($inputData['board_color']) ? trim($inputData['board_color']) : (isset($_GET['board_color']) ? trim($_GET['board_color']) : 'standard');


    // Ako nemamo user_id, a imamo username, pronalazimo id korisnika
    if ($user_id <= 0 && !empty($username)) {
        $stmt = $pdo->prepare("
    SELECT 
        nc.id, 
        nc.news_id, 
        nc.user_id, 
        nc.comment_text, 
        nc.created_at, 
        u.username 
    FROM news_comments nc
    JOIN users u ON nc.user_id = u.id
    WHERE nc.news_id = ?
    ORDER BY nc.created_at ASC
");
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
        // POPRAVLJENO: Uvek čitamo username direktno iz baze preko ID-ja radi sigurnosti
    $stmtCheck = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmtCheck->execute([$user_id]);
    $realUsername = $stmtCheck->fetchColumn() ?: '';

    // Admin je isključivo nalog snikic01
    $is_admin = ($realUsername === 'snikic01');


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

        case 'comments_list':
            require_once "dashboard-actions/list_comments.php";
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
