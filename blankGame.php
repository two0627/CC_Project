<?php
session_start();
include 'dbConnect.php';
$conn->set_charset("utf8mb4");
$nickname = $_SESSION['nickname'] ?? '';

if (!$nickname) {
    echo "<p>로그인이 필요합니다.</p>";
    echo "<a href='loginPage.php'><button>로그인 페이지로 이동</button></a>";
    exit();
}


// ------------------- 문제 목록 -------------------
$questions = [
    ['question' => '독도는 ___ 땅이다', 'answer' => ['대한민국','한국']],
    ['question' => '한글은 ___이 만들었다', 'answer' => ['세종대왕']],
    ['question' => '한국의 수도는 ___이다', 'answer' => ['서울']],
    ['question' => '프랑스의 수도는 ___이다', 'answer' => ['파리','paris']]
];

// ------------------- 새 문제 선택 (세션이 비어 있을 때) -------------------
if (!isset($_SESSION['current_question'])) {
    $rand_index = array_rand($questions);
    $current_question = $questions[$rand_index];

    // 세션에 문제와 시작 시간 저장
    $_SESSION['current_question'] = $current_question;
    $_SESSION['start_time'] = microtime(true);
} else {
    $current_question = $_SESSION['current_question'];
}

// ------------------- 정답 제출 처리 -------------------
$nickname = $_SESSION['nickname'];
$is_correct = false;
$answer = trim($_POST['answer'] ?? '');

if ($answer !== '') {
    $correct_answers = $current_question['answer'];

    $user_answer_norm = mb_strtolower(trim(preg_replace('/\s+/', '', $answer)), 'UTF-8');

    foreach ($correct_answers as $ca) {
        $ca_norm = mb_strtolower(trim(preg_replace('/\s+/', '', $ca)), 'UTF-8');
        if ($user_answer_norm === $ca_norm) {
            $is_correct = true;
            break;
        }
    }

    $time_taken = microtime(true) - ($_SESSION['start_time'] ?? microtime(true));

    $score = $is_correct ? 100 : 0;

    // 랭킹 테이블에 기록
    $stmt = $conn->prepare("INSERT INTO ranking (nickname, score) VALUES (?, ?)");
    $stmt->bind_param("si", $nickname, $score);
    $stmt->execute();
    $stmt->close();

    // 랭킹 조회
    $result = $conn->query("SELECT nickname, score, submission_time FROM ranking ORDER BY score DESC, submission_time ASC LIMIT 10");
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>빈칸 채우기 게임</title>
</head>
<body>
    <h1>빈칸 채우기 게임</h1>
    <p>플레이어: <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    <form action="process.php" method="post">
        <p>
            <?php
            $display_question = str_replace('___', '<input type="text" name="answer" required>', $current_question['question']);
            echo $display_question;
            ?>
        </p>
        <input type="hidden" name="problem_type" value="<?php echo htmlspecialchars($current_question['question']); ?>">
        <button type="submit">제출</button>
    </form>
</body>
</html>

