<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 - 가로수길 등록</title>
    <style>
        body { margin:0; padding:0; display:flex; height:100vh; font-family: 'Noto Sans KR', sans-serif; }
        #sidebar { width: 320px; background: #f8f9fa; padding: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); z-index: 10; overflow-y: auto;}
 

       /* [추가] 지도 컨테이너를 relative로 설정 (버튼 위치 잡기 위해) */
        #map { flex: 1; position: relative; }
        
        /* [추가] 지도 컨트롤 버튼 스타일 */
        .map-controls {
            position: absolute; top: 10px; right: 10px; z-index: 20;
            display: flex; gap: 5px;
        }
        .map-btn {
            background: white; border: 1px solid #999; padding: 8px 12px;
            border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .map-btn.active { background: #4263eb; color: white; border-color: #4263eb; }

        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 14px; margin-top: 5px; }
        .btn-primary { background: #004c80; }
        .btn-danger { background: #dc3545; margin-top: 10px; }
        .info-box { background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        .highlight { color: #d63384; font-weight: bold; }

	
    </style>
</head>
<body>

<div id="sidebar">
    <h2>🌳 가로수길 등록</h2>
    <div class="info-box">
        지도에 점을 클릭하여 경로를 그리세요.<br>
        마우스 오른쪽 클릭하면 그리기 종료!
    </div>

    <div class="form-group">
        <label>구역 이름 (예: 중앙로 가로수길)</label>
        <input type="text" id="zoneName" placeholder="이름 입력">
    </div>

    <div class="form-group">
        <label>구역 타입</label>
        <select id="zoneType">
            <option value="street">가로수길 (Street)</option>
            <option value="park">공원 산책로 (Park Path)</option>
        </select>
    </div>

    <div class="form-group">
        <label>총 거리 (자동 계산)</label>
        <input type="text" id="totalDistance" readonly>
    </div>

    <div class="form-group">
        <label>예상 수목 수 (자동 추산: 8m 간격)</label>
        <input type="number" id="estTreeCount">
    </div>

    <button class="btn btn-primary" onclick="saveRoute()">💾 DB에 저장하기</button>
    <button class="btn btn-danger" onclick="resetMap()">🔄 초기화</button>
</div>

<div id="map">
    <div class="map-controls">
        <button class="map-btn active" id="btnRoadmap" onclick="setMapType('roadmap')">일반지도</button>
        <button class="map-btn" id="btnSkyview" onclick="setMapType('skyview')">위성지도</button>
    </div>
</div>

<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=257fdd3647dd6abdb05eae8681106514"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    var mapContainer = document.getElementById('map'),
        mapOption = { 
            center: new kakao.maps.LatLng(34.8118, 126.4057), // 목포 중심
            level: 3 
        };

    var map = new kakao.maps.Map(mapContainer, mapOption);

    // [추가] 지도 타입 변경 함수
    function setMapType(maptype) {
        var roadmapBtn = document.getElementById('btnRoadmap');
        var skyviewBtn = document.getElementById('btnSkyview'); 
        
        if (maptype === 'roadmap') {
            map.setMapTypeId(kakao.maps.MapTypeId.ROADMAP);
            roadmapBtn.classList.add('active');
            skyviewBtn.classList.remove('active');
        } else {
            map.setMapTypeId(kakao.maps.MapTypeId.HYBRID);
            roadmapBtn.classList.remove('active');
            skyviewBtn.classList.add('active');
        }
    }

    // 그리기 관련 변수
    var drawingFlag = false; // 그리기 상태
    var moveLine; // 마우스 따라다니는 선
    var clickLine; // 확정된 선
    var distanceOverlay; // 거리 정보 오버레이
    var dots = []; // 찍은 점들
    var pathData = []; // DB로 보낼 좌표 배열

    // 1. 지도 클릭 이벤트 (그리기 시작/추가)
    kakao.maps.event.addListener(map, 'click', function(mouseEvent) {
        var clickPosition = mouseEvent.latLng;

        if (!drawingFlag) {
            // 그리기 시작
            drawingFlag = true;
            deleteClickLine();
            deleteDistnce();
            deleteCircleDot();

            // 선 생성
            clickLine = new kakao.maps.Polyline({
                map: map,
                path: [clickPosition],
                strokeWeight: 3,
                strokeColor: '#db4040',
                strokeOpacity: 1,
                strokeStyle: 'solid'
            });

            moveLine = new kakao.maps.Polyline({
                strokeWeight: 3,
                strokeColor: '#db4040',
                strokeOpacity: 0.5,
                strokeStyle: 'solid'
            });

            displayCircleDot(clickPosition, 0);

        } else {
            // 점 추가
            var path = clickLine.getPath();
            path.push(clickPosition);
            clickLine.setPath(path);

            var distance = Math.round(clickLine.getLength());
            displayCircleDot(clickPosition, distance);
        }
    });

    // 2. 마우스 무브 이벤트 (선 미리보기)
    kakao.maps.event.addListener(map, 'mousemove', function(mouseEvent) {
        if (drawingFlag) {
            var mousePosition = mouseEvent.latLng;
            var path = clickLine.getPath();
            var movepath = [path[path.length-1], mousePosition];
            
            moveLine.setPath(movepath);
            moveLine.setMap(map);
        }
    });

    // 3. 우클릭 이벤트 (그리기 종료)
    kakao.maps.event.addListener(map, 'rightclick', function(mouseEvent) {
        if (drawingFlag) {
            moveLine.setMap(null);
            moveLine = null;
            
            var path = clickLine.getPath();
            
            // 2개 이상 점이 찍혀야 유효
            if (path.length > 1) {
                drawingFlag = false;
                
                // 최종 데이터 계산
                var distance = Math.round(clickLine.getLength()); // 미터 단위
                
                // UI 업데이트
                $('#totalDistance').val(distance + 'm');
                // 가로수는 보통 8m 간격으로 식재 (양쪽이면 *2, 여기선 편도로 계산)
                $('#estTreeCount').val(Math.floor(distance / 8)); 

                // 좌표 데이터 추출 (WGS84)
                pathData = path.map(function(latlng) {
                    return {
                        lat: latlng.getLat(),
                        lng: latlng.getLng()
                    };
                });
                
                alert('경로 그리기가 완료되었습니다. 저장 버튼을 누르세요.');
            }
        }
    });

    // 점(Dot) 표시 함수
    function displayCircleDot(position, distance) {
        var circleOverlay = new kakao.maps.CustomOverlay({
            content: '<span class="dot"></span>',
            position: position,
            zIndex: 1
        });
        circleOverlay.setMap(map);
        dots.push({circle: circleOverlay});
    }

    // 초기화 함수
    function deleteClickLine() {
        if (clickLine) { clickLine.setMap(null); clickLine = null; }
    }
    function deleteDistnce() {
        if (distanceOverlay) { distanceOverlay.setMap(null); distanceOverlay = null; }
    }
    function deleteCircleDot() {
        for (var i = 0; i < dots.length; i++) {
            if (dots[i].circle) dots[i].circle.setMap(null);
        }
        dots = [];
    }
    function resetMap() {
        deleteClickLine();
        deleteDistnce();
        deleteCircleDot();
        if(moveLine) moveLine.setMap(null);
        drawingFlag = false;
        $('#totalDistance').val('');
        $('#estTreeCount').val('');
        pathData = [];
    }

    // --- [데이터 저장 요청] ---
    function saveRoute() {
        var name = $('#zoneName').val();
        var type = $('#zoneType').val();
        var distance = parseInt($('#totalDistance').val());
        var estCount = $('#estTreeCount').val();

        if (!name) { alert('구역 이름을 입력하세요.'); return; }
        if (pathData.length < 2) { alert('지도에 경로를 그려주세요.'); return; }

        // AJAX 전송
        $.ajax({
            url: 'save_route.php',
            type: 'POST',
            data: {
                name: name,
                type: type,
                distance: distance,
                est_tree_count: estCount,
                path: JSON.stringify(pathData) // 좌표 배열을 JSON 문자열로 변환
            },
            success: function(response) {
                var res = JSON.parse(response);
                if (res.success) {
                    alert('✅ 성공적으로 저장되었습니다!');
                    resetMap();
                    $('#zoneName').val('');
                } else {
                    alert('❌ 저장 실패: ' + res.error);
                }
            },
            error: function() {
                alert('서버 통신 오류가 발생했습니다.');
            }
        });
    }

    // 스타일 주입 (점 모양)
    var style = document.createElement('style');
    style.innerHTML = '.dot { overflow:hidden; float:left; width:12px; height:12px; background: url(https://t1.daumcdn.net/localimg/localimages/07/mapapidoc/mini_circle.png); }';
    document.head.appendChild(style);
</script>
</body>
</html>
