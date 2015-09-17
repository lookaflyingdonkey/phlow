<?php namespace Phlow\Deciders;

use Monolog\Logger;
use Monolog\Registry;
use Phlow\Job;

use Aws\Swf\Enum\EventType;
use Aws\Common\Aws;
use Aws\Swf\Enum\DecisionType;

/**
 * Determines what the next course of action should be for a Job.
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow\Deciders
 */
class Decider {
    private $domain = '';
    private $taskList = '';
    private $identity = '';

    private $aws;
    private $swfClient;

    /**
     * @var Job
     */
    private $job = null;

    private $decisions = array();

    /**
     *
     * @param Aws $aws The aws factory
     * @param string $domain AWS SWF Domain to watch
     * @param string $taskList AWS SWF task list to watch
     * @param string $identity The name this decider will take on
     */
    public function __construct(Aws $aws, $domain, $taskList, $identity = null, Logger $logger)
    {

        $this->setup($identity, $domain, $taskList, $logger);

        $this->aws = $aws;
        $this->swfClient = $this->aws->get('swf');
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

    private function addDecision(array $decision)
    {
        $this->decisions[] = $decision;
    }

    /**
     * @todo move this out to a class and look at the number key
     *
     * @param $flow
     * @param string $number
     * @return array
     */
    private function loadTask($flow, $number = '1')
    {
        $task = $flow['steps'][$number];
        Registry::PhlowLog()->addInfo("Starting Task: " . $task['task']['name']);
        if(isset($task['wait_for_signal']) && is_array($task['wait_for_signal']) && $task['wait_for_signal']['name'] != "") {
            Registry::PhlowLog()->addInfo("This task needs to wait for a signal from {$task['wait_for_signal']['name']}");
            if(!isset($this->job->getEventManager()->getSignalData()[$task['wait_for_signal']])) {
                $retries = $this->job->getEventManager()->getTimerCount($task['wait_for_signal']['name']);
                Registry::PhlowLog()->addInfo("This timer already tried {$retries} of {$task['wait_for_signal']['max_retries']} times...");
                if($retries < $task['wait_for_signal']['max_retries']) {
                    Registry::PhlowLog()->addInfo("Haven't received the signal yet, starting timer...");
                    return array(
                        'decisionType' => DecisionType::START_TIMER,
                        'startTimerDecisionAttributes' => array(
                            'timerId' => "{$number}-{time()}",
                            'control' => $task['wait_for_signal']['name'],
                            'startToFireTimeout' => (string) $task['wait_for_signal']['timer']
                        )
                    );
                }

                return array(
                    'decisionType' => DecisionType::FAIL_WORKFLOW_EXECUTION,
                    'failWorkflowExecutionDecisionAttributes' => array(
                        'reason' => "Too many retries waiting for signal {$task['wait_for_signal']['name']} for task {$task['name']}"
                    )
                );

            }
        }

        // @todo make the timeouts part of the JSON schema
        return array(
            'decisionType' => DecisionType::SCHEDULE_ACTIVITY_TASK,
            'scheduleActivityTaskDecisionAttributes' => array(
                'control' => $number,
                'activityType' => $task['task'],
                'activityId' => $task['task']['name'] . time(),
                'input' => base64_encode(json_encode($this->job->parseTaskInputs($task['inputs']))),
                'scheduleToCloseTimeout' => '3900',
                'taskList' => array('name' => strtolower("{$this->taskList}-{$task['task']['name']}-{$task['task']['version']}")),
                'scheduleToStartTimeout' => '300',
                'startToCloseTimeout' => '3600',
                'heartbeatTimeout' => '120'
            )
        );

    }


    /**
     * Waits for a decision task to come in from SWF and determines what the next action should be
     */
      public function pollForTasks()
      {
        while(true) {
          try {
            $taskData = $this->swfClient->pollForDecisionTask(array(
              'domain' => $this->domain,
              'taskList' => array(
                "name" => $this->taskList
              ),
              'identity' => $this->identity,
              'reverseOrder' => true
            ));
          } catch(Exception $e) {
            var_dump($e);
            die('here');
          }

          if($taskData['taskToken']) {
              $this->job = new Job($taskData->toArray());

              // @todo Move this out to another function/class
              $lastEvent = $this->job->getEventManager()->getLastEvent();

              switch ($lastEvent->getType()) {
                  case EventType::TIMER_FIRED:
                      $this->addDecision($this->loadTask($this->job->getEventManager()->getExecutionEvent()->getFlow()));
                      break;
                  case EventType::WORKFLOW_EXECUTION_STARTED:
                      $this->addDecision($this->loadTask($this->job->getEventManager()->getExecutionEvent()->getFlow()));
                      break;
                  case EventType::WORKFLOW_EXECUTION_SIGNALED:
                      Registry::PhlowLog()->addInfo("Received Signal {$lastEvent->getName()}");
                        $this->addDecision(array(
                            'decisionType' => DecisionType::RECORD_MARKER,
                            'recordMarkerDecisionAttributes' => array(
                                'markerName' => 'Signal',
                                'details' => "Incoming Signal of type {$lastEvent->getName()}"
                            )
                        ));
                      break;
                  case EventType::ACTIVITY_TASK_COMPLETED:
                      $eventCaller = $this->job->getEventManager()->getEvent($lastEvent->getScheduledEventId());
                      Registry::PhlowLog()->addInfo("Task Completed: {$eventCaller->getId()}");
                      $lastStep = $eventCaller->getControl();
                      // @todo Look at this and make a bit more robust
                      $nextStep = ($lastStep * 1) + 1;

                      if($nextStep <= sizeof($this->job->getEventManager()->getExecutionEvent()->getFlow()['steps'])) {
                          $this->addDecision($this->loadTask($this->job->getEventManager()->getExecutionEvent()->getFlow(), "".$nextStep));
                      } else {
                          Registry::PhlowLog()->addInfo("Task Completed: {$eventCaller->getType()}");
                          $this->addDecision(array(
                              'decisionType' => DecisionType::COMPLETE_WORKFLOW_EXECUTION,
                              'completeWorkflowExecutionDecisionAttributes' => array(
                                  'result' => 'FINAL RESULTS'
                              )
                          ));
                      }
                      break;
                  default:
                      // Do nothing
                      $this->addDecision(array(
                          'decisionType' => DecisionType::FAIL_WORKFLOW_EXECUTION,
                          'failWorkflowExecutionDecisionAttributes' => array(
                              'reason' => 'Error'
                          )
                      ));
              }

                if(sizeof($this->decisions) > 0) {
                    $this->swfClient->respondDecisionTaskCompleted(array(
                        'taskToken' => $this->job->getToken(),
                        'decisions' => $this->decisions
                    ));

                    $this->decisions = array();
                } else {
                    Registry::PhlowLog()->addInfo('Not responding with task completed');
                }
          }
        }
      }
    
}
