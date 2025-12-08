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
    const MAX_ZOOM_LEVEL = 5; // 지적도 표시 최대 줌 레벨

    var mapContainer = document.getElementById('map'),
        mapOption = { center: new kakao.maps.LatLng(34.8118, 126.4057), level: 3 };
    var map = new kakao.maps.Map(mapContainer, mapOption);

    var ps = new kakao.maps.services.Places();
    var geocoder = new kakao.maps.services.Geocoder();

    // 현재 선택된(저장할) 구역 관련 변수
    var currentPolygon = null;
    var currentPathData = [];
    var currentMarker = null;

    // 지적편집도(VWorld 오버레이) 관련 변수
    var useDistrict = false;
    var vworldPolygons = []; // 화면에 그려진 파란색 지적도 폴리곤들
    var hoverOverlay = null;
    var isVWorldLoading = false;
    
    var searchMarkers = []; 

    // ==========================================
    // 1. 공통 유틸리티 (마커, 좌표 변환)
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

    // ==========================================
    // 2. 지도 컨트롤 (일반/위성/지적도)
    // ==========================================
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

    // ★★★ [핵심 수정] 지적편집도 토글 기능 통합 ★★★
    function toggleDistrict() {
        useDistrict = !useDistrict;
        var btn = document.getElementById('btnUseDistrict');
        
        if (useDistrict) {
            btn.classList.add('active');
            // 지적도 모드 활성화 시: 줌 레벨 조정 및 데이터 로드 시작
            if(map.getLevel() > 3) map.setLevel(2);
            
            getVWorldDataAll(); // 초기 로드

            // 이벤트 리스너 등록 (드래그, 줌 변경 시 데이터 다시 불러오기)
            kakao.maps.event.addListener(map, 'dragend', debouncedGetData);
            kakao.maps.event.addListener(map, 'zoom_changed', debouncedGetData);
            
            alert("🔲 지적도 모드가 켜졌습니다.\n지도에 구획이 표시되면 원하는 땅을 클릭하세요.");
        } else {
            btn.classList.remove('active');
            // 지적도 모드 비활성화: 오버레이 제거
            removeVWorldPolygons();
            
            // 이벤트 리스너 제거
            kakao.maps.event.removeListener(map, 'dragend', debouncedGetData);
            kakao.maps.event.removeListener(map, 'zoom_changed', debouncedGetData);
        }
    }

    // ==========================================
    // 3. 지도 클릭 이벤트 (일반 모드일 때만 동작)
    // ==========================================
    kakao.maps.event.addListener(map, 'click', function(mouseEvent) {
        // 지적도 모드가 켜져있을 때는 개별 폴리곤 클릭 이벤트가 처리하므로 여기선 무시하거나 보조 역할
        // 하지만 빈 공간(데이터 없음)을 클릭했을 때를 대비해 유지할 수도 있음.
        if (useDistrict) return; 

        var latlng = mouseEvent.latLng;
        processClickLocation(latlng);
        
        // 기존 방식: 클릭 지점 기준으로 VWorld 데이터 1개만 요청 (백업용)
        getSingleVWorldData(latlng.getLng(), latlng.getLat());
    });

    function processClickLocation(latlng) {
        updateMarker(latlng);
        geocoder.coord2Address(latlng.getLng(), latlng.getLat(), function(result, status) {
            if (status === kakao.maps.services.Status.OK) {
                var addr = result[0].address.address_name;
                document.getElementById('zoneName').value = addr;
            }
        });
    }

    // ==========================================
    // 4. VWorld 지적도 (전체 보기 & 단일 선택)
    // ==========================================
    
    // 4-1. Debounce (과도한 API 호출 방지)
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    const debouncedGetData = debounce(getVWorldDataAll, 800);

    // 4-2. 화면 내 모든 지적도 데이터 가져오기 (two.html 로직)
    function getVWorldDataAll() {
        if (!useDistrict) return;
        
        const currentLevel = map.getLevel();
        if (currentLevel > MAX_ZOOM_LEVEL) {
            removeVWorldPolygons();
            return;
        }

        if (isVWorldLoading) return;
        isVWorldLoading = true;
        $('#mapLoading').show();

        var bounds = map.getBounds();
        var sw = bounds.getSouthWest();
        var ne = bounds.getNorthEast();
        var bbox = `${sw.getLng()},${sw.getLat()},${ne.getLng()},${ne.getLat()}`;

        const params = {
            service: 'WFS', version: '2.0.0', request: 'GetFeature',
            typeName: 'lp_pa_cbnd_bubun', srsName: 'EPSG:4326',
            bbox: bbox, output: 'text/javascript', format_options: 'callback:parseVWorldAll',
            exceptions: 'text/javascript', key: VWORLD_KEY
        };

        const url = "https://api.vworld.kr/req/wfs?" + $.param(params);
        
        // 기존 스크립트 제거 후 새로 추가
        $('#vworld-all-script').remove();
        const script = document.createElement('script');
        script.src = url;
        script.id = 'vworld-all-script';
        script.onerror = function() { isVWorldLoading = false; $('#mapLoading').hide(); };
        document.head.appendChild(script);
    }

    window.parseVWorldAll = function(data) {
        isVWorldLoading = false;
        $('#mapLoading').hide();
        removeVWorldPolygons(); // 기존 파란 폴리곤 삭제

        let features = data.features;
        if (!features && data.response) features = data.response.result.featureCollection.features;

        if (!features || features.length === 0) return;

        features.forEach(function(feature) {
            drawVWorldPolygon(feature);
        });
    };

    // 4-3. 지적도 폴리곤 그리기 (파란색 오버레이)
    function drawVWorldPolygon(feature) {
        var geometry = feature.geometry;
        var props = feature.properties;

        if (!geometry || !geometry.coordinates) return;

        var rawPath = [];
        if (geometry.type === 'Polygon') rawPath = geometry.coordinates[0];
        else if (geometry.type === 'MultiPolygon') rawPath = geometry.coordinates[0][0];

        if (rawPath.length < 3) return;

        var path = [];
        rawPath.forEach(pt => path.push(new kakao.maps.LatLng(pt[1], pt[0])));

        // 화면에 보여줄 파란색 폴리곤
        var polygon = new kakao.maps.Polygon({
            map: map, path: path,
            strokeWeight: 1, strokeColor: '#004c80', strokeOpacity: 0.6,
            fillColor: '#fff', fillOpacity: 0.1
        });

        // 호버 이벤트
        kakao.maps.event.addListener(polygon, 'mouseover', function(mouseEvent) {
            polygon.setOptions({ fillColor: '#09f', fillOpacity: 0.4 });
            const jibun = props.jibun || props.addr || '지번모름';
            const content = `<div class="custom-overlay">${jibun}</div>`;
            
            if(hoverOverlay) hoverOverlay.setMap(null);
            hoverOverlay = new kakao.maps.CustomOverlay({
                position: mouseEvent.latLng, content: content, yAnchor: 1
            });
            hoverOverlay.setMap(map);
        });

        kakao.maps.event.addListener(polygon, 'mouseout', function() {
            polygon.setOptions({ fillColor: '#fff', fillOpacity: 0.1 });
            if(hoverOverlay) { hoverOverlay.setMap(null); hoverOverlay = null; }
        });

        // ★ 클릭 이벤트: 이 구역을 "선택" 처리함
        kakao.maps.event.addListener(polygon, 'click', function(mouseEvent) {
            // 1. 빨간색 선택 폴리곤으로 변환
            selectPolygonFromFeature(feature);
            
            // 2. 마커 이동 및 주소 찾기
            var latlng = mouseEvent.latLng; // 클릭한 위치
            updateMarker(latlng);
            
            // 3. 폼 데이터 채우기 (VWorld 속성 활용)
            const addr = props.addr || props.jibun || '주소 정보 없음';
            document.getElementById('zoneName').value = addr;
        });

        vworldPolygons.push(polygon);
    }

    function removeVWorldPolygons() {
        vworldPolygons.forEach(p => p.setMap(null));
        vworldPolygons = [];
        if(hoverOverlay) { hoverOverlay.setMap(null); }
    }

    // 4-4. 선택 처리 (빨간색 폴리곤 생성 및 정보창 업데이트)
    function selectPolygonFromFeature(feature) {
        if (currentPolygon) currentPolygon.setMap(null);
        currentPathData = [];
        $('#selectionInfo').hide();

        var geometry = feature.geometry;
        var props = feature.properties;

        // 좌표 추출
        let rawPath = [];
        if (geometry.type === 'Polygon') rawPath = geometry.coordinates[0];
        else if (geometry.type === 'MultiPolygon') rawPath = geometry.coordinates[0][0];

        let path = [];
        rawPath.forEach(pt => {
            path.push(new kakao.maps.LatLng(pt[1], pt[0]));
            currentPathData.push({lng: pt[0], lat: pt[1]});
        });

        // 빨간색 선택 폴리곤 그리기
        currentPolygon = new kakao.maps.Polygon({
            map: map, path: path,
            strokeWeight: 3, strokeColor: '#ff0000', strokeOpacity: 0.8,
            fillColor: '#ff0000', fillOpacity: 0.3
        });

        // 정보창 업데이트
        const addr = props.addr || props.jibun || props.pnu || '주소 정보 없음';
        const jimokCode = props.jimok_text || props.ldcgdr_nm || '';
        let jimok = "정보없음";
        let badgeClass = "badge-park";

        if (jimokCode) {
            if (jimokCode.includes('도로') || jimokCode === '도') {
                jimok = "도로 (Road)"; badgeClass = "badge-road";
                alert("⚠️ 주의: '도로'를 선택하셨습니다.");
            } else if (jimokCode.includes('공원') || jimokCode === '원') {
                jimok = "공원 (Park)";
            } else {
                jimok = jimokCode;
            }
        }

        $('#infoAddr').text(addr);
        $('#infoJimok').html(`<span class="badge ${badgeClass}">${jimok}</span>`);
        $('#infoArea').text(props.calc_area ? Math.round(props.calc_area) + "㎡" : "정보없음");
        
        // 마커가 있다면 좌표 표시
        if(currentMarker) {
            const pos = currentMarker.getPosition();
            $('#infoCoord').text(`${pos.getLat().toFixed(6)}, ${pos.getLng().toFixed(6)}`);
        }
        
        $('#selectionInfo').fadeIn();
    }

    // 4-5. (구버전) 단일 클릭 시 가져오기 - 일반 지도 모드용
    function getSingleVWorldData(lng, lat) {
        const bbox = `${parseFloat(lng)-0.0001},${parseFloat(lat)-0.0001},${parseFloat(lng)+0.0001},${parseFloat(lat)+0.0001}`;
        const params = {
            service: 'WFS', version: '2.0.0', request: 'GetFeature',
            typeName: 'lp_pa_cbnd_bubun', srsName: 'EPSG:4326',
            bbox: bbox, output: 'text/javascript', format_options: 'callback:parseSingleVWorld',
            key: VWORLD_KEY
        };
        $.getScript("https://api.vworld.kr/req/wfs?" + $.param(params));
    }

    window.parseSingleVWorld = function(data) {
        let features = data.features;
        if (!features && data.response) features = data.response.result.featureCollection.features;
        if (!features || features.length === 0) return;
        
        selectPolygonFromFeature(features[0]);
    };

    // ==========================================
    // 5. 통합 검색
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
                position: new kakao.maps.LatLng(place.y, place.x), map: map
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
        
        // 검색 장소로 이동 후 지적도 데이터 확인
        if(useDistrict) {
            getVWorldDataAll();
        } else {
            getSingleVWorldData(place.x, place.y);
        }
        
        searchMarkers.forEach(marker => marker.setMap(null));
        searchMarkers = [];
    }

    // ==========================================
    // 6. 사진 GPS (EXIF)
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
            
            // 사진 위치로 이동 시 지적도 데이터 로드
            if(useDistrict) getVWorldDataAll();
            else getSingleVWorldData(longitude, latitude);

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
    // 7. 저장 및 초기화
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
        
        // 지적편집도 모드 초기화
        if (useDistrict) toggleDistrict();
        
        map.setCenter(new kakao.maps.LatLng(34.8118, 126.4057));
        map.setLevel(3);
    }
    
    $(document).ready(function() {
        console.log('시스템 초기화 완료');
    });
</script>
</body>
</html>
