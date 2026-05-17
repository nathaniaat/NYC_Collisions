<?php
// api/get_graph.php
// Query Neo4j via Bolt (port 7687) menggunakan laudis/neo4j-php-client
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';
use Laudis\Neo4j\ClientBuilder;

// PENTING: fatal_rate di Neo4j disimpan sebagai desimal (0.0–1.0).
// Kita kalikan 100 di sini agar frontend menerima nilai persen (0–100).
$minCount        = (int)($_GET['min_count'] ?? 5);
$minFatal        = (float)($_GET['min_fatal'] ?? 0); // dalam persen dari slider
$minFatalDecimal = $minFatal / 100.0;                // dikonversi ke desimal untuk Cypher

// ── Koneksi Neo4j via Bolt ──────────────────────────────────────────────
try {
    $client = ClientBuilder::create()
        ->withDriver('bolt', 'bolt://neo4j:neo4jneo4j@localhost:7687')
        ->build();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Neo4j connection failed: ' . $e->getMessage()]);
    exit;
}

// ── Cypher Query ────────────────────────────────────────────────────────
$cypher = '
MATCH (f1:Factor)-[r:CO_OCCURS_WITH]-(f2:Factor)
WHERE r.count >= $minCount
  AND r.fatal_rate >= $minFatal
  AND id(f1) < id(f2)
RETURN
    f1.factor_name  AS source_name,
    f2.factor_name  AS target_name,
    r.count         AS crash_count,
    r.fatal_count   AS fatal_count,
    r.fatal_rate    AS fatal_rate
ORDER BY r.fatal_rate DESC
LIMIT 300
';

try {
    $result = $client->run($cypher, [
        'minCount' => $minCount,
        'minFatal' => $minFatalDecimal,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    exit;
}

// ── Susun nodes + links ─────────────────────────────────────────────────
$nodeMap = [];
$links   = [];
$nodeId  = 0;

foreach ($result as $row) {
    $s  = $row->get('source_name') ?? '';
    $t  = $row->get('target_name') ?? '';
    $cc = (int)($row->get('crash_count') ?? 0);
    $fc = (int)($row->get('fatal_count') ?? 0);
    // fatal_rate dari Neo4j adalah desimal → kalikan 100 jadi persen
    $fr = round((float)($row->get('fatal_rate') ?? 0) * 100, 2);

    if (!$s || !$t) continue;

    if (!isset($nodeMap[$s])) {
        $nodeMap[$s] = ['id' => $nodeId++, 'name' => $s, 'freq' => 0];
    }
    if (!isset($nodeMap[$t])) {
        $nodeMap[$t] = ['id' => $nodeId++, 'name' => $t, 'freq' => 0];
    }

    $nodeMap[$s]['freq'] += $cc;
    $nodeMap[$t]['freq'] += $cc;

    $links[] = [
        'source'      => $nodeMap[$s]['id'],
        'target'      => $nodeMap[$t]['id'],
        'source_name' => $s,
        'target_name' => $t,
        'crash_count' => $cc,
        'fatal_count' => $fc,
        'fatal_rate'  => $fr, // sudah dalam persen
    ];
}

echo json_encode([
    'nodes' => array_values($nodeMap),
    'links' => $links,
]);
?>