# POLL1 - 고객 피드백 설문조사 애플리케이션

본 웹 애플리케이션은 웹사이트 방문자들의 피드백을 수집하고 분석하기 위해 구축된 PHP + MySQL 기반의 경량 설문조사 시스템입니다.

## 🚀 프로젝트 개요 (Overview)
- **사용자 페이지 (`index.php`)**: 설문 응답을 입력하는 인터페이스 (Tailwind CSS 기반 반응형 UI).
- **데이터 처리 (`submit.php`)**: 응답 폼 전송 데이터베이스 적재 (PDO).
- **관리자 대시보드 (`admin.php`)**: 다이나믹 그래프 (Chart.js) 기반의 설문 결과 시각화 및 로우 데이터(Raw Data) 확인.

## 🛠️ 기술 스택 (Tech Stack)
- **Backend**: PHP 8+ (기본 LAMP Stack 환경 호환)
- **Database**: MySQL (MariaDB) - `utf8mb4` 인코딩 (Emoji 등 특수 기호 완벽 지원)
- **Frontend**: HTML5, Tailwind CSS (CDN 연동)
- **Data Visualization**: Chart.js 3+

## ⚙️ 로컬 개발 환경 설정 (Getting Started)
로컬에서 프로젝트를 구동하려면, 로컬 PHP 개발 환경(XAMPP, MAMP 등)이 구축되어 있어야 합니다.

1. **저장소 클론(Clone)**
   ```bash
   git clone https://github.com/dall7next/POLL1.git
   cd POLL1
   ```

2. **데이터베이스 초기 셋팅**
   MySQL에서 `POLL1` 이라는 이름으로 데이터베이스를 생성하고, 응답 데이터를 담을 `responses` 테이블을 생성합니다.
   ```sql
   CREATE DATABASE POLL1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE POLL1;

   CREATE TABLE responses (
       id INT AUTO_INCREMENT PRIMARY KEY,
       q1_source VARCHAR(255) NOT NULL,
       q2_purpose VARCHAR(255) NOT NULL,
       q3_first_impression VARCHAR(50) NOT NULL,
       q4_ease_of_use VARCHAR(50) NOT NULL,
       q5_best_content TEXT NOT NULL,
       q6_additional_options TEXT NOT NULL,
       q7_recommend_score INT NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

3. **환경 설정 파일 셋팅 (`config.php`)**
   보안상 GitHub 저장소에는 템플릿 파일인 `config.example.php` 만 공유되어 있습니다. 이를 복사하여 실제 구성 파일로 연결합니다.
   ```bash
   cp config.example.php config.php
   ```
   이후 생성된 `config.php` 내용을 열어, 자신의 로컬 MySQL 정보를 포함하도록 수정합니다.
   **(`admin.php` 로그인에 사용될 관리자 비밀번호도 이곳에서 수정합니다)**
   ```php
    // 예시: config.php 수정 내용
    $host = '127.0.0.1';
    $dbname = 'POLL1';
    $username = 'root'; // 로컬 DB 계정
    $password = '본인의로컬DB비번'; 
    $admin_password = '원하는관리자비밀번호설정'; 
   ```

## 🔒 보안 정책 가이드 (Security)
- `config.php`, 서버 인증키(`AWS.txt` 등)와 같은 리얼 서버용 암호 파일은 절대로 Git 버전 관리에 올리지 마세요. (현재 `.gitignore` 로 안전하게 차단 처리됨)
- 향후 실서버 DB 비밀번호가 변경된다면, 리모트(`52.78.44.84`) 서버에 위치한 `/opt/bitnami/apache2/htdocs/POLL1/config.php` 내부의 비밀번호 부분을 직접(또는 SSH 터미널에서 `sed`, `vi` 등을 활용해) 갱신해 주어야 합니다.

## 📊 관리자 대시보드 구조 파악 (Features)
관리자 페이지(`admin.php`)에 접속하면 아래와 같은 분석 모듈을 확인 가능합니다.
* **Top Metric Cards**: 총 응답 수 및 일일 응답 건수 현황, NPS 종합 점수
* **Trend Chart (혼합형)**: 최근 7일간의 참여자/점수 트렌드
* **Doughnut / Radar / Bar 차트**: 유입원, 목적, 첫인상 분포 분석

## 📝 릴리즈 및 배포 (Deployment)
본 앱은 정적 파일과 단일 PHP 스크립트로 구성되어 있어서 별도의 빌드(build) 과정이 불필요합니다.
단순히 소스코드를 연결된 퍼블릭 경로(HTDOCS 등)에 `git pull` 혹은 `scp`/`rsync` 로 덮어씌워주기만 하면 즉시 배포가 완료됩니다.
