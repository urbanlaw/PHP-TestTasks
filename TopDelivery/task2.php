<?php

require_once 'helpers.php';

/**
 * Задание 2 - выполнено за 1 час 30 мин
 * Показать процент доставленных заказов от полученных в городе.
 * Заказ считается полученным в городе, если у него есть запись в логе с типом события «2; Изменение статуса движения» и событием «7;Получен в городе». Среди общего количества таких заказов необходимо выбрать доставленные, т.е. заказы у которых есть запись в логе с типом события «10;Изменение статуса выполнения» и событием «3;Полностью выполнен».
 * Способ решения SQL или РНР на выбор.
 */

$dbh = DBH::Factory();

$rows = $dbh->QueryArr("
    SELECT t1.parcel_id as parcel_id, new_value_title as city_name
    FROM parcel_log t1
    JOIN(
        SELECT  parcel_id, MAX(date_create) max_date
        FROM    parcel_log
        WHERE order_log_event_type_id = 9
        GROUP   BY parcel_id
    ) t2 ON t1.parcel_id = t2.parcel_id AND t1.date_create = t2.max_date
    WHERE order_log_event_type_id = 9
    ORDER BY city_name
");
$parcels = array_column($rows, null, 'parcel_id');

$rows = $dbh->QueryArr("
    SELECT parcel_id, 0 as isFulfilled
    FROM parcel_log 
    WHERE order_log_event_type_id = 2 AND new_value = 7
");
$received = array_column($rows, 'isFulfilled', 'parcel_id');

$rows = $dbh->QueryArr("
    SELECT parcel_id, 1 as isFulfilled
    FROM parcel_log 
    WHERE order_log_event_type_id = 10 AND new_value = 3
");
$fulfilled = array_column($rows, 'isFulfilled', 'parcel_id');

$stateMap = array_replace($received, $fulfilled);

foreach($parcels as $id => $parcel)
{
    if(!isset($stateMap[$id]))
    {
        unset($parcels[$id]);
        continue;
    }
    $parcels[$id]['state'] = $stateMap[$id];
}

$parcelSplit = [];
foreach($parcels as $parcel)
{
    $cityName = $parcel['city_name'];
    $parcelSplit[$cityName][] = $parcel;
}

$cityPercents = [];
foreach($parcelSplit as $city => $cityParcels)
{
    $arr = array_column($cityParcels, 'state');
    $cityPercents[$city] = number_format(array_sum($arr) / count($arr) * 100, 2);
}
unset($cityParcels);

echo '<table>';
foreach($cityPercents as $city => $percent)
{
    echo '<tr>';
    echo '<td>'.$city.'</td>';
    echo '<td>'.$percent.'%</td>';
    echo '</tr>';
}
echo '</table>';
