<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$borough    = $_GET['borough']    ?? 'ALL';
$hour       = isset($_GET['hour']) ? (int)$_GET['hour'] : null;
$day        = $_GET['day']        ?? null;
$year_start = (int)($_GET['year_start'] ?? 2020);
$year_end   = (int)($_GET['year_end']   ?? 2025);

<<<<<<< HEAD
$conn = pg_connect("host=localhost port=5433 dbname=nyc_collisions user=postgres password=admin");
=======
$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
>>>>>>> ef53d672a91ff09e58c2ba8db667f4cb2f627f01
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$params  = [$year_start, $year_end, $borough];
$filters = "t.col_year BETWEEN \$1 AND \$2 AND (\$3 = 'ALL' OR l.borough = \$3)";

if ($hour !== null) {
    $params[]  = $hour;
    $filters  .= " AND t.col_hour = \$" . count($params);
}
if ($day !== null) {
    $params[]  = $day;
    $filters  .= " AND t.day_of_week = \$" . count($params);
}

$sql = "
    SELECT
        fc_lookup.factor_name,
        COUNT(*) AS crash_count,
        SUM(CASE WHEN fc.people_killed > 0 THEN 1 ELSE 0 END) AS fatal_count,
        ROUND(
            SUM(CASE WHEN fc.people_killed > 0 THEN 1 ELSE 0 END)::numeric
            / NULLIF(COUNT(*), 0) * 100, 1
        ) AS fatal_pct
    FROM fact_vehicles fv
    JOIN factor_collision fc_lookup ON fv.factor_id    = fc_lookup.factor_id
    JOIN fact_collisions  fc        ON fv.collision_id = fc.collision_id
    JOIN location_collision l       ON fc.location_id  = l.location_id
    JOIN time_collision t           ON fc.time_id      = t.time_id
    WHERE $filters
    GROUP BY fc_lookup.factor_name
    HAVING COUNT(*) >= 10
    ORDER BY crash_count DESC
    LIMIT 15
";

$result = pg_query_params($conn, $sql, $params);
echo json_encode(pg_fetch_all($result) ?: []);
pg_close($conn);