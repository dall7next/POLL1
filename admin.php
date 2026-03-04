<?php
session_start();
$admin_password = 'admin'; // 기본 관리자 비밀번호 (변경 권장)

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "비밀번호가 일치하지 않습니다.";
    }
}

if (empty($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>관리자 로그인</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-sm w-full bg-white rounded-xl shadow border border-slate-100 p-8">
        <h2 class="text-2xl font-bold text-center mb-6">설문 관리 대시보드 로그인</h2>
        <?php if(isset($error)) echo '<p class="text-red-500 text-sm mb-4 text-center">'.$error.'</p>'; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="비밀번호 입력" required class="w-full border border-slate-300 rounded-lg px-4 py-3 mb-4 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-lg hover:bg-indigo-700 transition">로그인</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

require_once 'config.php';
$stmt = $pdo->query("SELECT * FROM responses ORDER BY id DESC");
$responses = $stmt->fetchAll();

// -------------------------------------------------------------------
// 통계 데이터 가공
// -------------------------------------------------------------------
$total_responses = count($responses);
$today_count = 0;
$total_score = 0;
$source_counts = [];
$impression_counts = [];

$today_date = date('Y-m-d');

foreach ($responses as $r) {
    // NPS & Average
    $total_score += (int)$r['q7_recommend_score'];
    
    // Today Count
    if (strpos($r['created_at'], $today_date) === 0) {
        $today_count++;
    }

    // Source (도넛 차트용)
    // "기타: 어쩌구" 처리를 위해 '기타'라는 문구로 시작하면 기타로 묶어줌
    $src = $r['q1_source'];
    if (strpos($src, '기타:') === 0) $src = '기타';
    
    // 라벨 단순화를 위한 매핑
    $mapped_src = $src;
    if (strpos($src, '검색 엔진') !== false) $mapped_src = '검색 엔진';
    elseif (strpos($src, 'SNS') !== false) $mapped_src = 'SNS';
    elseif (strpos($src, '지인 추천') !== false) $mapped_src = '지인 추천';
    elseif (strpos($src, '포트폴리오') !== false) $mapped_src = '포트폴리오';
    elseif (strpos($src, '우연히 발견') !== false) $mapped_src = '우연히 발견';

    if (!isset($source_counts[$mapped_src])) $source_counts[$mapped_src] = 0;
    $source_counts[$mapped_src]++;

    // Impression (바 차트용)
    $imp = $r['q3_first_impression'];
    $mapped_imp = $imp;
    if (strpos($imp, '개선') !== false) $mapped_imp = '개선필요';
    elseif (strpos($imp, '깔끔') !== false) $mapped_imp = '깔끔함';
    elseif (strpos($imp, '인상적') !== false) $mapped_imp = '매우 인상적';
    elseif (strpos($imp, '무난') !== false) $mapped_imp = '무난함';
    elseif (strpos($imp, '혼란') !== false) $mapped_imp = '혼란스러움';

    if (!isset($impression_counts[$mapped_imp])) $impression_counts[$mapped_imp] = 0;
    $impression_counts[$mapped_imp]++;
}

$average_score = $total_responses > 0 ? round($total_score / $total_responses, 1) : 0;

// 차트 데이터 JSON 직렬화
$chart_source_labels = json_encode(array_keys($source_counts));
$chart_source_data = json_encode(array_values($source_counts));

// 바 차트는 순서를 고정하는 것이 예쁨
$imp_order = ['개선필요', '깔끔함', '매우 인상적', '무난함', '혼란스러움'];
$chart_imp_data_arr = [];
foreach($imp_order as $order_key) {
    $chart_imp_data_arr[] = isset($impression_counts[$order_key]) ? $impression_counts[$order_key] : 0;
}
$chart_imp_labels = json_encode($imp_order);
$chart_imp_data = json_encode($chart_imp_data_arr);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>설문 관리 대시보드</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>body { font-family: "Pretendard", sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen pb-12">
    <!-- 헤더 영역 -->
    <div class="text-center pt-10 pb-8 bg-white border-b border-slate-100 flex flex-col items-center">
        <h1 class="text-3xl font-bold text-indigo-500 mb-2">설문 관리 대시보드</h1>
        <p class="text-slate-500 font-medium tracking-tight">총 <?php echo $total_responses; ?>개의 피드백이 수집되었습니다.</p>
        <div class="absolute left-4 sm:left-8 top-6 sm:top-8"><a href="index.php" target="_blank" class="text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 sm:px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 shadow-sm border border-indigo-100">🏠 설문 페이지로 가기</a></div><a href="?logout=1" class="absolute right-4 sm:right-8 top-6 sm:top-8 text-sm text-slate-500 hover:text-slate-800 border px-3 sm:px-4 py-2 bg-white shadow-sm rounded-lg transition">로그아웃</a>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <!-- 상단 요약 카드 (3열) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
                <p class="text-slate-600 font-medium mb-3">총 응답 수</p>
                <p class="text-5xl font-bold text-indigo-500"><?php echo number_format($total_responses); ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
                <p class="text-slate-600 font-medium mb-3">평균 추천 점수 (NPS)</p>
                <p class="text-5xl font-bold text-indigo-500"><?php echo $average_score; ?> / 10</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
                <p class="text-slate-600 font-medium mb-3">오늘 응답</p>
                <p class="text-5xl font-bold text-indigo-500"><?php echo number_format($today_count); ?></p>
            </div>
        </div>

        <!-- 중간 차트 영역 (2열) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 방문 경로 도넛 차트 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">방문 경로</h3>
                <div class="relative h-72 w-full">
                    <canvas id="sourceChart"></canvas>
                </div>
            </div>
            <!-- 첫인상 막대 차트 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">첫인상 평가</h3>
                <div class="relative h-72 w-full">
                    <canvas id="impressionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 하단 원시 데이터 표 -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">최근 응답 기록</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider whitespace-nowrap">ID / 시간</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider min-w-[150px]">유입경로</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider min-w-[200px]">방문목적</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider whitespace-nowrap">첫인상</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider whitespace-nowrap">편의성</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider min-w-[300px]">유용건텐츠</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider min-w-[200px]">추가기능</th>
                            <th class="px-6 py-4 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">추천</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100 text-sm">
                        <?php if (count($responses) > 0): ?>
                            <?php foreach($responses as $row): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-900 font-medium">#<?php echo $row['id']; ?><br><span class="text-xs text-slate-400 font-normal"><?php echo date('y.m.d H:i', strtotime($row['created_at'])); ?></span></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q1_source']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q2_purpose']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q3_first_impression']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q4_ease_of_use']); ?></td>
                                <td class="px-6 py-4 text-slate-600 bg-slate-50/30 whitespace-pre-wrap"><?php echo htmlspecialchars($row['q5_best_content']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q6_additional_options']); ?></td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $row['q7_recommend_score'] >= 8 ? 'bg-indigo-100 text-indigo-700' : ($row['q7_recommend_score'] >= 5 ? 'bg-yellow-100 text-yellow-700' : 'bg-rose-100 text-rose-700'); ?>">
                                        <?php echo $row['q7_recommend_score']; ?>점
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400">등록된 응답이 없습니다.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chart.js 렌더링 스크립트 -->
    <script>
        // 공통 테마 컬러 세트 (이미지와 유사한 톤)
        const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#64748b', '#0ea5e9'];
        
        // --- 1. 방문 경로 (도넛 차트) ---
        const sourceCtx = document.getElementById('sourceChart').getContext('2d');
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $chart_source_labels; ?>,
                datasets: [{
                    data: <?php echo $chart_source_data; ?>,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 25, padding: 15, font: { family: 'Pretendard' } }
                    }
                }
            }
        });

        // --- 2. 첫인상 평가 (막대 차트) ---
        const impressionCtx = document.getElementById('impressionChart').getContext('2d');
        new Chart(impressionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $chart_imp_labels; ?>,
                datasets: [{
                    label: '응답 수',
                    data: <?php echo $chart_imp_data; ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 5, font: { family: 'Pretendard' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Pretendard' } } }
                },
                plugins: {
                    legend: { display: false } // 바 차트는 하나의 데이터셋이므로 범례 생략 (이미지와 동일)
                }
            }
        });
    </script>
</body>
</html>
