<?php
// friend-actions/list_friends.php

// 1. Prihvaćeni prijatelji
$query = "SELECT u.id, u.username, (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online 
          FROM users u
          JOIN friends f ON (f.sender_id = u.id AND f.receiver_id = ? AND f.status = 'accepted')
                         OR (f.sender_id = ? AND f.receiver_id = u.id AND f.status = 'accepted')";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id]);
$friends = $stmt->fetchAll();

// 2. Zahtevi koji čekaju tvoje odobrenje (Neko ti je poslao zahtev)
$queryRequests = "SELECT u.id, u.username FROM users u
                  JOIN friends f ON f.sender_id = u.id
                  WHERE f.receiver_id = ? AND f.status = 'pending'";
$stmtReq = $pdo->prepare($queryRequests);
$stmtReq.execute([$user_id]);
$requests = $stmtReq->fetchAll();

echo json_encode(["success" => true, "friends" => $friends, "requests" => $requests]);
exit;
