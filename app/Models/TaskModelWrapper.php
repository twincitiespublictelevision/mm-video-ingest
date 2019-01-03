<?php

namespace App\Models;

/**
 * Class TaskModelWrapper
 * @package App\Models
 */
class TaskModelWrapper implements Task {

  /**
   * @var TaskModel $_model
   */
  protected $_model;

  /**
   * TaskModelWrapper constructor.
   * @param TaskModel $model
   */
  public function __construct(TaskModel $model) {
    $this->_model = $model;
  }

  public function isEpisodeTask() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function isSpecialTask() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getParentPremiereYear() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getSeasonYear() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getSeasonNumber() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getEpisodeNumber() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getVideoUrl() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getCaptionUrl() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getImageUrl() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getEncoredOnAttribute($value) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getPremieredOnAttribute($value) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getParentEncoredOnAttribute($value) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getParentPremieredOnAttribute($value) {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function getImageData() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function hasAssetFailed() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function reset() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function retry() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function save() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function touch() {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function __call($name, $arguments) {
    return $this->_model->{$name}(...$arguments);
  }

  public function __get($name) {
    return $this->_model->{$name};
  }

  public function __set($name, $value) {
    return $this->_model->{$name} = $value;
  }

  public function __isset($name) {
    return isset($this->_model->{$name});
  }
}