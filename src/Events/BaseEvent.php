<?php namespace Phlow\Events;

class BaseEvent {
  
  protected $raw;
  
  private $id;
  private $timestamp;
  private $type;
  
  public function __construct($raw) {
    $this->raw = $raw;
    $this->inflateBasics();
  }
  
  private function inflateBasics() {
    $this->id = $this->raw['eventId'];
    $this->timestamp = $this->raw['eventTimestamp'];
    $this->type = $this->raw['eventType'];
  }

    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }
}