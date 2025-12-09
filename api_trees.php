<?php
// api_trees.php
header('Content-Type: application/json; charset=utf-8');

// 1. DB 연결
$host = 'localhost';
$user = 'im4u798';      
$pass = 'dbi73043365k!!';  
$db   = 'im4u798'; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die(json_encode(["error" => "DB Connection Failed: " . $conn->connect_error])); 
}
$conn->set_charset("utf8mb4");

// 2. 탄소 계산기
class CarbonCalculator {
    private $coefficients = [
        'pine' => ['a' => 0.045, 'b' => 2.41],
        'zelkova' => ['a' => 0.068, 'b' => 2.32],
        'ginkgo' => ['a' => 0.055, 'b' => 2.38],
        'default' => ['a' => 0.050, 'b' => 2.35]
    ];
    public function calculate($species, $dbh) {
        $coef = $this->coefficients[$species] ?? $this->coefficients['default'];
        $biomass = $coef['a'] * pow($dbh, $coef['b']);
        return round(($biomass * 0.5 * (44/12)), 2);
    }
}
$calc = new CarbonCalculator();

// 3. 데이터 조회
$sql = "SELECT id, species_code, dbh, height, status, image_path, tree_count,
        ST_X(coordinates) as lng, 
        ST_Y(coordinates) as lat 
        FROM smart_trees"; 

$result = $conn->query($sql);

$features = [];
$total_trees = 0;
$total_carbon = 0;

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // [수정] 강제로 1로 만드는 로직 제거. DB에 0이면 0으로 계산.
        $count = (int)$row['tree_count']; 
        
        // 유효한 나무만 집계 (수량이 0 이상인 경우만)
        if ($count > 0) {
            $total_trees += $count;
            $unit_co2 = $calc->calculate($row['species_code'], $row['dbh']);
            $total_co2 = $unit_co2 * $count;
            $total_carbon += $total_co2;

            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$row['lng'], (float)$row['lat']]
                ],
                'properties' => [
                    'id' => (int)$row['id'],
                    'species' => $row['species_code'],
                    'dbh' => (float)$row['dbh'],
                    'height' => (float)$row['height'],
                    'count' => $count,
                    'co2' => $total_co2,
                    'status' => $row['status'],
                    'image_path' => $row['image_path']
                ]
            ];
        }
    }
}

echo json_encode([
    'type' => 'FeatureCollection',
    'stats' => [
        'total_trees' => number_format($total_trees),
        'total_carbon' => number_format($total_carbon, 2),
        'car_equivalent' => number_format($total_carbon / 2500, 1)
    ],
    'features' => $features
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>