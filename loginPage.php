<?php
include 'dbConnect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id']);
    $password = trim($_POST['password']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $success = 0; // 0 = 실패, 1 = 성공

    $stmt = $conn->prepare("SELECT * FROM login_info WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $success = 1; // 로그인 성공
            $_SESSION['nickname'] = $row['nickname'];
            // 성공 로그 저장
            $log = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, success) VALUES (?, ?, ?)");
            $log->bind_param("ssi", $user_id, $ip, $success);
            $log->execute();

            header("Location: loginProcess.php");
            exit();
        }
    }

    // 실패 로그 저장 (로그인 실패)
    $log = $conn->prepare("INSERT INTO login_logs (user_id, ip_address, success) VALUES (?, ?, ?)");
    $log->bind_param("ssi", $user_id, $ip, $success);
    $log->execute();

    $error = "로그인 실패! ID 또는 비밀번호를 확인하세요.";
}
?>


<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
<h2>로그인</h2>
<form method="POST">
    ID: <input type="text" name="user_id" required><br>
    비밀번호: <input type="password" name="password" required><br>
    <button type="submit">로그인</button>
</form>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>