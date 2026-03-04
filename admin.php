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
// 상세 통계 데이터 가공
// -------------------------------------------------------------------
$total_responses = count($responses);
$today_count = 0;
$total_score = 0;
$source_counts = [];
$impression_counts = [];
$ease_counts = [];
$purpose_counts = [];
$trend_data = []; // 날짜별 일간 응답수/평균점수 추이

$today_date = date('Y-m-d');

// 지난 7일간의 날짜 구조 초기화
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend_data[$d] = ['count' => 0, 'sum_score' => 0];
}

foreach ($responses as $r) {
    // 1. 기본 메트릭
    $score = (int)$r['q7_recommend_score'];
    $total_score += $score;
    
    // 날짜별 데이터 처리
    $raw_date = date('Y-m-d', strtotime($r['created_at']));
    if ($raw_date === $today_date) {
        $today_count++;
    }
    
    if (isset($trend_data[$raw_date])) {
        $trend_data[$raw_date]['count']++;
        $trend_data[$raw_date]['sum_score'] += $score;
    }

    // 2. 방문 경로 분류 (도넛 차트용)
    $src = $r['q1_source'];
    if (strpos($src, '기타:') === 0) $src = '기타';
    
    $mapped_src = $src;
    if (strpos($src, '검색 엔진') !== false) $mapped_src = '검색 엔진';
    elseif (strpos($src, 'SNS') !== false) $mapped_src = 'SNS';
    elseif (strpos($src, '지인 추천') !== false) $mapped_src = '지인 추천';
    elseif (strpos($src, '포트폴리오') !== false) $mapped_src = '포트폴리오';
    elseif (strpos($src, '우연히 발견') !== false) $mapped_src = '우연히 발견';

    if (!isset($source_counts[$mapped_src])) $source_counts[$mapped_src] = 0;
    $source_counts[$mapped_src]++;

    // 3. 첫인상 평가 (막대 차트용)
    $imp = $r['q3_first_impression'];
    $mapped_imp = $imp;
    if (strpos($imp, '개선') !== false) $mapped_imp = '개선필요';
    elseif (strpos($imp, '깔끔') !== false) $mapped_imp = '깔끔함';
    elseif (strpos($imp, '인상적') !== false) $mapped_imp = '매우 인상적';
    elseif (strpos($imp, '무난') !== false) $mapped_imp = '무난함';
    elseif (strpos($imp, '혼란') !== false) $mapped_imp = '혼란스러움';

    if (!isset($impression_counts[$mapped_imp])) $impression_counts[$mapped_imp] = 0;
    $impression_counts[$mapped_imp]++;

    // 4. 탐색 편의성 (원형 차트용)
    $ease = $r['q4_ease_of_use'];
    if (!isset($ease_counts[$ease])) $ease_counts[$ease] = 0;
    $ease_counts[$ease]++;

    // 5. 방문 목적 다중선택 분리 (레이더 차트용)
    $purposes = explode(',', $r['q2_purpose']);
    foreach($purposes as $p) {
        $p = trim($p);
        if(empty($p)) continue;
        if (!isset($purpose_counts[$p])) $purpose_counts[$p] = 0;
        $purpose_counts[$p]++;
    }
}

$average_score = $total_responses > 0 ? round($total_score / $total_responses, 1) : 0;

// --------- 데이터 직렬화 (JSON) ---------
// 1. Source (Doughnut)
$chart_source_labels = json_encode(array_keys($source_counts));
$chart_source_data = json_encode(array_values($source_counts));

// 2. Impression (Bar) - 순서 고정
$imp_order = ['개선필요', '혼란스러움', '무난함', '깔끔함', '매우 인상적'];
$chart_imp_data_arr = [];
foreach($imp_order as $order_key) {
    $chart_imp_data_arr[] = isset($impression_counts[$order_key]) ? $impression_counts[$order_key] : 0;
}
$chart_imp_labels = json_encode($imp_order);
$chart_imp_data = json_encode($chart_imp_data_arr);

// 3. 편의성 (Pie)
$chart_ease_labels = json_encode(array_keys($ease_counts));
$chart_ease_data = json_encode(array_values($ease_counts));

// 4. 목적 (Radar)
$chart_purpose_labels = json_encode(array_keys($purpose_counts));
$chart_purpose_data = json_encode(array_values($purpose_counts));

// 5. 트렌드 (Line - 7일 기준)
$trend_date_labels = [];
$trend_count_data = [];
$trend_avg_data = [];

