<?php
use function GuzzleHttp\json_encode;

function connection($db = 'test', $host = '127.0.0.1', $username = 'root', $password = '')
{
    return new PDO("mysql:dbname=$db;host=$host", $username, $password);
}

function query(PDO $conn, $query, $fetchStyle = PDO::FETCH_ASSOC)
{
    return $conn->query($query)->fetchAll($fetchStyle);
}

function array2vector($array, $col = 0)
{
    $vector = [];
    foreach ($array as $row) {
        $vector[] = $row[$col];
    }
    return $vector;
}

function printVector($vector, $glue = "\n")
{
    print(implode($glue, $vector));
}

function filterLines($filter, $vector)
{
    $res = [];
    foreach ($vector as $row) {
        if (strpos($row, $filter) !== false) {
            $res[] = $row;
        }
    }
    return $res;
}

function readTCV($filename)
{
    $lines = explode("\n", file_get_contents($filename));
    array_walk(
        $lines,
        function (&$line) {
            $columns = explode("\t", $line);
            //var_dump($columns);die;
            array_walk(
                $columns,
                function (&$column) {
                    $column = substr($column, 0, 1) === '"' && substr($column, -1, 1) === '"' ? str_replace(
                        '""',
                        '"',
                        substr(
                            $column,
                            1,
                            -1
                                                                                                    )
            ) : $column;
                }
        );
            $line = $columns;
        }
    );
    return $lines;
}

function array2assoc($array)
{
    $res = [];
    foreach ($array as $cols) {
        $res[$cols[0]] = $cols[1];
    }
    return $res;
}

function saveJson($path, $json)
{
    file_put_contents($path, json_encode($json));
}

function loadJson($path)
{
    if (!file_exists($path)) {
        return false;
    }
    return json_decode(file_get_contents($path), true);
}

function decodeDrawIO($base64Deflated)
{
    return urldecode(gzinflate(base64_decode($base64Deflated)));
}

function eachDrawIOHtmlDiagrams($html, callable $callback)
{
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    $code = json_decode($dom->getElementsByTagName('div')->item(0)->getAttribute('data-mxgraph'));
    $design = new DOMDocument;
    $design->loadXML($code->xml);
    foreach ($design->getElementsByTagName('diagram') as $diagramTag) {
        $diagramEncoded = $diagramTag->nodeValue;
        $diagram = new DOMDocument;
        $diagram->loadXML(decodeDrawIO($diagramEncoded));
        $callback($diagram, $diagramTag->getAttribute('name'), $diagramTag->getAttribute('id'));
    }
}

function findDrawIOChain($shapes, $shape, callable $condition, &$res = [], $firstShape = null)
{
    $firstShape = isset($firstShape) ?: $shape;
    if ($firstShape === $shape || $condition($shape, $firstShape)) {
        $res[] = $shape;
        //forward
        foreach ($shapes as $flow) {
            if (in_array($flow, $res)) {
                continue;
            }
            if ($flow['source'] === $shape['id'] && $flow['target']) {
                $next = $shapes[$flow['target']];
                $res[] = $flow;
                findDrawIOChain($shapes, $next, $condition, $res, $firstShape);
            }
            if ($flow['target'] === $shape['id'] && $flow['source']) {
                $prev = $shapes[$flow['source']];
                $res[] = $flow;
                findDrawIOChain($shapes, $prev, $condition, $res, $firstShape);
            }
        }
    }
    return $res;
}

function findDrawIOChildren($shapes, $shape, callable $condition, &$res = [], $firstShape = null)
{
    $firstShape = isset($firstShape) ?: $shape;
    foreach ($shapes as $child) {
        if (in_array($child, $res)) {
            continue;
        }
        if ($child['parent'] === $shape['id'] && $condition($child, $firstShape)) {
            $res[] = $child;
            //findDrawIOChildren($shapes, $child, $condition, $res, $firstShape);
        }
    }
    return $res;
}

function nano2($file, $target)
{
    $file = realpath($file);
    $target = realpath($target);
    $path = getcwd();
    chdir('/Users/davidcallizaya/NetBeansProjects/nano2.1');
    echo "php artisan build $file $target", "\n";
    $exitCode = 0;
    passthru("/usr/local/bin/php artisan build $file $target 2>1", $exitCode);
    chdir($path);
    return $exitCode;
}

function nano2component($type, $data, $target)
{
    $filename = uniqid('output/') . '.xml';
    file_put_contents(
        $filename,
        '<?xml version="1.0" encoding="UTF-8"?>
    <root xmlns:v-bind="http://nano.com/vue">
    <script type="' . $type . '">
    ' . json_encode($data) . '
    </script>
    </root>'
    );
    $exitCode = nano2($filename, $target);
    if ($exitCode == 0) {
        //exit without errors
        unlink($filename);
    }
}

