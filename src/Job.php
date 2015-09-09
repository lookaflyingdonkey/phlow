<?php namespace Phlow;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Registry;
use Phlow\Events\Data\SignalData;
use Phlow\Events\Data\StepData;

use Aws\Swf\Enum\EventType;

/**
 * Represents a Job Run, used by the decider to route tasks
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow
 */
class Job {

    /**
     * Holds the raw data from SWF
     *
     * @todo Remove this after inflating, no need to keep
     * @var array
     */
    private $raw;

    /**
     * Task token
     *
     * @var string
     */
    private $token = '';

    /**
     * The Event that started this request
     *
     * @var int
     */
    private $startedEventId = -1;

    /**
     * The Unique Run ID of the current job
     *
     * @var string
     */
    private $workflowRunId = '';

    /**
     * The SWF Workflow ID of this job
     *
     * @var string
     */
    private $workflowId = '';

    /**
     * The SWF name of this job
     *
     * @var string
     */
    private $workflowName = '';

    /**
     * The SWF version of this job
     *
     * @var string
     */
    private $workflowVersion = '';

    /**
     * An EventManager object to hold our collection of events
     *
     * @var EventManager
     */
    private $eventManager;


    private $previousStartedEventId = -1;

    public function __construct($raw)
    {
        $this->eventManager = new EventManager();

        // Setup logging
        if(!Registry::hasLogger('PhlowLog')) {
            $logger = new Logger('phlow');
            $logger->pushHandler(new StreamHandler("phlow.log"));
            Registry::addLogger($logger, 'PhlowLog');
        }

        $this->raw = $raw;
        $this->inflate();
    }

    private function inflate()
    {
        Registry::PhlowLog()->addInfo('Inflating');
        $this->workflowName = $this->raw['workflowType']['name'];
        $this->workflowVersion = $this->raw['workflowType']['version'];

        $this->workflowRunId = $this->raw['workflowExecution']['runId'];
        $this->workflowId = $this->raw['workflowExecution']['workflowId'];

        $this->token = $this->raw['taskToken'];
        $this->previousStartedEventId = $this->raw['previousStartedEventId'];
        $this->startedEventId = $this->raw['startedEventId'];

        foreach($this->raw['events'] as $raw_event) {
            switch($raw_event['eventType']) {
                case EventType::WORKFLOW_EXECUTION_STARTED:
                    $event = new \Phlow\Events\WorkflowExecutionStarted($raw_event);
                    $this->eventManager->setExecutionEvent($event);
                    $this->eventManager->setLastEvent($event);
                    break;
                case EventType::ACTIVITY_TASK_COMPLETED:
                    $event = new \Phlow\Events\ActivityTaskCompleted($raw_event);
                    $this->eventManager->setLastEvent($event);
                    break;
                case EventType::ACTIVITY_TASK_FAILED:
                    $event = new \Phlow\Events\ActivityTaskFailed($raw_event);
                    $this->eventManager->setLastEvent($event);
                    break;
                case EventType::ACTIVITY_TASK_SCHEDULED:
                    $event = new \Phlow\Events\ActivityTaskScheduled($raw_event);
                    break;
                case EventType::ACTIVITY_TASK_STARTED:
                    $event = new \Phlow\Events\ActivityTaskStarted($raw_event);
                    break;
                case EventType::WORKFLOW_EXECUTION_SIGNALED:
                    $event = new \Phlow\Events\WorkflowExecutionSignaled($raw_event);
                    break;
                case EventType::TIMER_STARTED:
                    $event = new \Phlow\Events\TimerStarted($raw_event);
                    break;
                default:
                    $event = new \Phlow\Events\BaseEvent($raw_event);
            }
            $this->eventManager->addEvent($event);
        }
        $this->eventManager->buildOptionData();
    }

    public function getToken()
    {
        return $this->token;
    }

    public function parseTaskInputs($options)
    {
        foreach($options as $key => $val) {
            if(is_array($val)) {
                $options[$key] = $this->parseTaskInputs($val);
            } else {
                $options[$key] = $this->eventManager->evaluateInput($val);
            }

        }
        return $options;

    }

    public function getEventManager()
    {
        return $this->eventManager;
    }
}