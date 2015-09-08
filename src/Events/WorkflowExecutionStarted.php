<?php namespace Phlow\Events;

class WorkflowExecutionStarted extends BaseEvent {

    private $flow;

    public function __construct($raw) {
        parent::__construct($raw);

        $this->inflate();
    }

    private function inflate() {
        $this->flow = json_decode(base64_decode($this->raw['workflowExecutionStartedEventAttributes']['input']), true);
    }

    public function getFlow() {
        return $this->flow;
    }
}