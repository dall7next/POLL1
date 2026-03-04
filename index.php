<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>홈페이지 방문 피드백</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', sans-serif; background-color: #f8fafc; color: #0f172a; }
    </style>
</head>
<body class="antialiased min-h-screen py-12 px-4 sm:px-6 lg:px-8 flex justify-center">
    <div class="max-w-3xl w-full bg-white rounded-2xl shadow-sm border border-slate-100 p-8 sm:p-10 transition-all">
        <!-- Header -->
        <header class="mb-10 text-center sm:text-left">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 mb-3">홈페이지 방문 피드백 부탁드립니다</h1>
            <p class="text-slate-500 text-base leading-relaxed">
                소중한 시간 내어 피드백 주셔서 감사합니다.<br>여러분들의 의견은 더 나은 사이트를 만드는 데 큰 도움이 됩니다.
            </p>
        </header>

        <!-- Form -->
        <form action="submit.php" method="POST" class="space-y-10">
            
            <!-- Q1 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">1. 어떤 경로로 이 사이트를 방문하셨나요?</h3>
                <div class="space-y-3">
                    <?php 
                    $sources = ['검색 엔진 (Google, Naver 등)', 'SNS (Instagram, Facebook, Threads 등)', '지인 추천', '포트폴리오/작품 링크', '우연히 발견'];
                    foreach($sources as $index => $source): ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="radio" name="q1_source" value="<?php echo htmlspecialchars($source); ?>" required class="w-5 h-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span class="text-slate-700 group-hover:text-slate-900 transition-colors"><?php echo $source; ?></span>
                    </label>
                    <?php endforeach; ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="radio" name="q1_source" value="기타" class="w-5 h-5 text-indigo-600 border-slate-300 focus:ring-indigo-500" id="q1_other_radio">
                        <span class="text-slate-700">기타:</span>
                        <input type="text" name="q1_source_other" id="q1_other_text" class="ml-2 flex-1 border-b border-slate-300 focus:border-indigo-600 focus:outline-none py-1 bg-transparent transition-colors" placeholder="직접 입력" onclick="document.getElementById('q1_other_radio').checked = true;">
                    </label>
                </div>
            </section>

            <!-- Q2 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">2. 어떤 정보를 찾으러 오셨나요? <span class="text-sm font-normal text-slate-500 ml-2">(중복 선택 가능)</span></h3>
                <div class="space-y-3">
                    <?php 
                    $purposes = ['바이브 코딩 학습 자료', '디자인/일러스트 작품', '강의/워크샵 정보', '교수/전문가 프로필', '프로젝트 협업 문의', '그냥 둘러보기'];
                    foreach($purposes as $index => $purpose): ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="q2_purpose[]" value="<?php echo htmlspecialchars($purpose); ?>" class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 transition-colors">
                        <span class="text-slate-700 group-hover:text-slate-900 transition-colors"><?php echo $purpose; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Q3 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">3. 사이트의 첫인상은 어떠셨나요?</h3>
                <div class="space-y-3">
                    <?php 
                    $impressions = ['😍 매우 인상적이고 독창적', '😊 깔끔하고 전문적', '😐 평범하지만 무난함', '😕 약간 혼란스러움', '😞 개선이 많이 필요함'];
                    foreach($impressions as $index => $impression): ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="radio" name="q3_first_impression" value="<?php echo htmlspecialchars($impression); ?>" required class="w-5 h-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span class="text-slate-700 group-hover:text-slate-900 transition-colors"><?php echo $impression; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Q4 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">4. 원하는 정보를 찾기 쉬웠나요?</h3>
                <div class="space-y-3">
                    <?php 
                    $ease = ['매우 쉬웠다 (직관적)', '쉬웠다 (조금 탐색 필요)', '보통이다', '어려웠다 (구조 파악 힘듦)', '찾지 못했다'];
                    foreach($ease as $index => $item): ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="radio" name="q4_ease_of_use" value="<?php echo htmlspecialchars($item); ?>" required class="w-5 h-5 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span class="text-slate-700 group-hover:text-slate-900 transition-colors"><?php echo $item; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Q5 -->
            <section>
                <h3 class="text-lg font-semibold mb-2">5. 가장 인상 깊었거나 유용했던 콘텐츠는?</h3>
                <p class="text-sm text-slate-500 mb-3">예: 바이브 코딩 튜토리얼, 프로젝트 갤러리, 블로그 글 제목 등 구체적으로 적어주세요.</p>
                <textarea name="q5_best_content" rows="4" class="w-full rounded-lg border-slate-300 bg-slate-50 border p-3 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow resize-none" placeholder="답변을 입력해주세요"></textarea>
            </section>

            <!-- Q6 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">6. 이 사이트에 추가되었으면 하는 기능이나 콘텐츠는?</h3>
                <div class="space-y-3">
                    <?php 
                    $additions = ['온라인 코딩 에디터/플레이그라운드', '강의 일정/신청 시스템', '커뮤니티/댓글 기능', '작품 다운로드/공유 기능', '뉴스레터 구독'];
                    foreach($additions as $index => $addition): ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="q6_additional_options[]" value="<?php echo htmlspecialchars($addition); ?>" class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 transition-colors">
                        <span class="text-slate-700 group-hover:text-slate-900 transition-colors"><?php echo $addition; ?></span>
                    </label>
                    <?php endforeach; ?>
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="q6_additional_options_other_check" value="기타" class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" id="q6_other_check">
                        <span class="text-slate-700">기타:</span>
                        <input type="text" name="q6_additional_options_other" id="q6_other_text" class="ml-2 flex-1 border-b border-slate-300 focus:border-indigo-600 focus:outline-none py-1 bg-transparent transition-colors" placeholder="직접 입력" onclick="document.getElementById('q6_other_check').checked = true;">
                    </label>
                </div>
            </section>

            <!-- Q7 -->
            <section>
                <h3 class="text-lg font-semibold mb-4">7. 이 사이트를 다른 사람에게 추천하실 의향이 있나요?</h3>
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-100">
                    <div class="flex justify-between text-sm font-medium text-slate-500 mb-4 px-1">
                        <span>0점 (절대 아니다)</span>
                        <span>5점 (보통)</span>
                        <span>10점 (적극 추천)</span>
                    </div>
                    <div class="flex justify-between items-center gap-1 sm:gap-2">
                        <?php for($i=0; $i<=10; $i++): ?>
                        <div class="flex flex-col items-center">
                            <input type="radio" name="q7_recommend_score" value="<?php echo $i; ?>" id="score_<?php echo $i; ?>" required class="peer sr-only">
                            <label for="score_<?php echo $i; ?>" class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all font-semibold select-none shadow-sm">
                                <?php echo $i; ?>
                            </label>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

            <!-- Footer / Submit -->
            <div class="pt-8 mt-10 border-t border-slate-100">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-8 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 text-lg">
                    설문 제출하기
                </button>
                <div class="mt-6 text-center space-y-2">
                    <p class="text-sm font-medium text-slate-600">🙏 소중한 피드백은 사이트 개선에 직접 반영됩니다.</p>
                    <div class="text-xs text-slate-400 flex justify-center items-center space-x-4">
                        <a href="mailto:jvisualschool@gmail.com" class="hover:text-indigo-600 transition-colors">jvisualschool@gmail.com</a>
                        <span>&bull;</span>
                        <a href="https://jvibeschool.net/" target="_blank" class="hover:text-indigo-600 transition-colors">jvibeschool.net</a>
                    </div>
                </div>
            </div>

        </form>
    </div>
</body>
</html>
