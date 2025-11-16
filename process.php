<?php
// PHP 오류 출력 강제 설정 (디버깅 목적: 브라우저에 안 나오면 로그 파일에 기록됨)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 세션 유효시간 5분(300초)으로 설정
ini_set('session.gc_maxlifetime', 300);  // 서버가 세션 데이터를 유지하는 시간
session_set_cookie_params(300);         // 브라우저 쿠키 만료 시간

session_start();

// 세션에서 사용자 이름 가져오기
$user_name = $_SESSION['user_name'] ?? '';
if (!$user_name) {
    // 📢 사용자 이름이 없으면 멈춥니다.
    die("사용자 이름이 설정되지 않았습니다. 다시 게임을 시작해주세요.");
}

// 입력값
$answer = trim($_POST['answer'] ?? '');
$problem_type = $_POST['problem_type'] ?? '';

// 세션에서 문제 정보 가져오기
$current_question = $_SESSION['current_question'] ?? [];

if (empty($current_question) || !isset($current_question['answer'])) {
    // 📢 문제 정보가 없으면 멈춥니다.
    die("세션에 문제 정보가 누락되었습니다. (세션 유실 가능성). 다시 시작해주세요.");
}
$correct_answers = $current_question['answer'];

// 정답 비교를 위한 사용자 입력 정규화 (공백 제거, 소문자 변환)
$user_answer_norm = mb_strtolower(trim(preg_replace('/\s+/', '', $answer)), 'UTF-8');
$is_correct = false;


// --------------------------------------------------------------------------------
// 📢📢📢 로그 파일 기록 영역 시작: game_debug.log 파일에 정보 기록 📢📢📢
// --------------------------------------------------------------------------------
$log_message = "\n--- [DEBUG START: " . date('Y-m-d H:i:s') . "] ---\n";
$log_message .= "User: " . $user_name . "\n";
$log_message .= "Problem: " . ($current_question['question'] ?? 'N/A') . "\n";
$log_message .= "POST Answer: [" . $answer . "]\n";
$log_message .= "Normalized User Answer: [" . $user_answer_norm . "]\n";

foreach ($correct_answers as $ca) {
    $ca_norm = mb_strtolower(trim(preg_replace('/\s+/', '', $ca)), 'UTF-8');
    $log_message .= "Normalized Correct Answer: [" . $ca_norm . "]";
    
    if ($user_answer_norm === $ca_norm) {
        $is_correct = true;
        $log_message .= " (MATCH! 🎉)\n";
        break;
    } else {
        $log_message .= " (FAIL ❌ - Lengths: User=" . strlen($user_answer_norm) . ", Correct=" . strlen($ca_norm) . ")\n";
    }
}
$log_message .= "--- [DEBUG END] ---\n";

// process.php와 같은 디렉토리에 로그 파일 기록
error_log($log_message, 3, 'game_debug.log');

// --------------------------------------------------------------------------------
// 📢📢📢 로그 파일 기록 영역 끝 📢📢📢
// --------------------------------------------------------------------------------


// 정답까지 걸린 시간 계산
$time_taken = microtime(true) - ($_SESSION['start_time'] ?? microtime(true));


// MySQL 연결
$conn = new mysqli('localhost', 'root', 'test1234', 'blankGame');
if ($conn->connect_error) {
    // DB 연결 실패 시 die 후에도 로그 파일은 유지됨
    die("DB 연결 실패: " . $conn->connect_error);
}
// DB 연결 직후 인코딩 설정 (한글 깨짐 방지)
$conn->set_charset("utf8mb4");


$nickname = $_SESSION['nickname'] ?? '';
$score = $is_correct ? 100 : 0;

// ranking 테이블에 저장 (닉네임 기준)
$stmt = $conn->prepare("INSERT INTO ranking (nickname, score) VALUES (?, ?)");
$stmt->bind_param("si", $nickname, $score);
$stmt->execute();
$stmt->close();


// 랭킹 조회 (점수 내림차순, 동점이면 먼저 제출한 순서)
$result = $conn->query("SELECT nickname, score, submission_time FROM ranking ORDER BY score DESC, submission_time ASC LIMIT 10");

// 결과 출력 (이전 디버그 출력은 모두 로그 파일로 이동되었으므로 결과만 깔끔하게 출력)
echo "<h1>결과</h1>";
echo "<p>플레이어: " . htmlspecialchars($nickname) . "</p>";
echo "<p>입력: " . htmlspecialchars($answer) . "</p>";
echo $is_correct ? "<p>정답! 🎉</p>" : "<p>틀렸습니다 😢</p>";
echo "<p>정답: <strong>" . implode(' / ', $correct_answers) . "</strong></p>";
echo "<p>걸린 시간: " . round($time_taken, 2) . "초</p>";

// 랭킹 출력
echo "<h2>랭킹</h2>";
echo "<ol>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . htmlspecialchars($row['nickname']) . " (" . htmlspecialchars($row['nickname']) . ") - 점수: " . $row['score'] . " (" . $row['submission_time'] . ")</li>";
}
echo "</ol>";

// 다음 문제로 이동 버튼 추가
echo '<form action="blankGame.php" method="get">';
echo '<button type="submit">다음 문제</button>';
echo '</form>';

$conn->close();

// 이전 문제 세션 초기화
unset($_SESSION['current_question']);
unset($_SESSION['start_time']);
?>