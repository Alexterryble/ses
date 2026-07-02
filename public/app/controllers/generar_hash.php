<?php
$password_plana = 'password123';
$hash_generado = password_hash($password_plana, PASSWORD_DEFAULT); // PASSWORD_DEFAULT usa bcrypt
echo "El hash para '{$password_plana}' es: " . $hash_generado;
?>