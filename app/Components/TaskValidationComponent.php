<?php

namespace App\Components;

use App\Models\Task;
use App\Models\ValidationResult;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

/**
 * Class TaskValidationComponent
 * @package App\Components
 */
class TaskValidationComponent {

  const CONSISTENCY_CHECK_FAILED = "Client data consistency validation failed.";
  const PARAMETER_CHECK_FAILED = "Client parameter validation failed.";

  /**
   * @var PBS_Media_Manager_API_Client
   */
  private $_client;

  /**
   * Constructor method for the class
   */
  public function __construct(PBS_Media_Manager_API_Client $client) {
    $this->_client = $client;
  }

  /**
   * Returns validation results for parameter consistency and content.
   *
   * @param Task $task
   * @return ValidationResult
   */
  public function validate(Task $task) {

    $result = new ValidationResult(true);

    $consistencyValidator = new ConsistencyValidationComponent($this->_client);
    $consistencyValidationResult = $consistencyValidator->validate($task);
    if (! $consistencyValidationResult->getValidationResult()) {
      $result->setValidationResult(false);
      $result->addMessage(self::CONSISTENCY_CHECK_FAILED);
      $result->addMessage($consistencyValidationResult->getMessages());
    }

    $parameterValidator = new ParameterValidationComponent($this->_client);
    $parameterValidationResult = $parameterValidator->validate($task);
    if (! $parameterValidationResult->getValidationResult()) {
      $result->setValidationResult(false);
      $result->addMessage(self::PARAMETER_CHECK_FAILED);
      $result->addMessage($parameterValidationResult->getMessages());
    }

    return $result;

  }
}