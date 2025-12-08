<?php
// api_zones.php
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php'; // DB 연결 부분 분리했다고 가정

// 1. 구역(Zone) 정보와 구역 내 수종별 통계를 한방에 가져오는 쿼리
// GROUP BY를 이용해 구역별, 수종별로 묶습니다.
$sql = "
    SELECT 
        z.id, z.name, z.type, 
        ST_AsGeoJSON(z.area_polygon) as geometry, -- 폴리곤 좌표
        t.species_code,
        COUNT(t.id) as tree_count,     -- 수종별 그루 수
        SUM(t.dbh) as total_size       -- (선택) 흉고직경 합계 등
    FROM zones z
    LEFT JOIN smart_trees t 
        ON ST_Contains(z.area_polygon, t.coordinates) -- 공간 조인 (핵심!)
    GROUP BY z.id, t.species_code
    ORDER BY z.id
";

$result = $conn->query($sql);

$zones = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        
        // 데이터 구조화: Zone ID별로 묶기
        if (!isset($zones[$id])) {
            $zones[$id] = [
                'type' => 'Feature',
                'geometry' => json_decode($row['geometry']), // GeoJSON 폴리곤
                'properties' => [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'stats' => [], // 수종별 통계가 들어갈 배열
                    'total_trees' => 0
                ]
            ];
        }
        
        // 수종 데이터가 있는 경우에만 추가
        if ($row['species_code']) {
            $zones[$id]['properties']['stats'][$row['species_code']] = (int)$row['tree_count'];
            $zones[$id]['properties']['total_trees'] += (int)$row['tree_count'];
        }
    }
}

// 배열 인덱스 리셋하여 JSON 배열로 출력
echo json_encode(array_values($zones));
?>