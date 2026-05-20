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

    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';

    // ================= 1. LISTA SVIH GRUPA (Koristi ispravnu kolonu owner_id) =================
    if ($action === 'list') {
        $user_id = 0;
        
        if (!empty($username)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user_id = $stmt->fetchColumn() ?: 0;
        }

        // PROMENJENO: created_by zamenjeno sa owner_id prema strukturi tvoje baze
        $query = "SELECT id, name, owner_id, (owner_id = ?) as is_owner 
                  FROM chat_groups 
                  ORDER BY id ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        echo json_encode([
            "success" => true, 
            "groups" => $stmt->fetchAll()
        ]);
        exit;
    }

    // ================= STROGA PROVERA ZA NAPREDNE AKCIJE =================
    if (empty($username)) {
        echo json_encode(["success" => false, "message" => "Korisnik je obavezan za ovu akciju!"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user_id = $stmt->fetchColumn();
    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "Korisnik ne postoji!"]);
        exit;
    }

    // --- 2. KREIRANJE NOVE GRUPE ---
    if ($action === 'create') {
        $group_name = isset($inputData['group_name']) ? trim($inputData['group_name']) : '';
        if (empty($group_name)) {
            echo json_encode(["success" => false, "message" => "Ime grupe je obavezno!"]);
            exit;
        }

        $pdo->beginTransaction();
        // PROMENJENO: owner_id
        $stmt = $pdo->prepare("INSERT INTO chat_groups (name, owner_id) VALUES (?, ?)");
        $stmt->execute([$group_name, $user_id]);
        $group_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt->execute([$group_id, $user_id]);
        
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Grupa uspešno kreirana!"]);
        exit;
    }

    // --- 3. NAPUŠTANJE GRUPE ---
    if ($action === 'leave') {
        $group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
        $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$group_id, $user_id]);
        echo json_encode(["success" => true, "message" => "Napustili ste grupu!"]);
        exit;
    }

    // --- 4. BRISANJE GRUPE OD STRANE VLASNIKA ---
    if ($action === 'delete') {
        $group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
        
        // PROMENJENO: owner_id
        $stmt = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $owner_id = $stmt->fetchColumn();

        if ($owner_id != $user_id) {
            echo json_encode(["success" => false, "message" => "Nemate ovlašćenje da obrišete ovu grupu!"]);
            exit;
        }

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$group_id]);
        $pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")->execute([$group_id]);
        $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Grupa je trajno obrisana!"]);
        exit;
    }

    // --- 5. PREGLED ČLANOVA GRUPE ---
    if ($action === 'members') {
        $group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
        
        if ($group_id <= 0) {
            echo json_encode(["success" => false, "message" => "Nevalidan ID grupe!"]);
            exit;
        }

        $query = "SELECT u.username FROM users u 
                  JOIN group_members gm ON u.id = gm.user_id 
                  WHERE gm.group_id = ? 
                  ORDER BY u.username ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$group_id]);
        $members = $stmt->fetchAll();

        echo json_encode([
            "success" => true,
            "members" => $members
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
