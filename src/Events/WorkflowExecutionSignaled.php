<?php namespace Phlow\Events;

class WorkflowExecutionSignaled extends BaseEvent {

    private $inputs;
    private $name;

    public function __construct($raw) {
        parent::__construct($raw);

        $this->inflate();
    }

    private function inflate() {
        $this->name = $this->raw['workflowExecutionSignaledEventAttributes']['signalName'];
        $this->inputs = json_decode(base64_decode($this->raw['workflowExecutionSignaledEventAttributes']['input']), true);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getInputs()
    {
        return $this->inputs;
    }

}