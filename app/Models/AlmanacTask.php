<?php

namespace App\Models;

/**
 * Class AlmanacTaskModel
 * @package App\Models
 */
class AlmanacTask extends TaskModelWrapper {

  /**
   * Extracts the four digit year that indicates the season of the parent. This
   * handles computing the appropriate year based on the TPT fiscal year, as
   * opposed to the calendar year.
   *
   * @return string
   */
  public function getSeasonYear() {
    $month = (int) substr($this->_model->parent_premiered_on, 5, 2);
    $year = (int) $this->getParentPremiereYear();

    if ($month >= 9) { // Fiscal year starts in September
      return $year + 1;
    } else {
      return $year;
    }
  }
}