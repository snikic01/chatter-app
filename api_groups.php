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

    // UNIVERZALNI PARSER: Čita i JSON body i GET/POST parametre čak i ako ih ruter sakrije
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $user_id  = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;

    // Ako je telefon poslao username, a nemamo user_id, saznaćemo ga bezbedno
    if ($user_id <= 0 && !empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    // Stroga provera: Ako nemamo nikakav parametar korisnika, tek tada prekidamo skriptu
    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "User ID ili Korisnik je obavezan!", "groups" => []]);
        exit;
    }

    // ================= 1. LISTA SVIH GRUPA (Identično kao na veb sajtu) =================
    if ($action === 'list') {
        $query = "SELECT id, name, owner_id, (owner_id = ?) as is_owner 
                  FROM chat_groups 
                  ORDER BY id ASC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $groups = $stmt->fetchAll();

        $outputGroups = [];
        foreach ($groups as $group) {
            // Bezbedno računamo broj nepročitanih poruka preko NOT EXISTS (imuno na NULL vrednosti u bazi)
            $unreadQuery = "SELECT COUNT(*) FROM private_messages pm
                            WHERE pm.group_id = ? AND pm.sender_id != ?
                            AND NOT EXISTS (
                                SELECT 1 FROM group_message_seen gms 
                                WHERE gms.message_id = pm.id AND gms.user_id = ?
                            )";
            $unreadStmt = $pdo->prepare($unreadQuery);
            $unreadStmt->execute([$group['id'], $user_id, $user_id]);
            $unreadCount = (int)$unreadStmt->fetchColumn();

            $outputGroups[] = [
                "id" => (int)$group['id'],
                "name" => $group['name'],
                "is_owner" => (bool)$group['is_owner'],
                "unread_count" => $unreadCount
            ];
        }

        echo json_encode(["success" => true, "groups" => $outputGroups]);
        exit;
    }

    // ================= SVE NAPREDNE AKCIJE (KREIRANJE, BRISANJE, LEAVE) =================

    // --- 2. KREIRANJE NOVE GRUPE ---
    if ($action === 'create') {
        $group_name = isset($inputData['group_name']) ? trim($inputData['group_name']) : '';
        if (empty($group_name)) {
            echo json_encode(["success" => false, "message" => "Ime grupe je obavezno!"]);
            exit;
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO chat_groups (name, owner_id) VALUES (?, ?)");
        $stmt->execute([$group_name, $user_id]);
        $group_id = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt->execute([$group_id, $user_id]);
        $pdo->commit();
        echo json_encode(["success" => true, "message" => "Grupa kreirana!"]);
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
        echo json_encode(["success" => true, "message" => "Grupa je obrisana!"]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
