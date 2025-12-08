<?php
// api_zones.php
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php'; 

// [수정 포인트] db.sql에 정의된 컬럼명(zone_geom)으로 변경했습니다.
$sql = "
    SELECT 
        z.id, z.name, z.type, 
        ST_AsGeoJSON(z.zone_geom) as geometry, -- zone_geom 컬럼 사용
        t.species_code,
        COUNT(t.id) as tree_count,     -- 구역 내 수종별 그루 수
        SUM(t.dbh) as total_size       -- (선택) 흉고직경 합계
    FROM zones z
    LEFT JOIN smart_trees t 
        ON ST_Contains(z.zone_geom, t.coordinates) -- 공간 조인 조건 수정
    GROUP BY z.id, t.species_code
    ORDER BY z.id
";

$result = $conn->query($sql);

// 디버깅: 쿼리 에러 시 메시지 출력
if (!$result) {
    die(json_encode(["error" => $conn->error]));
}

$zones = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        
        // 데이터 구조화: Zone ID별로 묶기 (GeoJSON Feature 형식)
        if (!isset($zones[$id])) {
            $zones[$id] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geometry']), // GeoJSON 문자열을 객체로 변환
                'properties' => [
                    'id' => $id,
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'stats' => [], // 수종별 통계 배열
                    'total_trees' => 0
                ]
            ];
        }
        
        // 수종 데이터가 있는 경우(LEFT JOIN 매칭됨) 통계 추가
        if ($row['species_code']) {
            $zones[$id]['properties']['stats'][$row['species_code']] = (int)$row['tree_count'];
            $zones[$id]['properties']['total_trees'] += (int)$row['tree_count'];
        }
    }
}

// 배열의 키(ID)를 제거하고 순수 배열로 반환
echo json_encode(array_values($zones));

$conn->close();
?>
