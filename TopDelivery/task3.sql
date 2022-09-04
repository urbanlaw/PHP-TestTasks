/**
 * Задание 3 - выполнено за 1 час
 * Показать количество доставленных и отказных заказов по каждому региону помесячно.
 * Для анализа необходимо выбрать заказы у которых статус выполнения 3-доставлен, 5 – отказ. И средствами SQL вывести статистику в виде:
 */

SELECT dir_regions.title as region_name, period, sum(delivered) as delivered, sum(rejected) as rejected
FROM (
    SELECT
        region_delivery_id as region_id,
        date_format(parcel_log.date_create, '%Y-%m') as period,
        count(*) as delivered,
        0 as rejected
    FROM parcel_log
    JOIN parcels USING(parcel_id)
    WHERE order_log_event_type_id = 10 AND new_value = 3
    GROUP BY region_id, period

    UNION ALL

    SELECT
        region_delivery_id as region_id,
        date_format(parcel_log.date_create, '%Y-%m') as period,
        0 as delivered,
        count(*) as rejected
    FROM parcel_log
    JOIN parcels USING(parcel_id)
    WHERE order_log_event_type_id = 10 AND new_value = 5
    GROUP BY region_id, period
) t
JOIN dir_regions ON region_id = dir_regions.id
GROUP BY region_id, period
ORDER BY region_name, period
