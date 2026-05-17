<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$collision_id = intval($_GET['id'] ?? 0);
if (!$collision_id) { echo json_encode(['error' => 'Missing id']); exit; }

require_once 'autoload.php';

$client = new MongoDB\Client("mongodb://mongodb:mongodb@localhost:27017/");
$col    = $client->pdds->collision;

$doc = $col->findOne(['collision_id' => $collision_id]);
echo json_encode($doc, JSON_UNESCAPED_UNICODE);