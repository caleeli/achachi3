<?php
//$fileA = "/Users/davidcallizaya/Netbeans/bpm/protocolo.txt";
//$fileB = "/Users/davidcallizaya/Downloads/EscaneadoDeOriginalUTF8.txt";
$fileA = "data/protocolo.txt";
$fileB = "data/EscaneadoDeOriginalUTF8.txt";

$fileA = "data/segundaTranscripcion.txt";
$fileB = "data/segundaOriginal.txt";

$parseA = parse($fileA);
$parseB = parse($fileB);

/////////////////////////////

function parse($filename)
{
    return preg_split('/\s+/',
        trim(preg_replace('/\{[^{}]+\}/m', '',
                preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9{}]+/u', ' ',
                    file_get_contents($filename)))));
}

function fixTo($index, $to)
{
    global $parseA;
    global $parseB;
    if ($to == 1) {
        $parseB[$index] = $parseA[$index];
    } else {
        $parseA[$index] = $parseB[$index];
    }
    saveData($index);
}

function remove($index, $to)
{
    global $parseA;
    global $parseB;
    if ($to == 1) {
        array_splice($parseA, $index, 1);
    } else {
        array_splice($parseB, $index, 1);
    }
    saveData($index);
}

function saveData($index)
{
    global $fileA;
    global $fileB;
    global $parseA;
    global $parseB;
    saveArray($parseA, $fileA, $index);
    saveArray($parseB, $fileB, $index);
    header('Location: comparaDocs.php?i=' . $index);
    exit;
}

function saveArray($array, $filename, $index)
{
    $handler = fopen($filename, 'w');
    foreach ($array as $i => $word) {
        fwrite($handler, $i % 15 == 0 ? "\n" : " ");
        fwrite($handler, $word);
        if ($i == $index)
            fwrite($handler, '|');
    }
    fclose($handler);
}
////////////////////////
if (isset($_REQUEST['fixTo'])) {
    fixTo($_REQUEST['i'], $_REQUEST['fixTo']);
}
if (isset($_REQUEST['remove'])) {
    remove($_REQUEST['i'], $_REQUEST['remove']);
}
////////////////////////
$index = isset($_REQUEST['i']) ? floor($_REQUEST['i'] / 1000) * 1000 : 0;
foreach ($parseA as $i => $word) {
    if ($i < $index) {
        continue;
    }
    echo ($i % 15 == 0) ? "\n" : " ";
    if ($word != $parseB[$i]) {
        echo "[<a href='?i=", $i, "&fixTo=1'>$word</a> <a href='?i=", $i, "&remove=1'>&#10799;</a> ";
        //"<a href='?i=", $i, "&mark=1'>&#9999;</a>";
        echo " != ";
        echo "<a href='?i=", $i, "&fixTo=2'>", $parseB[$i], "</a> <a href='?i=", $i, "&remove=2'>&#10799;</a> ",
        //"<a href='?i=", $i, "&mark=2'>&#9999;</a>]",
        "]";
        //echo "[$word != ", $parseB[$i], "]";
        break;
    } else {
        echo $word;
    }
}

    