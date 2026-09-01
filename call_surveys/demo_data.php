<?php
// Demo fixture for call_surveys/index.php, shown when FEATURE_CALL_SURVEYS
// is off. Shape matches a real `SELECT num, operator, queue, valuation, date
// FROM survey` row exactly, so computeSurveyMetrics() treats it identically.

return [
    ['num' => '447911223344', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 5, 'date' => '2026-09-02 09:14:05'],
    ['num' => '447700900123', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 4, 'date' => '2026-09-02 08:52:11'],
    ['num' => '447811556677', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 3, 'date' => '2026-09-01 16:40:02'],
    ['num' => '447911223344', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 5, 'date' => '2026-09-01 14:21:49'],
    ['num' => '447700900456', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 2, 'date' => '2026-09-01 11:16:29'],
    ['num' => '447811556699', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 4, 'date' => '2026-09-01 09:03:18'],
    ['num' => '447911998877', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 4, 'date' => '2026-08-31 17:46:20'],
    ['num' => '447700112233', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 5, 'date' => '2026-08-31 13:53:53'],
    ['num' => '447811334455', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 1, 'date' => '2026-08-31 10:28:57'],
    ['num' => '447911223344', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 5, 'date' => '2026-08-30 15:32:46'],
    ['num' => '447700900123', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 3, 'date' => '2026-08-30 12:14:01'],
    ['num' => '447811556677', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 4, 'date' => '2026-08-30 09:08:30'],
    ['num' => '447911998877', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 4, 'date' => '2026-08-29 16:19:00'],
    ['num' => '447700112233', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 5, 'date' => '2026-08-29 11:44:46'],
    ['num' => '447811334455', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 3, 'date' => '2026-08-29 09:55:13'],
    ['num' => '447911223344', 'operator' => 'Anton', 'queue' => 'Support', 'valuation' => 5, 'date' => '2026-08-28 14:09:06'],
    ['num' => '447700900456', 'operator' => 'Leo',   'queue' => 'Sales',   'valuation' => 4, 'date' => '2026-08-28 10:32:50'],
    ['num' => '447811556699', 'operator' => 'Maria',  'queue' => 'Billing', 'valuation' => 2, 'date' => '2026-08-28 07:25:13'],
];
