<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = pg_connect("host=localhost port=5433 dbname=nyc_collisions user=postgres password=admin");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$sql = "
    SELECT
        l.borough,
        COUNT(*) AS total_crash,
        SUM(fc.people_killed) AS total_killed,
        ROUND(
            SUM(fc.people_killed)::numeric / NULLIF(COUNT(*), 0) * 1000, 2
        ) AS fatality_rate_per_1000
    FROM fact_collisions fc
    JOIN location_collision l ON fc.location_id = l.location_id
    WHERE l.borough IS NOT NULL AND l.borough != 'Unknown'
    GROUP BY l.borough
    ORDER BY total_killed DESC
";

$result = pg_query($conn, $sql);
echo json_encode(pg_fetch_all($result) ?: []);
pg_close($conn);