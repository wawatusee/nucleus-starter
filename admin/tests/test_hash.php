<?php
// admin/test_hash.php
$hash = password_hash('motdepasse', PASSWORD_DEFAULT);
echo $hash;