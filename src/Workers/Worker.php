<?php namespace Phlow\Workers;

use Monolog\Logger;
use Monolog\Registry;
use Phlow\Task;
use Phlow\Workers\Handlers\Handler;

use Aws\Common\Aws;

/**
 * Takes a AWS ActivityTask and processes it and returns a result
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow\Workers
 */
class Worker {

    private $domain = '';
    private $taskList = '';

    private $aws;
    private $swfClient;

    private $handler;

    /**
     *
     * @param Aws $aws The aws factory
     * @param string $domain AWS SWF Domain to watch
     * @param string $taskList AWS SWF task list to watch
     * @param string $identity The name this worker will take on
     * @param Handler $handler The Class that will handle events caught by this worker
     */
    public function __construct(Aws $aws, $domain, $taskList, $identity, Handler $handler, Logger $logger)
    {
        $this->setup($identity, $domain, $taskList, $logger);
        $this->aws = $aws;
        $this->swfClient = $this->aws->get('swf');
        $this->handler = $handler;
    }

    /**
     * Set local variables and bootstrap logging
     *
     * @param string $domain
     * @param string $taskList
     */
    private function setup($identity, $domain, $taskList, Logger $logger)
    {
        if(!$identity) {
            $this->identity = get_class() . '-' . microtime();
        }

        $this->taskList = $taskList;
        $this->domain = $domain;

        // Setup logging
        if(!Registry::hasLogger('PhlowLog')) {
            Registry::addLogger($logger, 'PhlowLog');
        }
    }

    /**
     * Waits for an activity task to come in from SWF and directs it to the appropriate handler
     */
    public function pollForTasks()
    {
        while(true) {
            try {
                $taskData = $this->swfClient->pollForActivityTask(array(
                    'domain' => $this->domain,
                    'taskList' => array(
                        "name" => $this->taskList
                    ),
                    'identity' => get_class() . '-' . microtime()
                ));
            } catch(Exception $e) {
                var_dump($e);
                die('here');
            }


            if($taskData['taskToken']) {
                $task = new Task($taskData->toArray());

                Registry::PhlowLog()->addInfo('Trying to process request');

                try {
                    $results = $this->handler->handle($this->aws, $task);

                    Registry::PhlowLog()->addInfo('Task Handled successfully');
                    $this->swfClient->respondActivityTaskCompleted(array(
                        'taskToken' => $task->getToken(),
                        'result' => base64_encode(json_encode($results))
                    ));
                } catch (\Exception $e) {
                    Registry::PhlowLog()->addInfo('There was a problem handling this Task');
                    $this->swfClient->respondActivityTaskFailed(array(
                        'taskToken' => $task->getToken(),
                        'reason' => $e->getMessage()
                    ));
                }

            }
        }
    }

}
