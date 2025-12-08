<?php
// update_tree.php
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php';

// save_tree.php에 있는 compressImage 함수 복사해서 사용하거나, 별도 파일(util.php)로 빼서 include 하세요.
// 편의상 여기서는 간단한 로직만 적습니다. (위의 compressImage 함수를 여기에 똑같이 넣으세요)
function compressImage($source, $destination, $quality) {
    // ... (save_tree.php와 동일한 함수 내용 복사 붙여넣기) ...
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source);
    elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source);
    elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source);
    else return false;
    
    // EXIF 회전 등 로직 생략 (위와 동일하게 적용 권장)
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
    imagejpeg($image, $destination, $quality);
    return true;
}

$id = $_POST['id'] ?? 0;
$species = $_POST['species'] ?? 'default';
$dbh = $_POST['dbh'] ?? 0;
$height = $_POST['height'] ?? 0;
$status = $_POST['status'] ?? 'healthy';
$treeCount = $_POST['tree_count'] ?? 1;

if (!$id) { echo json_encode(['success'=>false, 'error'=>'ID 누락']); exit; }

// 이미지 업데이트 처리
$imageClause = "";
$imagePath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $uploadDir = 'uploads/';
    $newFileName = time() . '_' . rand(1000,9999) . '.jpg';
    $targetFile = $uploadDir . $newFileName;
    
    if (compressImage($_FILES['photo']['tmp_name'], $targetFile, 75)) {
        $imagePath = $targetFile;
        // 기존 이미지 삭제 로직 필요하면 추가
    }
}

try {
    if ($imagePath) {
        $sql = "UPDATE smart_trees SET species_code=?, dbh=?, height=?, status=?, tree_count=?, image_path=?, last_checked_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddsssi", $species, $dbh, $height, $status, $treeCount, $imagePath, $id);
    } else {
        $sql = "UPDATE smart_trees SET species_code=?, dbh=?, height=?, status=?, tree_count=?, last_checked_at=NOW() WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddssi", $species, $dbh, $height, $status, $treeCount, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>