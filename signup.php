<?php
include 'dbConnect.php';

// 회원가입 처리 로직
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $user_id = trim($_POST['user_id']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    // 비밀번호 확인
    if ($password !== $confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
        // 닉네임 중복 검사
        $checkNickname = $conn->prepare("SELECT * FROM login_info WHERE nickname = ?");
        $checkNickname->bind_param("s", $nickname);
        $checkNickname->execute();
        $resultNickname = $checkNickname->get_result();

        if ($resultNickname->num_rows > 0) {
            $error = "이미 존재하는 닉네임입니다. 다른 닉네임을 사용하세요.";
        } else {
            // ID 중복 검사
            $checkUserId = $conn->prepare("SELECT * FROM login_info WHERE user_id = ?");
            $checkUserId->bind_param("s", $user_id);
            $checkUserId->execute();
            $resultUserId = $checkUserId->get_result();

            if ($resultUserId->num_rows > 0) {
                $error = "이미 사용 중인 ID입니다. 다른 ID를 사용하세요.";
            } else {
                // 비밀번호 암호화
                $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

                // DB 저장
                $insert = $conn->prepare("INSERT INTO login_info (nickname, user_id, password) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $nickname, $user_id, $hashed_pw);

                if ($insert->execute()) {
                    header("Location: signupComplete.php");
                    exit();
                } else {
                    $error = "회원가입 중 오류가 발생했습니다.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
</head>
<body>
<h2>회원가입</h2>
<form method="POST">
    닉네임: <input type="text" name="nickname" required><br>
    ID: <input type="text" name="user_id" required><br>
    비밀번호: <input type="password" name="password" required><br>
    비밀번호 확인: <input type="password" name="confirm" required><br>
    <button type="submit">회원가입 완료</button>
</form>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
