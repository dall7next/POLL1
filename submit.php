<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q1_source = $_POST['q1_source'] ?? '';
    if ($q1_source === '기타' && !empty($_POST['q1_source_other'])) {
        $q1_source = '기타: ' . $_POST['q1_source_other'];
    }

    $q2_purpose = isset($_POST['q2_purpose']) ? implode(', ', $_POST['q2_purpose']) : '';
    $q3_first_impression = $_POST['q3_first_impression'] ?? '';
    $q4_ease_of_use = $_POST['q4_ease_of_use'] ?? '';
    $q5_best_content = $_POST['q5_best_content'] ?? '';

    $q6_options = $_POST['q6_additional_options'] ?? [];
    if (isset($_POST['q6_additional_options_other_check']) && !empty($_POST['q6_additional_options_other'])) {
        $q6_options[] = '기타: ' . $_POST['q6_additional_options_other'];
    }
    $q6_additional_options = implode(', ', $q6_options);

    $q7_recommend_score = isset($_POST['q7_recommend_score']) ? (int)$_POST['q7_recommend_score'] : 0;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO responses (
                q1_source, q2_purpose, q3_first_impression, q4_ease_of_use, 
                q5_best_content, q6_additional_options, q7_recommend_score
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $q1_source, $q2_purpose, $q3_first_impression, $q4_ease_of_use,
            $q5_best_content, $q6_additional_options, $q7_recommend_score
        ]);
        
        // 제출 성공 시 감사 메시지 렌더링
        echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>제출 완료</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: "Pretendard", sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
        <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">제출이 완료되었습니다!</h2>
        <p class="text-slate-500 mb-8">소중한 피드백에 감사드립니다.<br>더 나은 사이트로 보답하겠습니다.</p>
        <a href="admin.php" class="inline-block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">관리자 모드로 보기</a>
    </div>
</body>
</html>';

    } catch (PDOException $e) {
        die("데이터 저장 중 오류가 발생했습니다: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("Location: index.php");
    exit;
}
?>
