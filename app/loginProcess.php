<?php
session_start();
if (!isset($_SESSION['nickname'])) {
    header("Location: loginPage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인 성공</title>
</head>
<body>
<h2>로그인 성공!</h2>
<p>환영합니다, <?php echo htmlspecialchars($_SESSION['nickname']); ?> 님!</p>
<a href="blankGame.php">문제를 풀러 가기</a>
</body>
</html>