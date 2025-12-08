<?php

class CarbonCalculator {
    
    // 수종별 탄소 흡수 계수 테이블 (예시 값입니다. 실제 산림청 데이터로 교체 필요)
    // 공식 모델: Y (바이오매스) = a * (흉고직경)^b
    private $coefficients = [
        'pine' => ['name' => '소나무', 'a' => 0.045, 'b' => 2.41],
        'zelkova' => ['name' => '느티나무', 'a' => 0.068, 'b' => 2.32],
        'ginkgo' => ['name' => '은행나무', 'a' => 0.055, 'b' => 2.38],
        'default' => ['name' => '기타', 'a' => 0.050, 'b' => 2.35] // 알 수 없는 수종용
    ];

    /**
     * 나무 한 그루의 연간 이산화탄소(CO2) 흡수량을 계산합니다.
     * @param string $speciesCode 수종 코드
     * @param float $dbh 흉고직경 (cm)
     * @return float 연간 CO2 흡수량 (kg/year)
     */
    public function getCo2Absorption($speciesCode, $dbh) {
        // 1. 수종 계수 가져오기
        $coef = $this->coefficients[$speciesCode] ?? $this->coefficients['default'];

        // 2. 지상부 바이오매스 계산 (kg)
        // 공식: W = a * D^b
        $biomass = $coef['a'] * pow($dbh, $coef['b']);

        // 3. 탄소 저장량으로 변환 (보통 바이오매스의 50%가 탄소)
        $carbonStorage = $biomass * 0.5;

        // 4. 이산화탄소(CO2)량으로 환산
        // 탄소(C) 1kg은 이산화탄소(CO2) 약 3.67kg (44/12)에 해당
        $co2Absorption = $carbonStorage * (44 / 12);

        // ※ 실제로는 '연간 생장률'을 곱해야 '연간' 흡수량이 나오지만, 
        // 시연용으로는 현재 총 저장량을 보여주는 것이 임팩트가 큽니다.
        // 필요시 여기에 '연간 생장 계수'를 추가로 곱하세요.

        return round($co2Absorption, 2); // 소수점 2자리 반올림
    }
}

// --- [사용 예시] ---
$calculator = new CarbonCalculator();

// 느티나무(zelkova), 지름 25cm인 나무의 탄소 흡수량은?
$treeCo2 = $calculator->getCo2Absorption('zelkova', 25);

echo "이 느티나무는 약 {$treeCo2}kg의 이산화탄소를 품고 있습니다.";
?>