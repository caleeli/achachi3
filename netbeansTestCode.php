#!/usr/bin/env php
<?php
if ($argv[1] == '-i') {
    die('The installed coding standards are MySource, PEAR, PSR1, PSR2, Squiz and Zend');
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
$testFile = array_pop($argv);//'tests/Feature/Api/Designer/ProcessManagerTest.php';
$options = '';
$php = '/usr/local/bin/php';
$logFile = uniqid('/tmp/') . '.log';
$cmd = "POPULATE_DATABASE=1 TESTING_VERBOSE=0 RUN_MSSQL_TESTS=0 DB_PASSWORD='' $php vendor/bin/phpunit $testFile $options 1>$logFile";
$log = 'storage/logs/processmaker-' . date('Y-m-d') . '.log';
if (!file_exists($log)) $log = $logFile;
$cwd = getcwd();
$exclude = [$cwd . '/vendor/'];
file_put_contents($log, '');
ob_start();
passthru($cmd, $returnVal);
$output = ob_get_contents();
ob_end_clean();

$response = file_get_contents($log);
if ($returnVal) {
    $response .= "\n\n" . $output;
}
$lines = explode("\n", $response);
$files = [];
$cleanError = false;
$error = '';
foreach ($lines as $line) {
    if (substr($line, 0, 1) === '#') {
        $txt = substr($line, 1);
        list($errNum, $txt) = explode(' ', $txt, 2);
        if (substr($txt, 0, 1) === '/') {
            list($file, $txt) = explode('(', $txt, 2);
            list($lineNum, $txt) = explode(')', $txt, 2);
            list($aux, $txt) = explode(' ', $txt, 2);
            if (!isset($files[$file])) $files[$file] = [];
            $files[$file][] = [
                'type'    => 'warning',
                'line'    => $lineNum,
                'error'   => $error,
                'message' => $error . "\n===============\n" . $txt,
            ];
        }
        $cleanError = true;
    } else {
        if ($cleanError) $error = '';
        $cleanError = false;
        $error .= $line . "\n";
        $regexp = '/(' . preg_quote($cwd, '/') . '[^:\n]+?)\:(\d+)/';
        if (preg_match($regexp, $line, $match)) {
            $files[$match[1]][] = [
                'type'    => 'error',
                'line'    => $match[2],
                'error'   => $error,
                'message' => $error . "\n===============\n" . $line,
            ];
        }
    }
}
if ($returnVal) {
    //Si no termino satisfactoriamente.
    $rp = realpath($testFile);
    if (!isset($files[$rp])) $files[$rp] = [];
    $files[$rp][] = [
            'type'    => 'error',
            'line'    => 1,
            'error'   => 'Output',
            'message' => $output,
        ];
}
?>

<phpcs version="3.2.2">
    <?php
    foreach ($files as $file => $errors) {
        foreach ($exclude as $ex) {
            if (strpos($file, $ex) === 0) continue 2;
        }
        echo '    <file name="' . $file . '" errors="' . count($errors) . '" warnings="0" fixable="' . count($errors) . '">', "\n";
        foreach ($errors as $error) {
            echo '        <' . $error['type'] . ' line="' . $error['line'] . '" column="1" source="'
            . htmlentities($error['error'], ENT_QUOTES) . '" severity="5" fixable="0">'
            . htmlentities($error['message']) . '</' . $error['type'] . '>', "\n";
        }
        echo '    </file>', "\n";
    }
    ?>

</phpcs>
