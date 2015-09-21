<?php namespace Phlow;

use Phlow\Enum\SWF\EventType;
use Phlow\Events\Data\SignalData;
use Phlow\Events\Data\StepData;

/**
 * Tracks the events already processed on this Job
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow
 */
class EventManager {
    private $events = array();
    private $executionEvent = null;
    private $lastEvent = null;

    private $optionData = array();
    private $signalData = array();
    private $timers = array();

    public function addEvent($event)
    {
        if(isset($this->events[$event->getId()])) {
            throw new \Exception("Error, event '{$event->getId()}' has already been set");
        }

        $this->events[$event->getId()] = $event;
    }

    public function getEvent($id)
    {
        if(!isset($this->events[$id])) {
            throw new \Exception("Error, event '$id' isn't set");
        }

        return $this->events[$id];
    }

    public function setLastEvent($event)
    {
        if(!$this->lastEvent) {
            $this->lastEvent = $event->getId();
        }
    }

    public function getLastEvent()
    {
        return $this->events[$this->lastEvent];
    }

    public function setExecutionEvent($event)
    {
        $this->executionEvent = $event->getId();
    }

    public function getExecutionEvent()
    {
        return $this->events[$this->executionEvent];
    }

    private function setStepInputs($name, $data)
    {
        if(!isset($this->optionData[$name])) {
            $this->optionData[$name] = new StepData();
        }

        $this->optionData[$name]->setInputs($data);
    }

    private function setStepOutputs($name, $data)
    {
        if(!isset($this->optionData[$name])) {
            $this->optionData[$name] = new StepData();
        }

        $this->optionData[$name]->setOutputs($data);
    }

    private function setSignal($name, $data)
    {
        if(!isset($this->signalData[$name])) {
            $this->signalData[$name] = new SignalData();
        }

        $this->signalData[$name]->setInputs($data);
    }

    /**
     * Process all the past events and build an option set for use in other tasks
     *
     * @throws \Exception
     */
    public function buildOptionData()
    {
        foreach($this->events as $event) {
            switch($event->getType()) {
                case EventType::WORKFLOW_EXECUTION_STARTED:
                    // Set the job inputs in the global registry
                    $this->setStepInputs("job", $event->getFlow()['inputs']);
                    break;
                case EventType::WORKFLOW_EXECUTION_SIGNALED:
                    $this->setSignal($event->getName(), $event->getInputs());
                    echo "Setting Signal" . PHP_EOL;
                    var_dump($this->signalData);
                    break;
                case EventType::ACTIVITY_TASK_COMPLETED:
                    // Set outputs for this step in the global registry
                    $this->setStepOutputs("step" . $this->getEvent($event->getScheduledEventId())->getControl(), $event->getResults());
                    break;
                case EventType::ACTIVITY_TASK_SCHEDULED:
                    // Set inputs for this step in the global registry
                    $this->setStepInputs("step" . $event->getControl(), $event->getInputs());
                    break;
                case EventType::TIMER_STARTED:
                    var_dump($event);
                    // Count up timer starts for a task
                    if(isset($this->timers[$event->getControl()])) {
                        $this->timers[$event->getControl()] = $this->timers[$event->getControl()] + 1;
                    } else {
                        $this->timers[$event->getControl()] = 1;
                    }
                    break;
                default:
                    // Do nothing
            }
        }
    }

    /**
     * Takes an option string and tries to determine if it should be
     * mapped to another value (i.e. output of another task or signal)
     *
     * @param $option
     * @return mixed
     * @todo: Need to move the option collection out to a class
     */
    public function evaluateInput($option)
    {
        $parsed = parse_url($option);
        if(!isset($parsed["scheme"])) return $option;

        echo "Trying to evaluate " . $parsed['scheme'] . PHP_EOL;
        if($parsed['scheme'] == "signal" || in_array($parsed['scheme'], array_keys($this->optionData))) {
            if($parsed["scheme"] == "signal") {
                echo "Checking for signal data" . PHP_EOL;
                // Check if the signal has been triggered and grab the data
                $value = $this->signalData[$parsed["host"]]->getInput($parsed["path"]);
                if(!$value) return $option;

                return $value;
            }
            // Found a scheme with a mapping in our data
            if(!in_array($parsed['host'], array('inputs', 'outputs'))) {
                // isn't a valid path, return original
                return $option;
            }

            if($parsed['host'] == 'inputs') {
                echo "Inside inputs for {$parsed['scheme']}" . PHP_EOL;
                $value = $this->optionData[$parsed['scheme']]->getInput($parsed['path']);

                if(!$value) return $option;

                return $value;
            }

            if($parsed['host'] == 'outputs') {
                $value = $this->optionData[$parsed['scheme']]->getOutput($parsed['path']);

                if(!$value) return $option;

                return $value;
            }
        }

        return $option;
    }

    /**
     * Gets all the assembled option data
     *
     * @return array
     */
    public function getOptionData()
    {
        return $this->optionData;
    }

    /**
     * Gets all the signal data
     *
     * @return array
     */
    public function getSignalData()
    {
        return $this->signalData;
    }

    /**
     * Returns the number of times a particular timer has fired.
     * Used to manage retry counts
     *
     * @param $signal_name
     * @return int
     */
    public function getTimerCount($signal_name)
    {
        if(!isset($this->timers[$signal_name])) return 0;

        return $this->timers[$signal_name];
    }
}