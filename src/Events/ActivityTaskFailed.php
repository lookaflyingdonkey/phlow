<?php namespace Phlow\Events;

class ActivityTaskFailed extends BaseEvent {

    public function __construct($raw) {
        parent::__construct($raw);

        $this->inflate();
    }

    private function inflate() {
    }
}