<?php
// save_polygon.php
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php'; // DB 연결 설정 파일이 같은 폴더에 있어야 합니다.

// 에러 확인을 위한 로그 (필요시 주석 해제)
// file_put_contents('debug_log.txt', print_r($_POST, true));

$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? 'park';
$pathJson = $_POST['path'] ?? '[]';

// 1. 데이터 유효성 검사
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => '구역 이름이 전달되지 않았습니다.']);
    exit;
}

$pathArray = json_decode($pathJson, true);
if (!is_array($pathArray) || count($pathArray) < 3) {
    echo json_encode(['success' => false, 'error' => '좌표 데이터가 없거나 유효하지 않습니다.']);
    exit;
}

// 2. WKT(Well-Known Text) 생성
// 형식: POLYGON((lng lat, lng lat, ...))
$points = [];
foreach ($pathArray as $coord) {
    // 보안을 위해 float로 형변환
    $lng = (float)$coord['lng'];
    $lat = (float)$coord['lat'];
    $points[] = "$lng $lat";
}

// 3. 폴리곤 닫기 (Start Point == End Point)
// MySQL 공간 데이터는 도형이 닫혀있지 않으면 에러가 납니다.
if ($points[0] !== end($points)) {
    $points[] = $points[0];
}

$polygonWKT = "POLYGON((" . implode(", ", $points) . "))";

try {
    // 4. DB 저장 (Prepared Statement)
    $sql = "INSERT INTO zones (name, type, zone_geom) VALUES (?, ?, ST_GeomFromText(?))";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL 준비 실패: " . $conn->error);
    }

    $stmt->bind_param("sss", $name, $type, $polygonWKT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        throw new Exception("쿼리 실행 에러: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>