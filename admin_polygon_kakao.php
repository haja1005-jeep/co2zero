<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 - 스마트 지적도 구역 등록</title>
    <style>
        body { margin:0; padding:0; display:flex; height:100vh; font-family: 'Noto Sans KR', sans-serif; }
        
        /* 사이드바 스타일 */
        #sidebar { width: 380px; background: #f8f9fa; padding: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); z-index: 10; overflow-y: auto; display: flex; flex-direction: column;}
        
        /* 지도 스타일 */
        #map { flex: 1; position: relative; }
        
        /* 컨트롤 버튼 박스 (지도 위에 띄움) */
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

        /* 입력 폼 스타일 */
        .form-group { margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 20px; }
        .form-group:last-child { border-bottom: none; }
        
        label { display: block; margin-bottom: 8px; font-weight: bold; font-size: 14px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;}
        
        .btn { width: 100%; padding: 12px; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 14px; margin-top: 5px; font-weight: bold; transition: 0.2s;}
        .btn-search { background: #004c80; }
        .btn-photo { background: #fd7e14; }
        .btn-primary { background: #28a745; margin-top: 10px;}
        .btn-danger { background: #dc3545; margin-top: 10px; }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        /* 선택된 구역 정보 박스 */
        #selectionInfo {
            background: #e7f5ff; border: 1px solid #74c0fc;
            padding: 15px; border-radius: 5px; margin-bottom: 15px;
            display: none;
        }
        #selectionInfo h4 { margin: 0 0 10px 0; color: #1c7ed6; font-size: 15px;}
        #selectionInfo p { margin: 5px 0; font-size: 13px; color: #495057;}
        .badge { display: inline-block; padding: 3px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; color: white;}
        .badge-road { background: #868e96; } /* 도로 */
        .badge-park { background: #20c997; } /* 공원, 대지 */

        #placesList { list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; background: #fff; display: none; margin-top: 5px;}
        #placesList li { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; font-size: 13px;}
        #placesList li:hover { background: #e3f2fd; }
        #placesList li strong { display: block; margin-bottom: 3px; color: #333; }
        #placesList li span { color: #666; font-size: 12px; }

        /* 로딩 표시 */
        .loading { display: none; text-align: center; padding: 10px; color: #666; font-size: 13px; }
        .loading.show { display: block; }
        
        /* 전체 화면 로딩 (지적도용) */
        .map-loading {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            z-index: 999; background: rgba(0,0,0,0.7); color: white;
            padding: 10px 20px; border-radius: 5px; display: none; font-size: 14px;
        }

        /* 알림 메시지 */
        .notice { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 12px; color: #856404; }
        
        /* 호버 오버레이 */
        .custom-overlay {
            position: absolute; background: rgba(0, 0, 0, 0.85); color: white;
            padding: 8px 12px; border-radius: 5px; font-size: 13px;
            white-space: nowrap; pointer-events: none;
            transform: translate(-50%, -100%); margin-top: -10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3); z-index: 1001;
        }
        .custom-overlay::after {
            content: ''; position: absolute; bottom: -5px; left: 50%;
            transform: translateX(-50%); width: 0; height: 0;
            border-left: 5px solid transparent; border-right: 5px solid transparent;
            border-top: 5px solid rgba(0, 0, 0, 0.85);
        }
    </style>
</head>
<body>

<div id="sidebar">
    <h2 style="margin-top:0;">🏞️ 공원 및 생활숲 구역 등록</h2>
    
    <div class="form-group">
        <label>🔍 명칭/주소 검색</label>
        <div style="display:flex; gap:5px;">
            <input type="text" id="keyword" placeholder="예: 용당 어린이공원" onkeypress="if(event.keyCode==13) searchUnified();">
            <button class="btn btn-search" style="width: 60px; margin-top:0;" onclick="searchUnified()">검색</button>
        </div>
        <div class="loading" id="searchLoading">🔍 검색 중...</div>
        <ul id="placesList"></ul>
    </div>

    <div class="form-group">
        <label>📍 위치 지정 도구</label>
        <div class="notice">
            💡 <b>지적편집도 모드 사용법</b><br>
            1. 지도 상단 <b>[🔲 지적편집도]</b> 클릭<br>
            2. 지적도 구획이 나타나면 원하는 땅을 클릭하세요.<br>
            3. 자동으로 정보가 입력됩니다.
        </div>
        
        <button class="btn btn-photo" onclick="document.getElementById('photoInput').click()">📸 사진 올려서 찾기</button>
        <input type="file" id="photoInput" accept="image/*" onchange="handlePhoto(this)" style="display:none;">
        <div class="loading" id="photoLoading">📸 사진 분석 중...</div>
    </div>

    <div id="selectionInfo">
        <h4>✅ 선택된 구역 정보</h4>
        <p><b>주소:</b> <span id="infoAddr">-</span></p>
        <p><b>지목(추정):</b> <span id="infoJimok">-</span></p>
        <p><b>면적(VWorld):</b> <span id="infoArea">-</span></p>
        <p><b>좌표:</b> <span id="infoCoord">-</span></p>
    </div>

    <div class="form-group">
        <label>구역 이름</label>
        <input type="text" id="zoneName" placeholder="검색 또는 지도 클릭 시 자동 입력">
        
        <label style="margin-top:10px;">구역 타입</label>
        <select id="zoneType">
            <option value="park">공원 (Park)</option>
            <option value="forest">생활숲 (Forest)</option>
            <option value="zone">일반 구역 (Zone)</option>
        </select>

        <button class="btn btn-primary" onclick="savePolygon()">💾 확인 및 저장하기</button>
        <button class="btn btn-danger" onclick="resetMap()">🔄 초기화</button>
    </div>
</div>

<div id="map">
    <div class="map-loading" id="mapLoading">데이터 불러오는 중...</div>
    <div class="map-controls">
        <button class="map-btn active" id="btnRoadmap" onclick="setMapType('roadmap')">일반지도</button>
        <button class="map-btn" id="btnSkyview" onclick="setMapType('skyview')">위성지도</button>
        <button class="map-btn" id="btnUseDistrict" onclick="toggleDistrict()">🔲 지적편집도</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exif-js"></script> 
<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=257fdd3647dd6abdb05eae8681106514&libraries=services"></script>

<script>
    // ==========================================
    // 0. 전역 설정
    // ==========================================
    const VWORLD_KEY = 'ACEB012E-C384-3176-BC45-4D4CAE466B1E'; 
    const TREE_MARKER_SRC = 'https://cdn-icons-png.flaticon.com/512/489/489969.png'; 
    
    var mapContainer = document.getElementById('map'),
        mapOption = { center: new kakao.maps.LatLng(34.8118, 126.4057), level: 3 };
    var map = new kakao.maps.Map(mapContainer, mapOption);

    var ps = new kakao.maps.services.Places();
    var geocoder = new kakao.maps.services.Geocoder();

    var currentPolygon = null;
    var currentPathData = [];
    var currentMarker = null;
    var useDistrict = false;
    var searchMarkers = []; 

    // ==========================================
    // 1. 공통 유틸리티 (마커, 지도 타입)
    // ==========================================
    function updateMarker(position) {
        if (currentMarker) { currentMarker.setMap(null); }

        var imageSize = new kakao.maps.Size(35, 35); 
        var imageOption = { offset: new kakao.maps.Point(17, 35) }; 
        var markerImage = new kakao.maps.MarkerImage(TREE_MARKER_SRC, imageSize, imageOption);

        currentMarker = new kakao.maps.Marker({
            position: position,
            map: map,
            image: markerImage
        });
        map.panTo(position);
    }

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

    function toggleDistrict() {
        useDistrict = !useDistrict;
        var btn = document.getElementById('btnUseDistrict');
        
        if (useDistrict) {
            map.addOverlayMapTypeId(kakao.maps.MapTypeId.USE_DISTRICT);
            btn.classList.add('active');
            if(map.getLevel() > 4) map.setLevel(3); // 지적도가 잘 보이게 줌인
        } else {
            map.removeOverlayMapTypeId(kakao.maps.MapTypeId.USE_DISTRICT);
            btn.classList.remove('active');
        }
    }

    // ==========================================
    // 2. 지도 클릭 이벤트 (데이터 요청)
    // ==========================================
    kakao.maps.event.addListener(map, 'click', function(mouseEvent) {
        var latlng = mouseEvent.latLng;
        
        // 1. 마커 찍기
        updateMarker(latlng);
        
        // 2. 주소 가져오기 (이름 자동입력용)
        geocoder.coord2Address(latlng.getLng(), latlng.getLat(), function(result, status) {
            if (status === kakao.maps.services.Status.OK) {
                var addr = result[0].address.address_name;
                document.getElementById('zoneName').value = addr;
            }
        });

        // 3. VWorld 지적도 데이터 요청 (폴리곤 따기용)
        getVWorldData(latlng.getLng(), latlng.getLat());
    });

    function getVWorldData(lng, lat) {
        // 클릭한 지점 주변의 데이터를 요청
        const bbox = `${parseFloat(lng)-0.0001},${parseFloat(lat)-0.0001},${parseFloat(lng)+0.0001},${parseFloat(lat)+0.0001}`;
        const params = {
            service: 'WFS', version: '2.0.0', request: 'GetFeature',
            typeName: 'lp_pa_cbnd_bubun', srsName: 'EPSG:4326',
            bbox: bbox, output: 'text/javascript', format_options: 'callback:parseVWorldData',
            key: VWORLD_KEY
        };
        const url = "https://api.vworld.kr/req/wfs?" + $.param(params);
        
        $('#vworld-script').remove();
        const script = document.createElement('script');
        script.src = url;
        script.id = 'vworld-script';
        script.onerror = function() { alert("⚠️ VWorld 서버 연결 오류"); };
        document.head.appendChild(script);
    }

    // ==========================================
    // 3. VWorld 데이터 응답 처리 (핵심 로직)
    // ==========================================
    window.parseVWorldData = function(data) {
        // 기존 폴리곤 삭제
        if (currentPolygon) currentPolygon.setMap(null); 
        currentPathData = [];
        $('#selectionInfo').hide();

        let features = data.features;
        if (!features && data.response) features = data.response.result.featureCollection.features;

        if (!features || features.length === 0) {
            console.log("선택된 위치에 지적도 데이터 없음");
            return;
        }

        // 클릭한 위치 가져오기
        const markerPos = currentMarker.getPosition();
        const clickLng = markerPos.getLng();
        const clickLat = markerPos.getLat();

        // ★ [핵심] 클릭한 점을 포함하는 정확한 필지 찾기 (Ray Casting)
        let selectedFeature = null;

        for (const f of features) {
            const g = f.geometry;
            let ring = null;

            if (g.type === 'Polygon') {
                ring = g.coordinates[0];
            } else if (g.type === 'MultiPolygon') {
                ring = g.coordinates[0][0];
            }

            if (!ring) continue;

            // 좌표 포맷 변환 ([lng, lat] 배열 -> 객체 배열)
            const polygonRing = ring.map(p => ({ x: p[0], y: p[1] }));
            
            // 점이 폴리곤 안에 있는지 검사
            if (isPointInPolygon({ x: clickLng, y: clickLat }, polygonRing)) {
                selectedFeature = f;
                break; // 찾았으면 루프 종료
            }
        }

        // 못 찾았으면 첫 번째 데이터 사용 (Fallback)
        if (!selectedFeature) {
            selectedFeature = features[0];
        }

        const feature = selectedFeature;
        const props = feature.properties;
        const geometry = feature.geometry;

        // 정보창 표시
        const addr = props.addr || props.jibun || props.pnu || '주소 정보 없음';
        const jimokCode = props.jimok_text || props.ldcgdr_nm || '';
        
        let jimok = "정보없음";
        let isRoad = false;
        let badgeClass = "badge-park";

        if (jimokCode) {
            if (jimokCode.includes('도로') || jimokCode === '도') {
                jimok = "도로 (Road)"; isRoad = true; badgeClass = "badge-road";
            } else if (jimokCode.includes('공원') || jimokCode === '원') {
                jimok = "공원 (Park)";
            } else if (jimokCode.includes('전') || jimokCode.includes('답')) {
                jimok = "전/답 (Field)";
            } else {
                jimok = jimokCode;
            }
        } else {
            if (addr.includes('도') && !addr.includes('동')) { 
                jimok = "도로 (Road)"; isRoad = true; badgeClass = "badge-road";
            }
        }

        $('#infoAddr').text(addr);
        $('#infoJimok').html(`<span class="badge ${badgeClass}">${jimok}</span>`);
        $('#infoArea').text(props.calc_area ? Math.round(props.calc_area) + "㎡" : "정보없음");
        $('#infoCoord').text(`${clickLat.toFixed(6)}, ${clickLng.toFixed(6)}`);
        $('#selectionInfo').fadeIn();

        if (isRoad) alert("⚠️ 주의: '도로'를 선택하셨습니다.\n공원을 등록하려면 도로 안쪽 녹지를 클릭하세요.");

        // ★ [핵심] 빨간색 폴리곤 그리기
        let rawPath = [];
        if (geometry.type === 'Polygon') rawPath = geometry.coordinates[0];
        else if (geometry.type === 'MultiPolygon') rawPath = geometry.coordinates[0][0];

        let path = [];
        rawPath.forEach(pt => {
            // 카카오맵 그리기용
            path.push(new kakao.maps.LatLng(pt[1], pt[0]));
            // DB 저장용 (경도, 위도)
            currentPathData.push({lng: pt[0], lat: pt[1]});
        });

        currentPolygon = new kakao.maps.Polygon({
            map: map,
            path: path,
            strokeWeight: 3, 
            strokeColor: '#ff0000', // 🔴 빨간색 테두리
            strokeOpacity: 0.8, 
            fillColor: '#ff0000',   // 🔴 빨간색 채우기
            fillOpacity: 0.3
        });
    };

    // [보조 함수] 점이 다각형 안에 있는지 검사 (Ray Casting 알고리즘)
    function isPointInPolygon(p, polygon) {
        let isInside = false;
        let minX = polygon[0].x, maxX = polygon[0].x;
        let minY = polygon[0].y, maxY = polygon[0].y;
        
        for (let n = 1; n < polygon.length; n++) {
            let q = polygon[n];
            minX = Math.min(q.x, minX);
            maxX = Math.max(q.x, maxX);
            minY = Math.min(q.y, minY);
            maxY = Math.max(q.y, maxY);
        }

        if (p.x < minX || p.x > maxX || p.y < minY || p.y > maxY) {
            return false;
        }

        let i = 0, j = polygon.length - 1;
        for (i, j; i < polygon.length; j = i++) {
            if ( (polygon[i].y > p.y) != (polygon[j].y > p.y) &&
                    p.x < (polygon[j].x - polygon[i].x) * (p.y - polygon[i].y) / (polygon[j].y - polygon[i].y) + polygon[i].x ) {
                isInside = !isInside;
            }
        }
        return isInside;
    }

    // ==========================================
    // 4. 통합 검색
    // ==========================================
    function searchUnified() {
        const keyword = $('#keyword').val().trim();
        if (!keyword) { alert('검색어를 입력해주세요.'); return; }

        $('#searchLoading').addClass('show');
        $('#placesList').hide().empty();
        searchMarkers.forEach(marker => marker.setMap(null));
        searchMarkers = [];

        ps.keywordSearch(keyword, function(data, status) {
            $('#searchLoading').removeClass('show');
            if (status === kakao.maps.services.Status.OK) {
                displaySearchResults(data);
            } else {
                alert('검색 결과가 없습니다.');
            }
        });
    }

    function displaySearchResults(places) {
        const listEl = $('#placesList');
        listEl.empty();

        places.forEach((place) => {
            const li = $('<li>').html(`<strong>${place.place_name}</strong><span>${place.address_name}</span>`);
            li.on('click', function() { selectSearchPlace(place); });
            listEl.append(li);

            const marker = new kakao.maps.Marker({
                position: new kakao.maps.LatLng(place.y, place.x),
                map: map
            });
            searchMarkers.push(marker);
            kakao.maps.event.addListener(marker, 'click', function() { selectSearchPlace(place); });
        });
        listEl.fadeIn();
    }

    function selectSearchPlace(place) {
        $('#placesList').hide();
        const position = new kakao.maps.LatLng(place.y, place.x);
        
        updateMarker(position);
        $('#zoneName').val(place.place_name);
        getVWorldData(place.x, place.y); // 검색 위치의 지적도 데이터 요청
        
        searchMarkers.forEach(marker => marker.setMap(null));
        searchMarkers = [];
    }

    // ==========================================
    // 5. 사진 GPS (EXIF)
    // ==========================================
    function handlePhoto(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        $('#photoLoading').addClass('show');

        EXIF.getData(file, function() {
            const lat = EXIF.getTag(this, 'GPSLatitude');
            const lon = EXIF.getTag(this, 'GPSLongitude');
            const latRef = EXIF.getTag(this, 'GPSLatitudeRef');
            const lonRef = EXIF.getTag(this, 'GPSLongitudeRef');
            $('#photoLoading').removeClass('show');

            if (!lat || !lon) { alert('⚠️ 사진에 GPS 정보가 없습니다.'); return; }

            const latitude = convertDMSToDD(lat[0], lat[1], lat[2], latRef);
            const longitude = convertDMSToDD(lon[0], lon[1], lon[2], lonRef);
            const position = new kakao.maps.LatLng(latitude, longitude);

            updateMarker(position);
            getVWorldData(longitude, latitude);
            
            geocoder.coord2Address(longitude, latitude, function(result, status) {
                if (status === kakao.maps.services.Status.OK) {
                    $('#zoneName').val(result[0].address.address_name);
                }
            });
            alert('✅ 사진 위치를 찾았습니다!');
        });
        input.value = '';
    }

    function convertDMSToDD(d, m, s, dir) {
        let dd = d + m/60 + s/3600;
        if (dir === 'S' || dir === 'W') dd *= -1;
        return dd;
    }

    // ==========================================
    // 6. 저장 및 초기화
    // ==========================================
    function savePolygon() {
        const name = $('#zoneName').val().trim();
        const type = $('#zoneType').val();

        if (!name) { alert("❌ 구역 이름을 입력해주세요."); return; }
        if (currentPathData.length < 3) { alert("❌ 선택된 구역이 없습니다."); return; }

        if (!confirm(`[${name}] 구역을 저장하시겠습니까?`)) return;

        $.ajax({
            url: 'save_polygon.php',
            type: 'POST',
            data: { name: name, type: type, path: JSON.stringify(currentPathData) },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert("✅ 저장 성공!");
                    resetMap();
                } else {
                    alert("❌ 저장 실패: " + response.error);
                }
            },
            error: function(xhr) { alert("❌ 서버 오류 발생"); }
        });
    }

    function resetMap() {
        if (currentPolygon) currentPolygon.setMap(null);
        if (currentMarker) currentMarker.setMap(null);
        searchMarkers.forEach(marker => marker.setMap(null));
        
        currentPolygon = null;
        currentMarker = null;
        searchMarkers = [];
        currentPathData = [];
        
        $('#zoneName').val('');
        $('#keyword').val('');
        $('#placesList').hide().empty();
        $('#selectionInfo').hide();
        map.setCenter(new kakao.maps.LatLng(34.8118, 126.4057));
        map.setLevel(3);
    }
    
    $(document).ready(function() {
        console.log('스마트 지적도 구역 등록 시스템 초기화 완료');
    });
</script>
</body>
</html>