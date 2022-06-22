<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/token.php';
$client = new ClickUp\Client(TOKEN);

$team = $client->team('36631080');
foreach ($team->spaces()->getByName('IT & QA')->tasks() as $task) {
    $dateTimeImmutable = $task->startDate()?$task->startDate()->format(DATE_RFC3339):'-';
    $dueDate = $task->dueDate()?$task->dueDate()->format(DATE_RFC3339):'-';
    echo $task->id().$task->name().$task->timeEstimate().$task->status()->name().$task->orderindex().$task->priority()['priority']. $dateTimeImmutable . $dueDate ."\n";
    var_export($task->extra()['custom_id']);
    return;
}