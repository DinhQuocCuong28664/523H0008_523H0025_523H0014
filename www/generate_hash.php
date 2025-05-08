<?php
$password = 'ad123456'; // Thay 'your_password_here' bằng mật khẩu bạn muốn (ví dụ: 'admin123')
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Mật khẩu gốc: " . $password . "<br>";
echo "Mật khẩu đã mã hóa (bcrypt): " . $hashed_password . "<br>";
?>