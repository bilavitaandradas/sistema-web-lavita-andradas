<?php
$senha = "bem_suado"; //Substitua pela senha do usuário
$hash = password_hash(password: $senha, algo: PASSWORD_BCRYPT);
echo $hash;
?>