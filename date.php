<?php

$due = new \DateTime();
$due->setTimezone(new DateTimeZone('UTC'));
$due->setDate(2022, 6, 25);
$due->setTime(1, 0);
echo $due->format(DATE_RFC3339) . "\n";
echo $due->getTimestamp();
$due->modify('-1 day');
echo $due->format('D');
echo ' start date:' . $due->format(DATE_RFC3339 . ' D') . "\n";
$tasks = [];
$elapsedHours = 4;
foreach ([1, 2, 3, 5, 24, 0, 0.5, 0.5, 0.5] as $estimate) {
    if ($estimate == 0) {
        continue;
    }
    $task = ['estimate' => $estimate, 'start' => $due->format(DATE_RFC3339 . ' D')];
    if ($estimate > 8) {
        $estimate = $estimate / 8 * 4;
    }
    $elapsedHours -= $estimate;
    while ($elapsedHours < 0) {
        $due->modify('+1 day');
        if (in_array($due->format('D'), ['Sun', 'Sat'])) {
            continue;
        }
        $elapsedHours += 4;
    }
    $task['end'] = $due->format(DATE_RFC3339 . ' D');
    $task['elapsed'] = $elapsedHours;
    $tasks [] = $task;
}
foreach ($tasks as $task) {
    var_export($task);
}