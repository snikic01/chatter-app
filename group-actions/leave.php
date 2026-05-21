<?php
// group-actions/leave.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;

$pdo->beginTransaction();

// 1. Proveravamo ko je trenutni vlasnik te grupe u bazi
$stmt = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$current_owner = (int)$stmt->fetchColumn();

// 2. Brišemo trenutnog korisnika iz tabele group_members
$stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);

// 3. Ako je korisnik koji izlazi zapravo bio vlasnik te grupe, vršimo nasleđivanje
if ($current_owner === $user_id) {
    $stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? ORDER BY user_id ASC LIMIT 1");
    $stmt->execute([$group_id]);
    $next_owner = $stmt->fetchColumn();

    if ($next_owner) {
        // Postavljamo novog pronađenog člana kao novog vlasnika grupe
        $stmt = $pdo->prepare("UPDATE chat_groups SET owner_id = ? WHERE id = ?");
        $stmt->execute([$next_owner, $group_id]);
    } else {
        // Ako u grupi više nema niti jednog jedinog člana, trajno brišemo i grupu i njene poruke
        $pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")->execute([$group_id]);
        $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
    }
}

$pdo->commit();
echo json_encode(["success" => true, "message" => "Napustili ste grupu!"]);
exit;
