<?php

require_once 'helpers.php';

/**
 * Задание 1 - выполнено за 2 часа
 * ТЗ - Показать количество созданных заказов, но не принятых на складе ТопДеливери.
 * Необходимо в разрезе каждого месяца и каждого Клиента показать количество заказов у которых нет записи в логе с типом события «2; Изменение статуса движения» и событием «3;Получен в ТД».
 * Способ решения SQL или РНР на выбор.
 */

$dbh = DBH::Factory();

$rows = $dbh->QueryArr("
    SELECT count(*) as qty, dir_webshops.title as client, date_format(date_create, '%Y-%m') as period
    FROM parcels 
    JOIN dir_webshops ON parcels.webshop_id = dir_webshops.id 
    WHERE parcel_id NOT IN (SELECT parcel_id FROM parcel_log WHERE order_log_event_type_id = 2 AND new_value = 3 GROUP BY parcel_id)
    GROUP BY period, webshop_id
    ORDER BY period, webshop_id
");

echo '<table>';
foreach($rows as $row)
{
    echo '<tr>';
    echo '<td>'.$row['period'].'</td>';
    echo '<td>'.$row['client'].'</td>';
    echo '<td>'.$row['qty'].'</td>';
    echo '</tr>';
}
echo '</table>';
