<?php
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

require 'vendor/autoload.php';

//descarga actas
function descargaActas()
{
    $time = strtotime('2019-10-25 21:09:30');
    //acta.2019.10.25.16.33.30.xlsx
    $actas = glob("actas/*.xlsx");
    $time = explode('.', basename($actas[0], 'xlsx'));
    
    for ($r = 0; $r < 500; $r++) {
        $name = 'acta.' . date('Y.m.d.H.i.s', $time) . '.xlsx';
        if (!file_exists("actas/$name")) {
            $url = 'https://computo.oep.org.bo/PubResul/' . $name;
            @file_put_contents("actas/$name", file_get_contents($url));
        }
        if (!filesize("actas/$name")) {
            unlink("actas/$name");
            echo "[**] $name\n";
            $time -= 1;
        } else {
            echo "[OK] $name\n";
            $time -= 60 * 3;
        }
    }
}
function analizaActas()
{
    $anterior = null;
    foreach(glob('actas/*.xlsx') as $filePath) {
        $name = basename($filePath);
        echo "$name\n";
        $anterior ? comparar($filePath, $anterior) : null;
        $anterior = $filePath;
    }
}
function dd(...$args)
{
    var_dump(...$args);
    die;
}
function cargarActa($filePath)
{
    $reader = ReaderEntityFactory::createReaderFromFile($filePath);
    $reader->open($filePath);
    $acta = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $i => $row) {
            if ($i == 1) {
                continue;
            }
            // do stuff with the row
            $cells = $row->getCells();
            $data = $row->toArray();
            $eleccion = $data[11];
            $numero = $data[9];
            $codigo = $data[10];
            if (isset($acta[$eleccion][$numero])) {
                throw new Exception("Acta duplicada '$eleccion' '$numero'");
            }
            $acta[$eleccion][$numero] = $data;
        }
    }
    $reader->close();
    return $acta;
}
function cachedCargarActa($filePath)
{
    return cached(md5($filePath), function () use ($filePath) {
        return cargarActa($filePath);
    });
}
function comparar($ultimo, $anterior)
{
    if (md5_file($anterior) == md5_file($ultimo)) {
        return;
    }
    $acta1m = cachedCargarActa($anterior);
    $acta2m = cachedCargarActa($ultimo);
    foreach ($acta2m as $eleccion => $acta2) {
        foreach ($acta2 as $key => $value) {
            if (isset($acta1m[$eleccion][$key])) {
                if ($acta1m[$eleccion][$key] != $acta2[$key]) {
                    $localidad = $acta1m[$eleccion][$key][5] . ', ' . $acta1m[$eleccion][$key][6];
                    echo "Cambio en $localidad: $key\n";
                    foreach ($acta1m[$eleccion][$key] as $i => $v1) {
                        $v2 = $acta2[$key][$i];
                        $v1 != $v2 ? print("$v1 => $v2\n") : null;
                    }
                }
                unset($acta1m[$eleccion][$key]);
            } else {
                echo "Nuevo registro: $key\n";
                echo json_encode($value) . "\n";
            }
        }
    }
    foreach ($acta1m as $eleccion => $acta1) if ($acta1) {
        echo "Elección: $eleccion\n";
        foreach ($acta1 as $key => $value) {
            echo "Eliminado: $key\n";
            echo json_encode($value) . "\n";
        }
    }
}
ini_set('memory_limit', -1);

analizaActas();
