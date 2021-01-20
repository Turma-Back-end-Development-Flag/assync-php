<?php

class Timer
{
  private DateTime $timer;

  public function startTime()
  {
    $this->timer = new DateTime();
  }

  public function getTime()
  {
    $time = new DateTime();
    return $time->diff($this->timer);
  }
}