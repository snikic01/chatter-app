<?php
// friends-actions/friend_suggestion.php

// Tražimo korisnike koji nisu trenutno ulogovani
// i koji uopšte ne postoje u tabeli friends sa tvojim ID-jem (ni kao pending, ni kao accepted)
$query = "SELECT u.id, u.username, (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online 
          FROM users u
          WHERE u.id != ? 
          AND u.id NOT IN (
              SELECT friend_id FROM friends WHERE user_id = ?
              UNION
              SELECT user_id FROM friends WHERE friend_id = ?
          )
          LIMIT 5";

$stmt = $pdo->prepare($query);
// Prosleđujemo ulogovani user_id tri puta za sva tri znaka pitanja
$stmt->execute([$user_id, $user_id, $user_id]);
$suggestions = $stmt->fetchAll();

echo json_encode(["success" => true, "suggestions" => $suggestions]);
exit;
