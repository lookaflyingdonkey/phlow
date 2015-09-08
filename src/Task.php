<?php namespace Phlow;

/**
 * Used by the workers to represent the task to process
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow
 */
class Task {

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
     * Activity ID
     *
     * @var string
     */
    private $activityId = '';

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
     * The SWF name of this Activity Task
     *
     * @var string
     */
    private $name = '';

    /**
     * The SWF version of this Activity Task
     *
     * @var string
     */
    private $version = '';

    /**
     * The inputs sent to this Activity Task
     *
     * @var array
     */
    private $inputs = array();

    /**
     *
     *
     * @param $raw
     * @throws \Exception
     */
    public function __construct($raw)
    {
        $this->raw = $raw;
        $this->inflate();
    }

    /**
     * Inflate a Task object from the raw SWF activity message, also
     * parse the input options.
     *
     * @throws \Exception
     */
    private function inflate()
    {
        $this->token = $this->raw['taskToken'];
        $this->activityId = (int) $this->raw['activityId'];
        $this->startedEventId = $this->raw['startedEventId'];

        $this->workflowRunId = $this->raw['workflowExecution']['runId'];
        $this->workflowId = $this->raw['workflowExecution']['workflowId'];

        $this->name = $this->raw['activityType']['name'];
        $this->version = $this->raw['activityType']['version'];

        try {
            $this->inputs = json_decode(base64_decode($this->raw['input']), true);
        } catch (\Exception $e) {
            throw new \Exception('Input must be Base64 encoded JSON string');
        }
    }

    /**
     * Helper function to get a specific option or throw an exception to fail the job
     *
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getInput($name)
    {
        if(!isset($this->inputs[$name])) {
            throw new \Exception("Input {$name} not set");
        } else {
            return $this->inputs[$name];
        }
    }

    /**
     * Get the Task Token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get the activity ID of this Task
     *
     * @return string
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * Get the Event ID that started this Task
     *
     * @return int
     */
    public function getStartedEventId()
    {
        return $this->startedEventId;
    }

    /**
     * Get the Workflow Run ID that executed this Task
     *
     * @return string
     */
    public function getWorkflowRunId()
    {
        return $this->workflowRunId;
    }

    /**
     * Get the Workflow ID that executed this Task
     * @return string
     */
    public function getWorkflowId()
    {
        return $this->workflowId;
    }

    /**
     * Get the name of this Task
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the version of this Task
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the options array in full
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }



}