function addComposerLoader($path)
{
    return require $path;
}

/**
 * Busca todas las clases en PSR4 una carpeta concreta y namespace especifico
 * Require que este se haya cargado con composer.
 *
 * @param type $path
 * @param type $namespace
 * @param ReflectionClass $res
 * @return \ReflectionClass
 */
function findClasses($path, $namespace, &$res = [])
{
    foreach (glob("$path/*.php") as $filename) {
        $name = basename($filename, '.php');
        $className = "$namespace\\$name";
        $res[] = new ReflectionClass($className);
    }
    foreach (glob("$path/*", GLOB_ONLYDIR) as $dir) {
        $name = basename($dir);
        findClasses("$path/$name", "$namespace\\$name", $res);
    }
    return $res;
}

function drawio_Interface(DOMDocument $dom, $name, $stereotype = 'interface', $x = null, $y = null)
{
    if (!isset($x)) {
        $x = random_int(0, 400);
    }
    if (!isset($y)) {
        $y = random_int(0, 400);
    }
    $xml = '<mxCell id="' . uniqid() . '" value="«' . $stereotype . '»&lt;br style=&quot;font-size: 12px;&quot;&gt;&lt;b style=&quot;font-size: 12px;&quot;&gt;' . $name . '&lt;/b&gt;" style="html=1;shadow=0;comic=0;strokeColor=#000000;strokeWidth=1;fillColor=#FFFFFF;gradientColor=none;fontSize=12;fontColor=#000000;align=center;" vertex="1" parent="1">
        <mxGeometry x="' . $x . '" y="' . $y . '" width="110" height="50" as="geometry"/>
    </mxCell>';
    $dom2 = new DOMDocument;
    $dom2->loadXML($xml);
    return $dom->importNode($dom2->firstChild, true);
}

function drawio_Relationship(DOMDocument $dom, $source, $target)
{
    $xml = '<mxCell id="' . uniqid() . '" style="edgeStyle=none;rounded=0;html=0;entryX=0.5;entryY=1;startArrow=none;startFill=0;endArrow=block;endFill=0;jettySize=auto;orthogonalLoop=1;fontSize=12;fontColor=#000000;" edge="1" parent="1" source="' . $source . '" target="' . $target . '">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>';
    $dom2 = new DOMDocument;
    $dom2->loadXML($xml);
    return $dom->importNode($dom2->firstChild, true);
}

/**
 *
 * @param string $url
 *
 * @return DomDocument
 */
function loadHTML($url)
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTMLFile($url);
    libxml_clear_errors();
    return $dom;
}

function eachXpath(DOMNode $node, $query, $callback)
{
    $dom = $node instanceof DOMDocument ? $node : $node->ownerDocument;
    $xpath = new DOMXPath($dom);
    $ns = $dom->documentElement->namespaceURI;
    if ($ns) {
        $xpath->registerNamespace('ns', $ns);
    }
    $nodes = $node === $dom ? $xpath->query($query) : $xpath->query($query, $node);
    if ($nodes) {
        foreach ($nodes as $node) {
            $callback($node);
        }
    }
}

function domGetNextElement(DOMElement $element, $query, $callback)
{
    $first = true;
    eachXpath(
        $element->ownerDocument,
        $query,
        function (DOMElement $node) use ($element, $query, $callback, &$first) {
            if ($node->getLineNo() >= $element->getLineNo()) {
                if ($first) {
                    $callback($node);
                    $first = false;
                }
            }
        }
    );
}

/**
 * Print an error line that can be opened by Netbeans.
 *
 * @param type $error
 * @param type $filename
 * @param type $line
 */
function nbLogError($error, $filename, $line)
{
    if (strpos($filename, '/vendor/')) {
        return;
    }
    echo "$error in $filename on line $line\n";
}

/**
 * Get an array of files changed in $path using git.
 *
 * @staticvar type $res
 * @param type $path
 * @param type $baseBranch
 * @return type
 */
function gitGetChangedFiles($path, $baseBranch)
{
    static $res;
    if ($res === null) {
        $cwd = getcwd();
        chdir($path);
        $res = [];
        foreach (explode("\n", shell_exec('git diff --name-only ' . $baseBranch)) as $path) {
            $path = realpath($path);
            if ($path) {
                $res[] = $path;
            }
        }
        chdir($cwd);
    }
    return $res;
}

/**
 * Check if a file was changed.
 *
 * @param type $file
 * @param type $path
 * @param type $baseBranch
 * @return type
 */
