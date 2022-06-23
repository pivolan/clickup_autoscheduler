<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/token.php';
function println()
{
    echo implode(' ', func_get_args()) . "\n";
}

$client = new ClickUp\Client(TOKEN);

$team = $client->team('36631080');
$taskCollection = $team->spaces()->getByName('IT & QA')->taskFinder()->getCollection();
$tasks = [];
foreach ($taskCollection as $key => $task) {
    if ($task->status()->type() === 'closed') {
        println('closed', $key);
        continue;
    }
    if ($task->parentTaskId()) {
        println('subtask', $key);
        continue;
    }
    try {
        $task->assignees()->getByName('Igor Pechenikin');
    } catch (\RuntimeException $e) {
        continue;
    }
    $tasks[] = $task;
}
usort($tasks, function ($item1, $item2) {
    if (!isset($item1->extra()['priority']['orderindex'], $item2->extra()['priority']['orderindex'])) {
        return 0;
    }
    if ($item1->extra()['priority']['orderindex'] > $item2->extra()['priority']['orderindex']) {
        return 1;
    } elseif ($item1->extra()['priority']['orderindex'] < $item2->extra()['priority']['orderindex']) {
        return -1;
    }
    if ($item1->timeEstimate() == $item2->timeEstimate()) {
        return 0;
    }
    if (is_null($item1->timeEstimate())) {
        return 1;
    }
    if (is_null($item2->timeEstimate())) {
        return -1;
    }
    if ((int)$item1->timeEstimate() > (int)$item2->timeEstimate()) {
        return 1;
    } else {
        return -1;
    }
});
$due = new DateTime();
$due->setTime(1, 0);
$due->setTimezone(new DateTimeZone('UTC'));
$elapsedHours = 4;
$tasksScheduled = [];
foreach ($tasks as $n => $task) {
    $dateTimeImmutable = $task->startDate() ? $task->startDate()->format(DATE_RFC3339) : '-';
    $dueDate = $task->dueDate() ? $task->dueDate()->format(DATE_RFC3339) : '-';
    $priority = $task->priority() ? $task->priority()['priority'] : '-';
//    println($n, $task->extra()['custom_id'], $task->name(), ($task->timeEstimate() / 3600 / 1000) . 'h', $priority, $dateTimeImmutable, $dueDate);
//    if ($task->extra()['custom_id'] == 'IT-777') {
//        $task->edit(['due_date' => 1656464400000]);
//        $task->edit(['start_date' => 1656205200000]);
//    }
    //setCurrentDate
    $estimate = $task->timeEstimate() / 3600 / 1000;
    if ($estimate == 0) {
        continue;
    }
    $taskScheduled = ['estimate' => $estimate, 'start' => $due->format(DATE_RFC3339 . ' D'), 'end' => '', 'name' => $task->name(), 'priority' => $priority, 'id' => $task->extra()['custom_id']];
    $taskScheduled = ['estimate' => $estimate, 'start' => $due->getTimestamp() * 1000, 'end' => '', 'name' => $task->name(), 'priority' => $priority, 'id' => $task->extra()['custom_id']];
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
//    $taskScheduled['end'] = $due->format(DATE_RFC3339 . ' D');
    $taskScheduled['end'] = $due->getTimestamp() * 1000;
    $taskScheduled['elapsed'] = $elapsedHours;
    $task->edit(['due_date' => $taskScheduled['end'], 'start_date' => $taskScheduled['start']]);
    $tasksScheduled[] = $taskScheduled;
}
foreach ($tasksScheduled as $task) {
    var_export($task);
}