<?php namespace Phlow\Events;

class ActivityTaskScheduled extends BaseEvent {

    private $control;
    private $name;
    private $version;

    private $inputs;

  public function __construct($raw) {
    parent::__construct($raw);
    
    $this->inflate();
  }
  
  private function inflate() {
      $this->name = $this->raw['activityTaskScheduledEventAttributes']['activityType']['name'];
      $this->version = $this->raw['activityTaskScheduledEventAttributes']['activityType']['version'];
      $this->control = $this->raw['activityTaskScheduledEventAttributes']['control'];
      $this->inputs = json_decode(base64_decode($this->raw['activityTaskScheduledEventAttributes']['input']), true);
  }

    public function getName() {
        return $this->name;
    }

    public function version() {
        return $this->version;
    }

    public function getControl() {
        return $this->control;
    }

    public function getInputs() {
        return $this->inputs;
    }
}