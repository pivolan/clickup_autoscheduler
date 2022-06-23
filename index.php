<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/token.php';
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
    echo '0:' . $item1->name() . $item1->extra()['priority']['orderindex'] . '/' . $item1->timeEstimate() . '>' . $item2->name() . $item2->extra()['priority']['orderindex'] . '/' . $item2->timeEstimate() . "\n";
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
        return 0;
    }
    if (is_null($item2->timeEstimate())) {
        return 0;
    }
    if ((int)$item1->timeEstimate() > (int)$item2->timeEstimate()) {
        echo 'time:'.$item1->timeEstimate() .'>' .$item2->timeEstimate()."\n";
        return 1;
    }

    return 0;
});
foreach ($tasks as $n => $task) {
    $dateTimeImmutable = $task->startDate() ? $task->startDate()->format(DATE_RFC3339) : '-';
    $dueDate = $task->dueDate() ? $task->dueDate()->format(DATE_RFC3339) : '-';
    $priority = $task->priority() ? $task->priority()['priority'] : '-';
    println($n, $task->extra()['custom_id'], $task->name(), ($task->timeEstimate() / 3600 / 1000) . 'h', $priority, $dateTimeImmutable, $dueDate);
}
echo count($tasks);
function println()
{
    echo implode(' ', func_get_args()) . "\n";
}