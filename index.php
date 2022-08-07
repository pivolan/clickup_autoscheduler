<?php
const IGOR_PECHENIKIN = 'Igor Pechenikin';
const MANSUR_GAINETDINOV = 'Mansur Gainetdinov';
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
    if ($task->status()->type() === 'done') {
        println('closed', $key);
        continue;
    }
    if ($task->parentTaskId()) {
        println('subtask', $key);
        continue;
    }
    try {
        $task->assignees()->getByName(IGOR_PECHENIKIN);
    } catch (\RuntimeException $e) {
        continue;
    }
    $tasks[] = $task;
}
usort($tasks, function ($item1, $item2) {
    if (!isset($item1->extra()['priority']['orderindex'])&&!isset($item2->extra()['priority']['orderindex'])) {
        return 0;
    }
    if (!isset($item1->extra()['priority']['orderindex'])) {
        return 1;
    }
    if (!isset($item2->extra()['priority']['orderindex'])) {
        return -1;
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
//$due->modify('+01 day');
$due->setTimezone(new DateTimeZone('UTC'));
$elapsedHours = 4;
$tasksScheduled = [];
foreach ($tasks as $n => $task) {
    $dateTimeImmutable = $task->startDate() ? $task->startDate()->format(DATE_RFC3339) : '-';
    $dueDate = $task->dueDate() ? $task->dueDate()->format(DATE_RFC3339) : '-';
    $priority = $task->priority() ? $task->priority()['priority'] : '-';
    $priorityOrder = $task->priority() ? $task->priority()['orderindex'] : '-';
    //setCurrentDate
    $estimate = $task->timeEstimate() / 3600 / 1000;
    if ($estimate == 0) {
        continue;
    }
//    $taskScheduled = ['estimate' => $estimate, 'start' => $due->format(DATE_RFC3339 . ' D'), 'end' => '', 'name' => $task->name(), 'priority' => $priority, 'id' => $task->extra()['custom_id']];
    $taskScheduled = ['estimate' => $estimate, 'start' => $due->getTimestamp() * 1000, 'end' => '', 'name' => $task->name(), 'priority' => $priority, 'priority_orderindex'=>$priorityOrder, 'id' => $task->extra()['custom_id']];
    if ($estimate > 8) {
        $estimate = $estimate / 8 * 4;
    }
    $elapsedHours -= $estimate;
    while ($elapsedHours < 0) {
        $due->modify('+1 day');
        if (in_array($due->format('D'), ['Sun', 'Sat'])) {
            continue;
        }
        if ($due->format('D') == 'Mon') {
            $elapsedHours += 2;
        } else {
            $elapsedHours += 4;
        }
    }
    $taskScheduled['end'] = $due->getTimestamp() * 1000;
    $taskScheduled['elapsed'] = $elapsedHours;
    $task->edit(['due_date' => $taskScheduled['end'], 'start_date' => $taskScheduled['start']]);
    $tasksScheduled[] = $taskScheduled;
}
foreach ($tasksScheduled as $task) {
    var_export($task);
}