<?php
// api_trees.php
header('Content-Type: application/json; charset=utf-8');

// 1. DB 연결
// (특수문자 제거 및 변수명 정리 완료)
$host = 'localhost';
$user = 'im4u798';      // 본인 DB 아이디
$pass = 'dbi73043365k!!';  // 본인 DB 비밀번호
$db   = 'im4u798';      // 본인 DB 이름

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die(json_encode(["error" => "DB Connection Failed: " . $conn->connect_error])); 
}
$conn->set_charset("utf8mb4"); // 한글 깨짐 방지 추가

// 2. 탄소 계산 클래스
class CarbonCalculator {
    private $coefficients = [
        'pine' => ['a' => 0.045, 'b' => 2.41],    // 소나무
        'zelkova' => ['a' => 0.068, 'b' => 2.32], // 느티나무
        'ginkgo' => ['a' => 0.055, 'b' => 2.38],  // 은행나무
        'default' => ['a' => 0.050, 'b' => 2.35]
    ];
    public function calculate($species, $dbh) {
        $coef = $this->coefficients[$species] ?? $this->coefficients['default'];
        $biomass = $coef['a'] * pow($dbh, $coef['b']);
        return round(($biomass * 0.5 * (44/12)), 2); // CO2 kg
    }
}
$calc = new CarbonCalculator();

// 3. 지도 영역 내 나무 조회
// [수정] ST_X는 경도(Lng), ST_Y는 위도(Lat)입니다. 헷갈리지 않게 별칭을 정확히 수정했습니다.
$sql = "SELECT id, species_code, dbh, height, status, 
        ST_X(coordinates) as lng, 
        ST_Y(coordinates) as lat 
        FROM smart_trees"; 

$result = $conn->query($sql);

$features = [];
$total_trees = 0;
$total_carbon = 0;

if ($result && $result->num_rows > 0) {
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
                // [수정] GeoJSON 표준 순서는 [경도(x), 위도(y)] 입니다.
                'coordinates' => [(float)$row['lng'], (float)$row['lat']]
            ],
            'properties' => [
                'id' => (int)$row['id'],
                'species' => $row['species_code'],
                'dbh' => (float)$row['dbh'],
                'height' => (float)$row['height'], // 높이 정보도 반환하면 좋습니다
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
        // 차 1대 연간 배출량 약 2.5톤 = 2500kg
        'car_equivalent' => number_format($total_carbon / 2500, 1) 
    ],
    'features' => $features
], JSON_UNESCAPED_UNICODE); // 한글 깨짐 방지 옵션

$conn->close();
?>
