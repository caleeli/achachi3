<?php
require 'vendor/autoload.php';

use Achachi\GDrive;

$path = 'data/gdrive_token.json';
if (isset($_REQUEST['code'])) {
    $drive = new GDrive("http://localhost/AchachiX/processDrawIO.php", false);
    $token = $drive->fetchAccessToken($_REQUEST['code']);
    saveJson($path, $token);
    header('Location: ' . basename(__FILE__));
    return;
} else {
    $token = loadJson($path);
    $drive = new GDrive("http://localhost/AchachiX/processDrawIO.php", $token);
}

$authUrl = $drive->getAuthUrl();
echo '<a href="', htmlentities($authUrl, ENT_QUOTES), '">Connect to GDrive</a><br>';

if (!$token) {
    return;
}


$file = $drive->findPath('designs/Test1.html');
echo '<b><u>', $file->name, "</b></u><br>\n";
$html = $drive->getContent($file);

echo '<pre>';

eachDrawIOHtmlDiagrams($html,
                       function (DOMDocument $diagram, $name) {
    echo "$name\n";
    //echo htmlentities($diagram->saveXML());
    //echo $diagram->saveXML();
    $shapes = [];
    foreach ($diagram->getElementsByTagName('mxCell') as $cell) {
        if ($cell->getAttribute('edge')) {
            $type = 'edge';
        } else {
            $type = explode(';', $cell->getAttribute('style'))[0];
        }
        $id = $cell->getAttribute('id');
        $shape = [
            'id'     => $id,
            'parent' => $cell->getAttribute('parent'),
            'type'   => $type,
            'source' => $cell->getAttribute('source'),
            'target' => $cell->getAttribute('target'),
            'value'  => trim(strip_tags(str_replace('<br>', ' ', $cell->getAttribute('value')))),
        ];
        $shapes[$id] = $shape;
    }
    //prepare relationships
    $interfaces = [];
    $components = [];
    $codes = [];
    $TYPE_SHAPE = 'shape=lollipop';
    $TYPE_COMPONENT = 'shape=component';
    $TYPE_CODE = 'text';
    foreach ($shapes as $id => $shape) {
        if ($shape['type'] === $TYPE_SHAPE) {
            $interfaces[$shape['value']] = findDrawIOChain($shapes, $shape, function ($shape, $firtShape) {
                return $shape['parent'] === $firtShape['parent'];
            });
        }
        if ($shape['type'] === $TYPE_COMPONENT) {
            $parent = $shapes[$shape['parent']];
            $compName = $parent['value'];
            $components[$compName] = findDrawIOChildren($shapes, $parent, function ($shape) use ($TYPE_COMPONENT) {
                return $shape['type'] !== $TYPE_COMPONENT;
            });
        }
        if ($shape['type'] === $TYPE_CODE) {
            $target = 'Root';
            foreach ($shapes as $id => $conn) {
                if ($conn['source']===$shape['id']) {
                    $target = $conn['target'];
                    if ($conn['value']) {
                        $target = $target . '.' . $conn['value'];
                    }
                    break;
                }
            }
            $code = str_replace(json_decode('"\u00a0"'), ' ', $shape['value']);
            $codes[$target] = empty($codes[$target]) ? $code : $codes[$target] . "\n" . $code;
        }
    }
    $data = [
        'name' => $name,
        'interfaces' => $interfaces,
        'components' => $components,
        'codes' => $codes
    ];
    var_dump($data);
    nano2component('BpmnElement', $data, 'output/components');
});