foreach ($trend_data as $date => $info) {
    // mm-dd 포맷으로 축약
    $trend_date_labels[] = date('m/d', strtotime($date));
    $trend_count_data[] = $info['count'];
    $t_avg = $info['count'] > 0 ? round($info['sum_score'] / $info['count'], 1) : 0;
    $trend_avg_data[] = $t_avg;
}
$chart_trend_labels = json_encode($trend_date_labels);
$chart_trend_count_data = json_encode($trend_count_data);
$chart_trend_avg_data = json_encode($trend_avg_data);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>설문 관리 대시보드</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js 프로페셔널 버전 및 Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>body { font-family: "Pretendard", sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen pb-12">
    <!-- 헤더 영역 -->
    <div class="text-center pt-8 pb-6 bg-white border-b border-slate-100 flex flex-col items-center relative shadow-sm">
        <div class="absolute left-4 sm:left-8 top-6 sm:top-8">
            <a href="index.php" target="_blank" class="text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 sm:px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 border border-indigo-100">🏠 설문 페이지로 가기</a>
        </div>
        <h1 class="text-3xl font-bold text-indigo-600 mb-2 mt-4">설문 관리 대시보드</h1>
        <p class="text-slate-500 font-medium tracking-tight">총 <?php echo $total_responses; ?>개의 피드백이 수집되었습니다.</p>
        <a href="?logout=1" class="absolute right-4 sm:right-8 top-6 sm:top-8 text-sm text-slate-500 hover:text-slate-800 border px-3 sm:px-4 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg transition">로그아웃</a>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <!-- 상단 요약 카드 (3열) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 group">
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-8 text-center transform hover:-translate-y-1 transition duration-300">
                <p class="text-slate-500 font-semibold mb-3">전체 누적 응답</p>
                <div class="flex justify-center items-end gap-2">
                    <p class="text-5xl font-extrabold text-indigo-600"><?php echo number_format($total_responses); ?></p>
                    <span class="text-slate-400 font-medium mb-1">건</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-8 text-center transform hover:-translate-y-1 transition duration-300">
                <p class="text-slate-500 font-semibold mb-3">평균 추천 지수 (NPS)</p>
                <div class="flex justify-center items-end gap-2">
                    <p class="text-5xl font-extrabold text-emerald-500"><?php echo $average_score; ?></p>
                    <span class="text-slate-400 font-medium mb-1">/ 10.0</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-8 text-center transform hover:-translate-y-1 transition duration-300">
                <p class="text-slate-500 font-semibold mb-3">신규 응답 (오늘)</p>
                <div class="flex justify-center items-end gap-2">
                    <p class="text-5xl font-extrabold text-sky-500">+<?php echo number_format($today_count); ?></p>
                    <span class="text-slate-400 font-medium mb-1">건</span>
                </div>
            </div>
        </div>

        <!-- 고급 분석 차트 영역 -->
        
        <!-- Row 1: 트렌드 라인 차트 (전체 너비 차지) -->
        <div class="bg-white rounded-2xl shadow border border-slate-100 p-6 mb-6">
            <h3 class="text-lg font-bold text-slate-800 mb-1 px-2">최근 7일 일간 참여 트렌드</h3>
            <p class="text-sm text-slate-400 px-2 mb-6">응답량(막대)과 일일 평균 추천점수(꺾은선)를 함께 확인합니다.</p>
            <div class="relative h-72 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Row 2: 2열 배치 다양한 통계 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 방문 경로 -->
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">주요 유입 경로</h3>
                <div class="relative h-64 w-full">
                    <canvas id="sourceChart"></canvas>
                </div>
            </div>
            
            <!-- 첫인상 막대 차트 (가로형 배치) -->
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">첫인상 척도 분포</h3>
                <div class="relative h-64 w-full">
                    <canvas id="impressionChart"></canvas>
                </div>
            </div>
            
            <!-- 탐색 편의성 -->
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">정보 탐색 편의성</h3>
                <div class="relative h-64 w-full">
                    <canvas id="easeChart"></canvas>
                </div>
            </div>

            <!-- 방문 목적 레이더 -->
            <div class="bg-white rounded-2xl shadow border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">주 웹사이트 방문 목적 (다중응답)</h3>
                <div class="relative h-72 w-full">
                    <canvas id="purposeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 하단 원시 데이터 표 -->
        <div class="bg-white rounded-2xl shadow border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">최근 응답 로우 데이터</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">ID / 시간</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[150px]">유입경로</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[200px]">방문목적</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">첫인상</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">편의성</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[300px]">유용건텐츠</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[200px]">추가기능</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">추천(NPS)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100 text-sm">
                        <?php if (count($responses) > 0): ?>
                            <?php foreach($responses as $row): ?>
                            <tr class="hover:bg-indigo-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-800 font-bold">#<?php echo $row['id']; ?><br><span class="text-xs text-slate-400 font-normal"><?php echo date('y.m.d H:i', strtotime($row['created_at'])); ?></span></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q1_source']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q2_purpose']); ?></td>
                                <td class="px-6 py-4 text-slate-600 font-medium"><?php echo htmlspecialchars($row['q3_first_impression']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q4_ease_of_use']); ?></td>
                                <td class="px-6 py-4 text-slate-600 bg-slate-50/50 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($row['q5_best_content']); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($row['q6_additional_options']); ?></td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm <?php echo $row['q7_recommend_score'] >= 8 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : ($row['q7_recommend_score'] >= 5 ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-rose-100 text-rose-800 border-rose-200'); ?> border">
                                        <?php echo $row['q7_recommend_score']; ?>점
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400 font-medium">진행된 설문 응답이 없습니다.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 다이나믹 Chart.js 렌더링 스크립트 -->
    <script>
        // 전역 글꼴 설정
        Chart.defaults.font.family = "'Pretendard', sans-serif";
        Chart.defaults.color = '#64748b'; // slate-500

        // 컬러 테마 설정
        const modernColors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#0ea5e9', '#f43f5e'];

        // --- 1. 7일 트렌드 (혼합 차트: 막대 + 꺾은선) ---
        new Chart(document.getElementById('trendChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo $chart_trend_labels; ?>,
                datasets: [
                    {
                        type: 'line',
                        label: '일 평균 추천 평점',
                        data: <?php echo $chart_trend_avg_data; ?>,
                        borderColor: '#10b981', // emerald
                        backgroundColor: '#10b981',
                        borderWidth: 3,
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        type: 'bar',
                        label: '일간 응답 수',
                        data: <?php echo $chart_trend_count_data; ?>,
                        backgroundColor: 'rgba(99, 102, 241, 0.2)', // indigo light
                        hoverBackgroundColor: 'rgba(99, 102, 241, 0.4)',
                        borderColor: '#6366f1',
                        borderWidth: 1,
                        borderRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        type: 'linear', display: true, position: 'left',
                        title: { display: true, text: '응답 수(건)' },
                        grid: { borderDash: [4, 4] }
                    },
                    y1: { 
                        type: 'linear', display: true, position: 'right',
                        title: { display: true, text: '추천 평점(0-10)' },
                        min: 0, max: 10,
                        grid: { display: false }
                    }
                }
            }
        });

        // --- 2. 방문 경로 (도넛) ---
        new Chart(document.getElementById('sourceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $chart_source_labels; ?>,
                datasets: [{
                    data: <?php echo $chart_source_data; ?>,
                    backgroundColor: modernColors,
                    borderWidth: 2, hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });

        // --- 3. 첫인상 평가 (가로형 막대 차트: Horizontal Bar) ---
        new Chart(document.getElementById('impressionChart').getContext('2d'), {
            type: 'bar', // Chart.js v3+ 에서는 type은 bar로 두고 indexAxis를 y로 설정
            data: {
                labels: <?php echo $chart_imp_labels; ?>,
                datasets: [{
                    label: '응답 인원',
                    data: <?php echo $chart_imp_data; ?>,
                    // 각 항목별로 인상에 맞는 그라데이션 느낌의 색상 배열 (부정->긍정)
                    backgroundColor: ['#f43f5e', '#f59e0b', '#94a3b8', '#38bdf8', '#8b5cf6'],
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y', // 가로 막대
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } }, // 범례 필요 없음
                scales: { x: { grid: { borderDash: [4, 4] } }, y: { grid: { display: false } } }
            }
        });

        // --- 4. 탐색 편의성 (파이 차트) ---
        new Chart(document.getElementById('easeChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo $chart_ease_labels; ?>,
                datasets: [{
                    data: <?php echo $chart_ease_data; ?>,
                    backgroundColor: ['#047857', '#10b981', '#6ee7b7', '#fcd34d', '#ef4444'], // 긍정(녹색계열) -> 부정(적색계열) 등 직관적 색상
                    borderWidth: 2, hoverOffset: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }
                }
            }
        });

        // --- 5. 방문 목적 (레이더 차트) ---
        new Chart(document.getElementById('purposeChart').getContext('2d'), {
            type: 'radar',
            data: {
                labels: <?php echo $chart_purpose_labels; ?>,
                datasets: [{
                    label: '빈도 수',
                    data: <?php echo $chart_purpose_data; ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.3)', // 보라색 반투명
                    borderColor: '#8b5cf6',
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#8b5cf6',
                    pointHoverBackgroundColor: '#8b5cf6',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: 'rgba(0,0,0,0.05)' },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        pointLabels: { font: { size: 12, family: 'Pretendard' }, color: '#475569' },
                        ticks: { display: false } // 수치 라벨 숨김 (깔끔하게)
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
