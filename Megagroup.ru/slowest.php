<?php

/**
 * ТЗ - Написать CLI на PHP для выборки 10ти самых медленных страниц из access.log nginx
 * Настройки логирования в ТЗ не описаны, предположительно последняя колонка - $upstream_response_time
 * соответственно по ней и считаем время выполнения скрипта
 * Использование - php slowest.php < %file_name%
 */

$contents = stream_get_contents(STDIN);
$lines = explode("\n", $contents);
$lines = array_filter($lines);

$responseTimes = [];
foreach($lines as $line)
{
    if(preg_match('~"GET\s(.+?)\s.+?"\s.+\s(.+?)$~', $line, $matches))
    {
        $request = $matches[1];
        $time = $matches[2];

        if(!isset($responseTimes[$request]))
        {
            $responseTimes[$request] = [];
        }

        $responseTimes[$request][] = $time;
    }
    else
    {
        echo 'Error in line: ' . $line . "\n\n";
    }
}

foreach($responseTimes as &$times)
{
    $times = array_sum($times) / count($times);
}

arsort($responseTimes);
$responseTimes = array_slice($responseTimes, 0, 10);

echo "\nTop 10 slowest requests is:\n";
foreach($responseTimes as $request => $time)
{
    echo '- ' . $request . ' - ' . $time . " sec\n";
}
echo "\n";
