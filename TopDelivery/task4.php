<?php

require_once 'helpers.php';

/**
 * Задание 4 - выполнено за 2 часа
 * Необходимо с помощью РНР обработать массив заявок и выгрузить результат в файл csv
 * Где для каждой группы надо найти самый длинный непрерывный период и показать минимальную и максимальную даты непрерывного периода. Если периоды пересекаются, то они считаются непрерывными. В найденном непрерывном периоде надо рассчитать сумму всех заявок. В самой последней строке файла вывести номер группы и период с максимальной суммой заявок.
 */

$inputPath = __DIR__ . '/testPeriodIntake.csv';
$outputPath = __DIR__ . '/testPeriodIntake-result.csv';

$csv = new CsvParser();
$csv->Read($inputPath);
$dataset = $csv->Dataset();

usort($dataset, function($a, $b) {
    return $a[1] > $b[1]; // cmp period starts
});

$dataGroups = [];
foreach($dataset as $data)
{
    $key = $data[0]; // group id
    $dataGroups[$key][] = $data;
}

$mergedGroups = [];
foreach($dataGroups as $index => $group)
{
    while($currData = array_shift($group))
    {
        $mergedGroups[$index][] = MergePeriods($currData, $group);
    }

}

$resultDataset = array_merge(...$mergedGroups);
usort($resultDataset, function($a, $b) {
    return $a[3] < $b[3]; // cmp intake
});

$maxRow = $resultDataset[0];

usort($resultDataset, function($a, $b) {
    return $a[0] == $b[0] ? $a[1] > $b[1] : $a[0] > $b[0]; // cmp group then period start
});

$resultDataset[] = ['', '', '', ''];
$resultDataset[] = $maxRow;

$csv = new CsvParser();
$csv
    ->SetHeaders(['groupId', 'minPeriodBegin', 'maxPeriodEnd', 'sumIntakeItg'])
    ->SetDataset($resultDataset)
    ->Write($outputPath)
;

function MergePeriods($currData, &$dataset)
{
    if(count($dataset) == 0)
    {
        return $currData;
    }

    foreach($dataset as $index => $data)
    {
        if($data[1] >= $currData[1] && $data[1] <= $currData[2])
        {
            $currData[2] = max($currData[2], $data[2]); // period end
            $currData[3] += $data[3];
            unset($dataset[$index]);
        }
        else
        {
            break;
        }
    }

    return $currData;
}
