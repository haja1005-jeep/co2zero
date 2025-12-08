<?php
// delete_tree.php
header('Content-Type: application/json; charset=utf-8');
include 'db_connect.php';

$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID 누락']);
    exit;
}

// 1. 이미지 파일 경로 조회 (파일 삭제를 위해)
$sql = "SELECT image_path FROM smart_trees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && $row['image_path'] && file_exists($row['image_path'])) {
    unlink($row['image_path']); // 서버에서 파일 삭제
}

// 2. DB 데이터 삭제
$delSql = "DELETE FROM smart_trees WHERE id = ?";
$delStmt = $conn->prepare($delSql);
$delStmt->bind_param("i", $id);

if ($delStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$conn->close();
?>