<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Phlow\Task;

class ExampleHandler implements Handler {

    /**
     * @param Task $task
     * @return array
     */
    public function handle(Aws $aws, Task $task)
    {

        $result = strtoupper($task->getInput("string"));

        return array(
            "string" => $result
        );

    }
}
