<?php namespace Phlow\Events;

class ActivityTaskStarted extends BaseEvent {
  private $identity;
  private $scheduledEventId;
  
  public function __construct($raw) {
    parent::__construct($raw);
    
    $this->inflate();
  }
  
  private function inflate() {
    $this->identity = $this->raw['activityTaskStartedEventAttributes']['identity'];
    $this->scheduledEventId = $this->raw['activityTaskStartedEventAttributes']['scheduledEventId'];
  }
}