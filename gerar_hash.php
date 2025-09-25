<?php
$senha = "";
$hash = password_hash(password: $senha, algo: PASSWORD_BCRYPT);
echo $hash;
?>