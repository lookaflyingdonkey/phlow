<?php namespace Phlow\Events;

class TimerStarted extends BaseEvent {

    private $control;

    public function __construct($raw) {
        parent::__construct($raw);

        $this->inflate();
    }

    private function inflate() {
        $this->control= $this->raw['timerStartedEventAttributes']['control'];
    }

    /**
     * @return mixed
     */
    public function getControl()
    {
        return $this->control;
    }

}