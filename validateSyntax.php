<?php
$source = '<?php $a=1';
$res = @token_get_all($source);
var_dump($res);
