<?php
// save_route.php
include 'db_connect.php';

// POST 데이터 수신
$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? 'street';
$distance = $_POST['distance'] ?? 0;
$est_tree_count = $_POST['est_tree_count'] ?? 0;
$pathJson = $_POST['path'] ?? '[]';

// 유효성 검사
if (empty($name) || empty($pathJson)) {
    echo json_encode(['success' => false, 'error' => '필수 데이터 누락']);
    exit;
}

$pathArray = json_decode($pathJson, true);
if (count($pathArray) < 2) {
    echo json_encode(['success' => false, 'error' => '경로 좌표가 부족합니다.']);
    exit;
}

// 1. WKT(Well-Known Text) 형식 생성
// 형식: LINESTRING(lng lat, lng lat, ...)
// 주의: MySQL 공간 함수는 보통 (경도 위도) 순서입니다! (X Y)
$points = [];
foreach ($pathArray as $coord) {
    $lat = $coord['lat'];
    $lng = $coord['lng'];
    $points[] = "$lng $lat"; // 공백으로 구분
}

$lineStringWKT = "LINESTRING(" . implode(", ", $points) . ")";

// 2. DB Insert (Prepared Statement 사용)
// ST_GeomFromText는 MySQL 5.7 호환 표준 함수입니다.
$sql = "INSERT INTO zones (name, type, zone_geom, total_distance, est_tree_count) 
        VALUES (?, ?, ST_GeomFromText(?), ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

// 바인딩: s(string), s(string), s(string-WKT), i(int), i(int)
$stmt->bind_param("sssii", $name, $type, $lineStringWKT, $distance, $est_tree_count);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>