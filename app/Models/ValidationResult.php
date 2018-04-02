<?php

namespace App\Models;

use App\Models\TaskModel;

/**
 * Represents the result of a validation test.
 * Class ValidationResult
 * @package App\Models
 */
class ValidationResult {

  private $_messages;
  private $_validationStatus;

  /**
   * ValidationResult constructor.
   * @param bool $status
   * @param array $messages
   */
  public function __construct($status = false, $messages = []) {
    $this->_messages = [];
    $this->addMessage($messages);
    $this->_validationStatus = $status;
  }

  /**
   * Add informational validation messages to the result.
   * @param $message
   */
  public function addMessage($message) {
    if (is_array($message)) {
      $this->_messages = array_merge($this->getMessages(), $message);
    }
    else {
      $this->_messages[] = $message;
    }
  }

  /**
   * Get the validation messages as an array.
   * @return array
   */
  public function getMessages() {
    return $this->_messages;
  }

  /**
   * Get the validation success status.
   * @return bool
   */
  public function getValidationResult() {
    return $this->_validationStatus;
  }

  /**
   * Set the validation success status.
   * @param $theResult
   */
  public function setValidationResult($theResult) {
    $this->_validationStatus = $theResult;
  }
}