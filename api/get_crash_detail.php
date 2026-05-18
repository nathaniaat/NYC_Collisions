<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$collision_id = intval($_GET['id'] ?? 0);
if (!$collision_id) { echo json_encode(['error' => 'Missing id']); exit; }

require_once 'autoload.php';

<<<<<<< HEAD
$client = new MongoDB\Client("mongodb://nath:150206@localhost:27017/");
$col    = $client->nyc_collisions->crash_events;
=======
$client = new MongoDB\Client("mongodb://mongodb:mongodb@localhost:27017/");
$col    = $client->collision->crash_events;
>>>>>>> ef53d672a91ff09e58c2ba8db667f4cb2f627f01

$doc = $col->findOne(['collision_id' => $collision_id]);
echo json_encode($doc, JSON_UNESCAPED_UNICODE);