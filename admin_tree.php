<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 - 개별 수목 등록 (검색 기능 추가)</title>
    <style>
        body { margin:0; padding:0; display:flex; height:100vh; font-family: 'Noto Sans KR', sans-serif; }
        
        #sidebar { width: 350px; background: #f8f9fa; padding: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); z-index: 10; overflow-y: auto; display: flex; flex-direction: column;}
        #map { flex: 1; position: relative; }
        
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

        .form-group { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .form-group:last-child { border-bottom: none; }
        
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;}
        
        .btn { width: 100%; padding: 12px; border: none; border-radius: 4px; color: white; cursor: pointer; font-size: 14px; margin-top: 5px; font-weight: bold; transition: 0.2s;}
        .btn-primary { background: #28a745; }
        .btn-warning { background: #fd7e14; display: none; } 
        .btn-danger { background: #dc3545; margin-top: 5px; } 
        .btn-reset { background: #6c757d; margin-top: 5px; }
        .btn:hover { opacity: 0.9; }

        .coord-box {
            background: #e9ecef; padding: 10px; border-radius: 4px; 
            font-size: 12px; color: #555; margin-bottom: 10px; text-align: center;
        }

        /* 범례 스타일 */
        .legend {
            position: absolute; bottom: 20px; right: 10px; z-index: 20;
            background: rgba(255,255,255,0.9); padding: 10px; border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); font-size: 12px;
        }
        .legend-item { display: flex; align-items: center; margin-bottom: 5px; }
        .color-box { width: 15px; height: 15px; margin-right: 8px; display: inline-block; border-radius: 3px;}
        
        /* ★ 검색 결과 리스트 스타일 추가 */
        #searchResult {
            list-style: none; padding: 0; margin: 5px 0 15px 0;
            max-height: 150px; overflow-y: auto;
            border: 1px solid #ddd; border-radius: 4px; background: white;
            display: none; /* 초기엔 숨김 */
        }
        #searchResult li {
            padding: 8px 10px; border-bottom: 1px solid #eee; cursor: pointer; font-size: 13px;
        }
        #searchResult li:hover { background: #e3f2fd; }
        #searchResult li:last-child { border-bottom: none; }
        .badge { display: inline-block; font-size: 10px; padding: 2px 5px; border-radius: 3px; color: white; margin-right: 5px;}
        .bg-park { background: #28a745; }
        .bg-street { background: #6f42c1; }

        /* 모바일 최적화 */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            #map { height: 45vh; flex: none; order: 1; }
            #sidebar { width: 100%; flex: 1; order: 2; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); padding: 15px; }
            .map-controls { top: auto; bottom: 10px; right: 10px; }
            .legend { display: none; }
            input, select, .btn { height: 45px; font-size: 16px; }
            h2 { font-size: 1.2rem; margin-bottom: 10px; }
        }
    </style>
</head>
<body>

<div id="sidebar">
    <h2 id="formTitle" style="margin-top:0;">🌲 개별 수목 심기</h2>
    
    <div class="form-group" style="border-bottom: 2px solid #ddd;">
        <label>🔍 구역/가로수길 찾기</label>
        <div style="display:flex; gap:5px;">
            <input type="text" id="searchKeyword" placeholder="예: 용당, 중앙로" onkeypress="if(event.key==='Enter') searchZone()">
            <button class="btn" style="width:60px; margin-top:0; background:#004c80;" onclick="searchZone()">이동</button>
        </div>
        <ul id="searchResult"></ul>
    </div>

    <div class="form-group">
        <label>📍 선택된 위치 (자동)</label>
        <div class="coord-box" id="coordDisplay">지도를 클릭하세요</div>
        <input type="hidden" id="treeId">
        <input type="hidden" id="treeLat">
        <input type="hidden" id="treeLng">
        <input type="text" id="address" placeholder="주소 (자동 입력)" readonly style="background:#f1f3f5;">
    </div>
    
    <div class="form-group">
        <label>📸 사진 (자동 리사이징)</label>
        <input type="file" id="photo" accept="image/*">
        <div id="previewArea" style="margin-top:5px; display:none;">
            <img id="currentPhoto" src="" style="width:100px; height:100px; object-fit:cover; border-radius:5px;">
            <p style="font-size:11px; color:blue;">* 현재 등록된 사진</p>
        </div>
    </div>
    
    <div class="form-group">
        <label>🌲 수종 선택</label>
        <select id="species">
            <option value="pine">소나무 (Pine)</option>
            <option value="zelkova">느티나무 (Zelkova)</option>
            <option value="ginkgo">은행나무 (Ginkgo)</option>
            <option value="cherry">벚나무 (Cherry)</option>
            <option value="default">기타 (Default)</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>🔢 수량 (그루)</label>
        <input type="number" id="treeCount" value="1" min="1" placeholder="예: 1">
        <p style="font-size:11px; color:#999; margin-top:5px;">* 군락 식재 시 수량 입력</p>
    </div>

    <div class="form-group">
        <label>📏 흉고직경 (cm)</label>
        <input type="number" id="dbh" placeholder="예: 25" min="1">
    </div>

    <div class="form-group">
        <label>📐 수고 (m)</label>
        <input type="number" id="height" placeholder="예: 8" min="1">
    </div>
    
    <div class="form-group">
        <label>🩺 건강 상태</label>
        <select id="status">
            <option value="healthy">양호 (Healthy)</option>
            <option value="warning">관리 필요 (Warning)</option>
            <option value="danger">위험 (Danger)</option>
        </select>
    </div>

    <button class="btn btn-primary" id="btnSave" onclick="saveTree()">💾 나무 심기 (저장)</button>
    <button class="btn btn-warning" id="btnUpdate" onclick="updateTree()">✏️ 수정하기</button>
    
    <div style="display:flex; gap:5px; margin-top:5px;">
        <button class="btn btn-danger" id="btnDelete" onclick="deleteTree()" style="display:none; flex:1;">🗑️ 삭제</button>
        <button class="btn btn-reset" onclick="resetForm()" style="flex:1;">🔄 초기화</button>
    </div>

</div>

<div id="map">
    <div class="map-controls">
        <button class="map-btn active" id="btnRoadmap" onclick="setMapType('roadmap')">일반지도</button>
        <button class="map-btn" id="btnSkyview" onclick="setMapType('skyview')">위성지도</button>
        <button class="map-btn" id="btnDistrict" onclick="toggleDistrict()">🔲 지적편집도</button>
    </div>
    
    <div class="legend">
        <div class="legend-item"><span class="color-box" style="background:rgba(0, 200, 83, 0.4); border:1px solid #004c80;"></span> 등록된 공원</div>
        <div class="legend-item"><span class="color-box" style="background:#8e44ad; opacity:0.6;"></span> 등록된 가로수길</div>
        <div class="legend-item"><img src="https://cdn-icons-png.flaticon.com/512/490/490091.png" width="15" style="margin-right:8px;"> 심을 나무</div>
        <div class="legend-item"><img src="https://cdn-icons-png.flaticon.com/512/489/489969.png" width="15" style="margin-right:8px;"> 기존 나무</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=257fdd3647dd6abdb05eae8681106514&libraries=services"></script>

<script>
    const TREE_ICON = 'https://cdn-icons-png.flaticon.com/512/490/490091.png';
    const OLD_TREE_ICON = 'https://cdn-icons-png.flaticon.com/512/489/489969.png';

    var mapContainer = document.getElementById('map'),
        mapOption = { center: new kakao.maps.LatLng(34.811678, 126.392322), level: 4 };
    var map = new kakao.maps.Map(mapContainer, mapOption);
    var geocoder = new kakao.maps.services.Geocoder();

    var currentMarker = null; 
    var existingMarkers = []; 
    var zonePolygons = []; 
    var zoneDataList = []; // 검색을 위해 불러온 구역 데이터 저장

    function setMapType(maptype) {
        var roadmapBtn = document.getElementById('btnRoadmap');
        var skyviewBtn = document.getElementById('btnSkyview'); 
        if (maptype === 'roadmap') {
            map.setMapTypeId(kakao.maps.MapTypeId.ROADMAP);
            roadmapBtn.classList.add('active'); skyviewBtn.classList.remove('active');
        } else {
            map.setMapTypeId(kakao.maps.MapTypeId.HYBRID);
            roadmapBtn.classList.remove('active'); skyviewBtn.classList.add('active');
        }
    }

    var useDistrict = false;
    function toggleDistrict() {
        useDistrict = !useDistrict;
        var btn = document.getElementById('btnDistrict');
        if (useDistrict) {
            map.addOverlayMapTypeId(kakao.maps.MapTypeId.USE_DISTRICT);
            btn.classList.add('active');
        } else {
            map.removeOverlayMapTypeId(kakao.maps.MapTypeId.USE_DISTRICT);
            btn.classList.remove('active');
        }
    }

    function onMapClick(latlng) {
        resetForm(); 
        if (!currentMarker) {
            currentMarker = new kakao.maps.Marker({
                position: latlng, 
                map: map,
                image: new kakao.maps.MarkerImage(TREE_ICON, new kakao.maps.Size(40, 40), {offset: new kakao.maps.Point(40, 40)})
            });
        } else {
            currentMarker.setPosition(latlng);
            currentMarker.setMap(map);
        }

        $('#treeLat').val(latlng.getLat());
        $('#treeLng').val(latlng.getLng());
        $('#coordDisplay').text(`${latlng.getLat().toFixed(6)}, ${latlng.getLng().toFixed(6)}`);

        geocoder.coord2Address(latlng.getLng(), latlng.getLat(), function(result, status) {
            if (status === kakao.maps.services.Status.OK) {
                $('#address').val(result[0].address.address_name);
            }
        });
    }

    kakao.maps.event.addListener(map, 'click', function(mouseEvent) {
        onMapClick(mouseEvent.latLng);
    });

    // 3. 데이터 로드 (검색용 리스트 확보)
    function loadData() {
        // 기존 나무 로드
        $.ajax({
            url: 'api_trees.php',
            type: 'GET',
            success: function(data) {
                existingMarkers.forEach(m => m.setMap(null));
                existingMarkers = [];
                if(data.features) {
                    data.features.forEach(f => {
                        var lat = f.geometry.coordinates[1];
                        var lng = f.geometry.coordinates[0];
                        var props = f.properties;
                        
                        var marker = new kakao.maps.Marker({
                            position: new kakao.maps.LatLng(lat, lng),
                            map: map,
                            title: props.species,
                            image: new kakao.maps.MarkerImage(OLD_TREE_ICON, new kakao.maps.Size(40, 40))
                        });
                        kakao.maps.event.addListener(marker, 'click', function() {
                            selectTreeForEdit(props);
                        });
                        existingMarkers.push(marker);
                    });
                }
            }
        });

        // 구역 로드 & 검색 데이터 저장
        $.ajax({
            url: 'api_zones.php',
            type: 'GET',
            success: function(zones) {
                zonePolygons.forEach(p => p.setMap(null));
                zonePolygons = [];
                zoneDataList = []; // 초기화

                if (!Array.isArray(zones)) return;

                zones.forEach(zone => {
                    var geoType = zone.geometry.type;
                    var coords = zone.geometry.coordinates;
                    var props = zone.properties;
                    var centerPos = null;

                    // 검색용 데이터 저장
                    if (geoType === 'Polygon') {
                        var path = coords[0].map(c => new kakao.maps.LatLng(c[1], c[0]));
                        var polygon = new kakao.maps.Polygon({
                            map: map, path: path, strokeWeight: 2, strokeColor: '#004c80',
                            strokeOpacity: 0.8, fillColor: '#00c853', fillOpacity: 0.3
                        });
                        kakao.maps.event.addListener(polygon, 'click', function(mouseEvent) { onMapClick(mouseEvent.latLng); });
                        zonePolygons.push(polygon);
                        centerPos = path[0]; // 이동 좌표 (임시)

                    } else if (geoType === 'LineString') {
                        var path = coords.map(c => new kakao.maps.LatLng(c[1], c[0]));
                        var polyline = new kakao.maps.Polyline({
                            map: map, path: path, strokeWeight: 10, strokeColor: '#8e44ad',
                            strokeOpacity: 0.5, strokeStyle: 'solid'
                        });
                        kakao.maps.event.addListener(polyline, 'click', function(mouseEvent) { onMapClick(mouseEvent.latLng); });
                        zonePolygons.push(polyline);
                        centerPos = path[Math.floor(path.length/2)]; // 중간 지점
                    }

                    // 리스트에 추가
                    zoneDataList.push({
                        name: props.name,
                        type: props.type, // 'park' or 'street'
                        position: centerPos
                    });
                });
            },
            error: function(e) { console.error("구역 로드 실패:", e); }
        });
    }

    // ★ 검색 기능 구현
    function searchZone() {
        var keyword = $('#searchKeyword').val().trim();
        var resultBox = $('#searchResult');
        resultBox.empty().hide();

        if (keyword === '') { alert("검색어를 입력하세요."); return; }

        var results = zoneDataList.filter(z => z.name.includes(keyword));

        if (results.length === 0) {
            alert("검색 결과가 없습니다.");
            return;
        }

        results.forEach(z => {
            var badgeClass = z.type === 'street' ? 'bg-street' : 'bg-park';
            var typeName = z.type === 'street' ? '길' : '공원';
            
            var li = $(`<li><span class="badge ${badgeClass}">${typeName}</span> ${z.name}</li>`);
            li.on('click', function() {
                map.panTo(z.position); // 지도 이동
                map.setLevel(3); // 줌인
                resultBox.hide();
            });
            resultBox.append(li);
        });
        resultBox.show();
    }

    function selectTreeForEdit(props) {
        if(currentMarker) currentMarker.setMap(null); 
        $('#treeId').val(props.id);
        $('#species').val(props.species);
        $('#dbh').val(props.dbh);
        $('#height').val(props.height);
        $('#treeCount').val(props.count);
        $('#status').val(props.status);
        $('#coordDisplay').text("선택된 나무 ID: " + props.id);
        $('#formTitle').text("✏️ 나무 정보 수정");

        if(props.image_path) {
            $('#currentPhoto').attr('src', props.image_path);
            $('#previewArea').show();
        } else {
            $('#previewArea').hide();
        }
        $('#btnSave').hide(); $('#btnUpdate').show(); $('#btnDelete').show();
    }

    function saveTree() {
        if(!validateForm()) return;
        var formData = getFormData();
        $.ajax({
            url: 'save_tree.php', type: 'POST', data: formData,
            processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if(res.success) { alert("✅ 저장 완료"); resetForm(); loadData(); }
                else alert("실패: " + res.error);
            }
        });
    }

    function updateTree() {
        if(!validateForm()) return;
        var formData = getFormData();
        formData.append('id', $('#treeId').val()); 
        if(!confirm("정보를 수정하시겠습니까?")) return;
        $.ajax({
            url: 'update_tree.php', type: 'POST', data: formData,
            processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if(res.success) { alert("✅ 수정 완료"); resetForm(); loadData(); }
                else alert("실패: " + res.error);
            }
        });
    }

    function deleteTree() {
        var id = $('#treeId').val();
        if(!id) return;
        if(!confirm("정말 삭제하시겠습니까?")) return;
        $.ajax({
            url: 'delete_tree.php', type: 'POST', data: {id: id}, dataType: 'json',
            success: function(res) {
                if(res.success) { alert("🗑️ 삭제 완료"); resetForm(); loadData(); }
                else alert("실패: " + res.error);
            }
        });
    }

    function getFormData() {
        var formData = new FormData();
        formData.append('lat', $('#treeLat').val());
        formData.append('lng', $('#treeLng').val());
        formData.append('species', $('#species').val());
        formData.append('dbh', $('#dbh').val());
        formData.append('height', $('#height').val());
        formData.append('status', $('#status').val());
        formData.append('tree_count', $('#treeCount').val());
        var photo = $('#photo')[0].files[0];
        if(photo) formData.append('photo', photo);
        return formData;
    }

    function validateForm() {
        if(!$('#treeId').val() && !$('#treeLat').val()) { alert("위치를 선택하세요."); return false; }
        if(!$('#dbh').val()) { alert("흉고직경을 입력하세요."); return false; }
        return true;
    }

    function resetForm() {
        $('#formTitle').text("🌲 나무 심기 (등록)");
        $('#treeId').val('');
        $('#treeLat').val(''); $('#treeLng').val('');
        $('#coordDisplay').text('지도를 클릭하세요');
        $('#dbh').val(''); $('#height').val(''); $('#treeCount').val('1');
        $('#photo').val(''); $('#previewArea').hide();
        $('#btnSave').show(); $('#btnUpdate').hide(); $('#btnDelete').hide();
        if(currentMarker) currentMarker.setMap(null);
    }

    loadData();
</script>
</body>
</html>