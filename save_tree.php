<?php
// save_tree.php (이미지 압축 기능 추가됨)
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php';

// --- [이미지 리사이징 함수] ---
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source);
    elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source);
    else return false;

    // 회전 보정 (스마트폰 세로 사진 문제 해결)
    $exif = @exif_read_data($source);
    if (!empty($exif['Orientation'])) {
        switch ($exif['Orientation']) {
            case 3: $image = imagerotate($image, 180, 0); break;
            case 6: $image = imagerotate($image, -90, 0); break;
            case 8: $image = imagerotate($image, 90, 0); break;
        }
    }

    // 크기 조정 (가로 최대 800px)
    $width = imagesx($image);
    $height = imagesy($image);
    $maxWidth = 800;
    
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = floor($height * ($maxWidth / $width));
        $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmpImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $image = $tmpImage;
    }

    // 파일 저장
    imagejpeg($image, $destination, $quality);
    return true;
}
// -----------------------------

$lat = $_POST['lat'] ?? '';
$lng = $_POST['lng'] ?? '';
$species = $_POST['species'] ?? 'default';
$dbh = $_POST['dbh'] ?? 0;
$height = $_POST['height'] ?? 0;
$status = $_POST['status'] ?? 'healthy';
$treeCount = $_POST['tree_count'] ?? 1;

if (empty($lat) || empty($lng)) {
    echo json_encode(['success' => false, 'error' => '위치 정보 누락']);
    exit;
}

// ★ 이미지 처리 (압축 적용)
$imagePath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true); // 폴더 없으면 생성

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $newFileName = time() . '_' . rand(1000,9999) . '.' . $ext;
    $targetFile = $uploadDir . $newFileName;

    // 압축하여 저장 (품질 75)
    if (compressImage($_FILES['photo']['tmp_name'], $targetFile, 75)) {
        $imagePath = $targetFile;
    }
}

$pointWKT = "POINT($lng $lat)";

try {
    $sql = "INSERT INTO smart_trees 
            (species_code, dbh, height, coordinates, status, image_path, tree_count, last_checked_at) 
            VALUES (?, ?, ?, ST_GeomFromText(?), ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddsssi", $species, $dbh, $height, $pointWKT, $status, $imagePath, $treeCount);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>