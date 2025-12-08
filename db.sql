CREATE TABLE `smart_trees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,    
    `species_code` VARCHAR(50) NOT NULL COMMENT '수종 코드 (예: pine, zelkova)',
    `tree_name` VARCHAR(100) COMMENT '나무 별명/관리명',    
    `dbh` DECIMAL(5,2) NOT NULL COMMENT '흉고직경(cm): 가슴 높이 지름',
    `height` DECIMAL(5,2) NOT NULL COMMENT '수고(m): 나무 키',
    `age_class` INT COMMENT '수령(나이)',
    -- [위치 데이터] 공간 인덱스 적용 (핵심!)
    `coordinates` POINT NOT NULL COMMENT '위도/경도 (WGS84)',    
    `status` ENUM('healthy', 'warning', 'danger', 'dead') DEFAULT 'healthy' COMMENT '건강 상태',
    `last_checked_at` DATETIME COMMENT '마지막 점검일',    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- [인덱스] 지도 검색 속도를 위한 필수 설정
    SPATIAL INDEX `sp_index_coordinates` (`coordinates`), 
    INDEX `idx_species` (`species_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `zones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '구역명',
    `type` VARCHAR(50) COMMENT '구역 타입 (park, street, forest)',
    
    -- [핵심 변경] POLYGON 대신 GEOMETRY 사용 (선, 면 모두 저장 가능)
    `zone_geom` GEOMETRY NOT NULL  COMMENT '공간 데이터',
    
    -- [추가된 관리 항목]
    `total_distance` INT DEFAULT 0 COMMENT '거리(m) - 가로수길용',
    `est_tree_count` INT DEFAULT 0 COMMENT '예상 수량 - 가로수길용',
    `start_point` VARCHAR(100),
    `end_point` VARCHAR(100),
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 공간 인덱스 (검색 속도 필수)
    SPATIAL INDEX `sp_index_geom` (`zone_geom`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- [테스트 데이터] 서울 시청 광장 주변을 '시청 공원'으로 설정
INSERT INTO `zones` (`name`, `type`, `zone_geom`)
VALUES (
    '서울 시청 광장 숲',
    'park',
    ST_GeomFromText(
        'POLYGON((126.9778 37.5668, 126.9784 37.5668, 126.9784 37.5662, 126.9778 37.5662, 126.9778 37.5668))'
    )
);

INSERT INTO zones (name, type, zone_geom, total_distance, est_tree_count) 
VALUES (
    '용해-연산 가로수길', 
    'street', 
    ST_GeomFromText('LINESTRING(126.3 34.8, 126.4 34.9)'),
    2300, -- 2.3km
    280   -- 280그루
);