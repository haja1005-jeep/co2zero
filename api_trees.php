<?php
// api_trees.php
header('Content-Type: application/json; charset=utf-8');

// 1. DB 연결 (설정에 맞게 수정하세요)
$host = 'localhost';
$user = 'im4u798';      // 본인 DB 아이디
$pass = 'dbi73043365k!!';  // 본인 DB 비밀번호
$db   = 'im4u798'; // 본인 DB 이름


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die(json_encode(["error" => "DB Connection Failed"])); }

// 2. 탄소 계산 클래스 (앞서 만든 로직 통합)
class CarbonCalculator {
    private $coefficients = [
        'pine' => ['a' => 0.045, 'b' => 2.41], // 소나무
        'zelkova' => ['a' => 0.068, 'b' => 2.32], // 느티나무
        'ginkgo' => ['a' => 0.055, 'b' => 2.38], // 은행나무
        'default' => ['a' => 0.050, 'b' => 2.35]
    ];
    public function calculate($species, $dbh) {
        $coef = $this->coefficients[$species] ?? $this->coefficients['default'];
        $biomass = $coef['a'] * pow($dbh, $coef['b']);
        return round(($biomass * 0.5 * (44/12)), 2); // CO2 kg
    }
}
$calc = new CarbonCalculator();

// 3. 지도 영역 내 나무 조회 (전체 조회로 간소화. 실제론 WHERE 절에 ST_Contains 사용 권장)
$sql = "SELECT id, species_code, dbh, height, status, 
        ST_X(coordinates) as lat, ST_Y(coordinates) as lng 
        FROM smart_trees"; 
$result = $conn->query($sql);

$features = [];
$total_trees = 0;
$total_carbon = 0;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_trees++;
        // 탄소량 계산
        $co2 = $calc->calculate($row['species_code'], $row['dbh']);
        $total_carbon += $co2;

        // GeoJSON Feature 생성
        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [(float)$row['lng'], (float)$row['lat']] // GeoJSON은 [경도, 위도] 순서
            ],
            'properties' => [
                'id' => $row['id'],
                'species' => $row['species_code'],
                'dbh' => $row['dbh'],
                'co2' => $co2,
                'status' => $row['status']
            ]
        ];
    }
}

// 4. 최종 JSON 응답
echo json_encode([
    'type' => 'FeatureCollection',
    'stats' => [ // 대시보드용 통계
        'total_trees' => number_format($total_trees),
        'total_carbon' => number_format($total_carbon, 2),
        'car_equivalent' => number_format($total_carbon / 2500, 1) // 차 1대 연간 배출량 약 2.5톤 가정
    ],
    'features' => $features
]);

$conn->close();
?>