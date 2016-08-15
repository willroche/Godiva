<?php

namespace Drupal\flippy;

use Symfony\Component\EventDispatcher\Event;

class FlippyEvent extends Event {

  protected $queries;

  /**
   * FlippyEvent constructor.
   * 
   * @param array $queries
   */
  public function __construct(Array $queries) {
    $this->queries = $queries;
  }

  /**
   * Getter for query array.
   * 
   * @return array
   */
  public function getQueries() {
    return $this->queries;
  }

  /**
   * Setter for query array.
   * 
   * @param array $queries
   */
  public function setQueries($queries) {
    $this->queries = $queries;
  }

}
