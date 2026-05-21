<?php
// friend-actions/list_friends.php

// 1. Prihvaćeni prijatelji (proveravamo obe kombinacije jer prijateljstvo važi obostrano)
$query = "SELECT u.id, u.username, (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online 
          FROM users u
          JOIN friends f ON (f.user_id = u.id AND f.friend_id = ? AND f.status = 'accepted')
                         OR (f.user_id = ? AND f.friend_id = u.id AND f.status = 'accepted')";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id]);
$friends = $stmt->fetchAll();

// 2. Zahtevi koji čekaju tvoje odobrenje (Neko je poslao zahtev tebi, pa si ti friend_id)
$queryRequests = "SELECT u.id, u.username FROM users u
                  JOIN friends f ON f.user_id = u.id
                  WHERE f.friend_id = ? AND f.status = 'pending'";
$stmtReq = $pdo->prepare($queryRequests);
$stmtReq->execute([$user_id]);
$requests = $stmtReq->fetchAll();

echo json_encode(["success" => true, "friends" => $friends, "requests" => $requests]);
exit;
