<?php namespace Phlow\Events;

class ActivityTaskCompleted extends BaseEvent {
    private $scheduledEventId;
    private $startedEventId;

    private $results = null;

  public function __construct($raw) {
    parent::__construct($raw);
    
    $this->inflate();
  }
  
  private function inflate() {
      $this->scheduledEventId = $this->raw['activityTaskCompletedEventAttributes']['scheduledEventId'];
      $this->startedEventId = $this->raw['activityTaskCompletedEventAttributes']['startedEventId'];
      $this->results = json_decode(base64_decode($this->raw['activityTaskCompletedEventAttributes']['result']), true);
  }


    public function getScheduledEventId() {
        return $this->scheduledEventId;
    }

    public function getResults() {
        return $this->results;
    }
}