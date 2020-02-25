<?php
$nombres = explode("\n", file_get_contents('data/nombres.txt'));
foreach ($nombres as $nombre) {
    $formula = [];
    $name = '';
    foreach (explode(',', $nombre) as $n) {
        $formula[] = 'SUMIF($Destinatario,"=' . $n . '",$Pendiente)';
        $name = strlen($n)>strlen($name) ? $n : $name;
    }
    //echo $name,"\n";
    echo '='.implode('+',$formula),"\n";
}