function gitHasChanged($file, $path, $baseBranch)
{
    return in_array(realpath($file), gitGetChangedFiles($path, $baseBranch));
}

function eachFile(callable $callback, $path, $filter, $except)
{
    if (in_array($path, $except)) {
        return;
    }
    foreach (glob("$path/$filter") as $filename) {
        $callback($filename);
    }
    foreach (glob("$path/*", GLOB_ONLYDIR) as $dirname) {
        eachFile($callback, $dirname, $filter, $except);
    }
}

function plural($phrase)
{
    $e = substr($phrase, -1);
    $plural = ($e == 'y') ? 'ies' : (($e == 's' || $e == 'x' || $e == 'z' || $e == 'ch' || $e == 'sh') ? $e . 'es' : $e . 's');
    return substr($phrase, 0, -1) . $plural;
}

/**
 * Obtiene una conexion a GDrive.
 *
 * @return \Achachi\GDrive
 */
function achachiGDrive($callback)
{
    $tokenPath = __DIR__ . '/../data/gdrive_token.json';
    if (isset($_REQUEST['code'])) {
        $drive = new \Achachi\GDrive($callback, false);
        $token = $drive->fetchAccessToken($_REQUEST['code']);
        saveJson($tokenPath, $token);
        header('Location: ' . basename(__FILE__));
        return;
    } else {
        $token = loadJson($tokenPath);
        $drive = new \Achachi\GDrive($callback, $token);
    }

    $authUrl = $drive->getAuthUrl();
    echo '<a href="', htmlentities($authUrl, ENT_QUOTES), '">Connect to GDrive</a><br>';

    if (!$token) {
        return;
    }
    return $drive;
}

function googleExport($path, $mime = 'text/html', $callback = null)
{
    $drive = achachiGDrive('http://localhost/AchachiX/' . basename(__FILE__));
    $file = $drive->findPath($path);
    echo '<b><u>', $file->name, "</b></u><br>\n";
    $content = $drive->export($file, $mime);
    $callback ? $callback($content) : null;
    return $content;
}

/**
 * Graba una cadena en un archivo temporal y devuelve el nombre del archivo.
 *
 * @param string $data
 *
 * @return string
 */
function tempFile($data)
{
    $filename = uniqid('output/hoja/', true);
    file_put_contents($filename, $data);
    return $filename;
}

function unzip($file, $path)
{
    $zip = new ZipArchive;
    $res = $zip->open($file);
    if ($res === true) {
        // extract it to the path we determined above
        $zip->extractTo($path);
        $zip->close();
    }
}

function diffImages($image1, $image2)
{
    if ($image1 === $image2) {
        return 0;
    }
    $img1 = imagecreatefromstring(file_get_contents($image1));
    $img2 = imagecreatefromstring(file_get_contents($image2));
    $lum1 = normLumImage($img1);
    $lum2 = normLumImage($img2);
    $lum = 0;
    foreach ($lum1 as $i => $l) {
        $lum += ($lum2[$i] - $lum1[$i]) * ($lum2[$i] - $lum1[$i]);
    }
    $lum = sqrt($lum);
    return $lum;
}

function normLumImage($img, $normSize = 32, $sens = 1)
{
    $width = imagesx($img);
    $height = imagesy($img);
    $wn = $width / $normSize;
    $hn = $height / $normSize;
    $lum = [];
    $lumC = [];
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $lumP = ($r + $r + $b + $g + $g + $g) / 6;
            $xn = floor($x / $wn);
            $yn = floor($y / $hn);
            $index = $xn + $yn * $normSize;
            @$lumC[$index]++;
            @$lum[$index] += $lumP;
        }
    }
    foreach ($lum as $index => $l) {
        $lum[$index] = round($l / $lumC[$index] * $sens);
    }
    return $lum;
}

function indexImage($image)
{
    $img = imagecreatefromstring(file_get_contents_cached($image));
    //return implode(',', normLumImage($img, 8, 0.01));
    $factor = 0.5;
    $cc = ceil(256 * $factor);
    $lum = normLumImage($img, 2, $factor);
    $index = 0;
    $coef = 1;
    foreach ($lum as $l) {
        $index += $l * $coef;
        $coef *= $cc;
    }
    return $index;
}

function file_get_contents_cached($url)
{
    $md5 = md5($url);
    if (!file_exists('output/' . $md5)) {
        file_put_contents('output/' . $md5, file_get_contents($url));
    }
    return file_get_contents('output/' . $md5);
}

function cached($md5, $callable)
{
    if (!file_exists('output/' . $md5)) {
        file_put_contents('output/' . $md5, serialize($callable()));
    }
    return unserialize(file_get_contents('output/' . $md5));
}
