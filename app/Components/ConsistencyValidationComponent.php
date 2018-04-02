<?php

namespace App\Components;

use App\Models\TaskModel;
use App\Models\ValidationResult;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

/**
 * Class ConsistencyValidationComponent
 * @package App\Components
 */
class ConsistencyValidationComponent {

  /**
   * @var array Array of possible parent types used for validation
   */
  private $_parentTypes = [
    'episode',
    'season',
    'special',
    'show'
  ];

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
   * Validates the data inside of a task to make sure that the show_slug,
   * parent_slug, and slug fields are together consistent with the data already
   * in the Media Manager
   *
   * @param TaskModel $task
   * @return bool
   */
  public function validate(TaskModel $task) {

    // Start by checking if this is an update to an existing asset. If it is,
    // then all of the needed validation data is in that object
    $asset = $this->_client->get_asset($task->slug);

    // If the asset check failed, check to see if it is an unpublished asset
    if (is_array($asset) && !$this->_hasData($asset)) {
      $asset = $this->_client->get_asset($task->slug, true);
    }

    $validationResult = new ValidationResult(true);
    $validationMessages = [];

    // If an asset was found the perform validation immediately
    if (is_array($asset) && $this->_hasData($asset)) {
      return $this->_validateAsset($asset, $task);
    } else {

      // If there is no asset to validate, try to validate the parent
      $parent = $task->isEpisodeTask() ?
        $this->_client->get_episode($task->parent_slug) :
        $this->_client->get_special($task->parent_slug);

      if (is_array($parent) && $this->_hasData($parent)) {
        return $this->_hasParent($parent, 'show', $task->show_slug);
      }
    }

    // If there was nothing else to check, make sure the show is available
    $show = $this->_client->get_show($task->show_slug);

    if (!is_array($show)) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage('No result found for show slug '.$task->show_slug);
    }
    if (!$this->_hasData($show)) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage('No data for show '.$task->show_slug);
    }
    return $validationResult;
  }

  /**
   * Checks if a response looks valid at face value
   *
   * @param array $response
   * @return bool
   */
  private function _hasData(array $response) {
    return $response !== null &&
      isset($response['data']) &&
      isset($response['data']['id']) &&
      !empty($response['data']['id']);
  }

  /**
   * Handles validating an asset response. This requires validating both the
   * parent_slug and the show_slug
   *
   * @param array $response
   * @param TaskModel $task
   * @return bool
   */
  private function _validateAsset(array $response, TaskModel $task) {

    if (
      !isset(
        $response['data'],
        $response['data']['attributes'],
        $response['data']['attributes']['parent_tree']
      )
    ) {
      return new ValidationResult(false, 'Asset does\'t have data, attributes, or parent_tree.');
    }

    $containerIsValid = $this->_hasParentInTree(
      $response['data']['attributes']['parent_tree']/**/,
      $task->isEpisodeTask() ? 'episode' : 'special',
      $task->parent_slug
    );

    $showIsValid = $this->_hasParentInTree(
      $response['data']['attributes']['parent_tree'],
      'show',
      $task->show_slug
    );

    $validation = new ValidationResult(true);
    if (!$containerIsValid) {
      $validation->setValidationResult(false);
      $validation->addMessage('Asset container is not valid.');
    }
    if (!$showIsValid) {
      $validation->setValidationResult(false);
      $validation->addMessage('Asset show is not valid.');
    }

    return $validation;
  }

  /**
   * Checks if a response contains a parent property of a specific type with a
   * specific slug
   *
   * @param array $response
   * @param $parentType
   * @param $parentSlug
   * @return bool
   */
  private function _hasParent(array $response, $parentType, $parentSlug) {

    $hasParentOfType = isset(
      $response['data'],
      $response['data']['attributes'],
      $response['data']['attributes'][$parentType],
      $response['data']['attributes'][$parentType]['attributes'],
      $response['data']['attributes'][$parentType]['attributes']['slug']
    );

    $validationResult = new ValidationResult(true);
    if (!$hasParentOfType) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage('Expected parent type to be '.$parentType);
    }
    if (!$this->_isParent($response['data']['attributes'][$parentType], $parentSlug)) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage('Expected parent slug to be '.$parentSlug);
    }
    return $validationResult;
  }

  /**
   * Checks if a response fragment has a given slug
   *
   * @param array $response
   * @param $parentSlug
   * @return bool
   */
  private function _isParent(array $response, $parentSlug) {
    return isset($response['attributes'], $response['attributes']['slug'])
      && $response['attributes']['slug'] === $parentSlug;
  }

  /**
   * Checks a parent_tree property in an asset response contains (at any depth)
   * a parent of a specific type and slug pair
   *
   * @param array $parentTree
   * @param $parentType
   * @param $parentSlug
   * @return bool
   */
  private function _hasParentInTree(array $parentTree, $parentType, $parentSlug) {

    // If the tree is missing attributes then fail
    if (!isset($parentTree['attributes'], $parentTree['type'])) {
      return false;
    }

    // If the current level matches, return
    if ($parentTree['type'] === $parentType &&
        $this->_isParent($parentTree, $parentSlug)) {
      return true;
    }

    // Otherwise check each of the possible parent types
    return array_reduce(
      $this->_parentTypes,
      function($matched, $type) use ($parentTree, $parentType, $parentSlug) {
        return $matched ||
          (
            isset($parentTree['attributes'][$type]) &&
            $this->_hasParentInTree(
              $parentTree['attributes'][$type],
              $parentType,
              $parentSlug
            )
          );
      },
      false
    );
  }
}