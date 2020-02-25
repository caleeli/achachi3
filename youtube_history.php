<?php
ini_set("memory_limit",-1);

// YOUTUBE HISTORY PAGE
$url = '/browse_ajax?' . $token;

$file = fopen('history.csv', 'a');

function getHistory($url)
{
    $youtube = 'https://www.youtube.com';
    $curl = "curl '{$youtube}{$url}' $headers";

    exec($curl, $output);

    $response = implode('', $output);
    $json = json_decode($response, true);

    return $json;
}

function browseHistory($url)
{
    global $file;
    echo "===========================================\n";
    echo "$url\n";
    echo "===========================================\n";
    $json = getHistory($url);
    //render list
    $contents = @$json[1]['response']['continuationContents']['itemSectionContinuation']['contents'];
    if (is_array($contents)) {
        foreach($contents as $item){
            $title = @$item['videoRenderer']['title']['simpleText'];
            $video = 'https://www.youtube.com' . @$item['videoRenderer']['navigationEndpoint']['commandMetadata']['webCommandMetadata']['url'];
            echo "$video\t$title\n";
            fwrite($file, "$video\t$title\n");
        }
    }

    sleep(1);

    //go to next
    //$next = @$json[1]['endpoint']['urlEndpoint'];
    $next = @$json[1]['response']['continuationContents']['itemSectionContinuation']['continuations'];

    if (is_array($next)) {
        foreach ($next as $data) {
            $continuation = $data['nextContinuationData']['continuation'];
            $itct = $data['nextContinuationData']['clickTrackingParams'];
            $url = preg_replace('/&continuation=(.+)&itct=(.+)/', "&continuation={$continuation}&itct={$itct}", $url);
            browseHistory($url);
        }
    } else {
    }
}

browseHistory($url);
