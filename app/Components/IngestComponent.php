<?php

namespace App\Components;

use App\Models\Task;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

/**
 * Class IngestComponent
 * @package App\Components
 */
class IngestComponent {

  const VIDEO_CLEAR = 'cleared';
  const VIDEO_IN_PROGRESS = 'pending';
  const VIDEO_DONE = 'done';
  const VIDEO_FAILED = 'failed';

  const CAPTION_CLEAR = 'cleared';
  const CAPTION_IN_PROGRESS = 3;
  const CAPTION_DONE = 1;
  const CAPTION_FAILED = 0;

  /**
   * @var PBS_Media_Manager_API_Client
   */
  private $_client;

  /**
   * @var TaskValidationComponent
   */
  private $_validator;

  /**
   * IngestComponent constructor.
   * @param PBS_Media_Manager_API_Client $client
   * @param TaskValidationComponent $validator
   */
  public function __construct(PBS_Media_Manager_API_Client $client, TaskValidationComponent $validator) {
    $this->_client = $client;
    $this->_validator = $validator;
  }

  /**
   * Attempts to ingest a task via the Media Manager API. If container objects
   * need to be created then they will try to be created. This may result in
   * Special, Season, and/or Episode objects being created.
   *
   * If any container step fails then the entire ingest will fail.
   *
   * @param Task $task The task to attempt to ingest
   * @return string A status string defined by the Task
   */
  public function ingest(Task $task) {

    // If the task is starting in the pending state then prior to ingesting,
    // perform a validation step
    if ($task->status !== Task::PENDING || $this->_validator->validate($task)) {

      // Determine if the task should be ingested as a special or an episode
      if ($task->episode_number === null) {
        $task->status = $this->_ingestToSpecial($task);
      } else {
        $task->status = $this->_ingestToEpisode($task);
      }
    } else {

      // If validation fails, then immediately cancel the task
      $task->status = Task::CANCELLED;
      $task->failure_reason = 'Task slugs are inconsistent with existing data in Media Manager';
    }

    // After performing the ingestion, save the model status to the db
    $task->save();

    // Finally return back the status to anyone listening
    return $task->status;
  }

  /**
   * Ingests the task to a special container. If necessary an attempt will be
   * made to create a Special container.
   *
   * If a Special container to save to can not be determined, then the task
   * will fail.
   *
   * @param Task $task The task to attempt to ingest
   * @return string A status string defined by the Task
   */
  private function _ingestToSpecial(Task $task) {

    // Lookup the special id to use for creation or create a special if one
    // does not already exist
    $specialResp = $this->_getSpecialId($task);

    // Make sure that a season was able to be fetched
    if ($specialResp && is_string($specialResp)) {
      return $this->_ingestToContainer($task, $specialResp, 'special');
    }

    // Log the failure reason to the task
    $task->failure_reason = json_encode($specialResp);

    // If a special couldn't be fetched then fail
    return Task::SPECIAL_FAILED;
  }

  /**
   * Attempts to find a Special container to add the Asset to. If one can
   * not be found then one wil be created.
   *
   * If both lookup and creation fail then the entire task will fail
   *
   * @param Task $task The task to determine a container for
   * @return string An id string that uniquely defines a Special
   */
  private function _getSpecialId(Task $task) {

    // Check to see if a Special already exists
    $special = $this->_client->get_special($task->parent_slug);

    // If one exists than return its id
    if ($special !== null &&
        isset($special['data']) &&
        isset($special['data']['id']) &&
        !empty($special['data']['id'])) {
      return $special['data']['id'];
    }

    // If a Special can not be found, then try to generate one
    return $this->_client->create_child(
      $task->show_slug,
      'show',
      'special',
      [
        'title' => $task->parent_title,
        'description_short' => $task->parent_description_short,
        'description_long' => $task->parent_description_long,
        'slug' => $task->parent_slug
      ]
    );
  }

  /**
   * Ingests the task to an Episode container inside of a Season container.
   * If necessary an attempt will be made to create both a Season container and
   * an Episode container.
   *
   * If at any point a container can not be determined the ingest will fail
   *
   * @param Task $task The task to attempt to ingest
   * @return string A status string defined by the Task
   */
  private function _ingestToEpisode(Task $task) {

    // Lookup the season id to use for creation or create a season if one
    // does not already exist
    $seasonResp = $this->_getSeasonId($task);

    // Make sure that a season was able to be fetched
    if ($seasonResp && is_string($seasonResp)) {
      $episodeResp = $this->_getEpisodeId($task, $seasonResp);

      // Make sure that an episode was able to be fetched
      if ($episodeResp && is_string($episodeResp)) {
        return $this->_ingestToContainer($task, $episodeResp, 'episode');
      }

      // Log the failure reason to the task
      $task->failure_reason = json_encode($episodeResp);

      // If an episode couldn't be fetched then fail
      return Task::EPISODE_FAILED;
    }

    // Log the failure reason to the task
    $task->failure_reason = json_encode($seasonResp);

    // If a season couldn't be fetched then fail
    return Task::SEASON_FAILED;
  }

  /**
   * Attempts to find a Season container to add the Episode to. If one can
   * not be found then one wil be created.
   *
   * If both lookup and creation fail then the entire task will fail
   *
   * @param Task $task The task to determine a container for
   * @return string An id string that uniquely defines a Season
   */
  private function _getSeasonId(Task $task) {

    // Look up the Season from the task
    $season = $this->_lookupSeason($task);

    // If one exists than return its id
    if ($season !== null &&
        isset($season) &&
        !empty($season['id'])) {
      return $season['id'];
    }

    // If it could not be found, then try to create a new one
    $show = $this->_client->get_show($task->show_slug);

    if (isset($show['data']) &&
      isset($show['data']['attributes']) &&
      isset($show['data']['attributes']['ordinal_season'])) {

      return $this->_client->create_child(
        $task->show_slug,
        'show',
        'season',
        [
          'ordinal' => $show['data']['attributes']['ordinal_season'] ? $task->getSeasonNumber() : $task->getSeasonYear()
        ]
      );
    }

    return null;
  }

  /**
   * Searches the Media Manager API for a Season object to create the
   * Episode under. If a matching one can not be found, null is returned
   *
   * @param Task $task The task to lookup a season for
   * @return array|null Returns the id of the found season or null if none can be found
   */
  private function _lookupSeason(Task $task) {

    // Look up the show with the Media Manager
    $show = $this->_client->get_show($task->show_slug);

    if (isset($show['data']) &&
        isset($show['data']['attributes']) &&
        isset($show['data']['attributes']['ordinal_season'])) {

      // Search for the season in the Media Manager API
      $seasons = $this->_client->get_show_seasons(
        $task->show_slug,
        [
          'ordinal' => $show['data']['attributes']['ordinal_season'] ? $task->getSeasonNumber() : $task->getSeasonYear()
        ]
      );

      // If a result is found, return the result. Otherwise return null
      return isset($seasons[0]) ? $seasons[0] : null;
    }

    return null;
  }

  /**
   * Attempts to find an Episode container to add the Asset to. If one can
   * not be found then one wil be created.
   *
   * If both lookup and creation fail then the entire task will fail
   *
   * @param Task $task The task to determine a container for
   * @param string $seasonId The season to create under if an episode is not found
   * @return string An id string that uniquely defines a Episode
   */
  private function _getEpisodeId(Task $task, $seasonId) {

    // Look up the Episode from the task
    $episode = $this->_client->get_episode($task->parent_slug);

    // If an Episode is found, return its id
    if ($episode !== null &&
        isset($episode['data']) &&
        isset($episode['data']['id']) &&
        !empty($episode['data']['id'])) {
      return $episode['data']['id'];
    }

    // Check the encore date to make sure it is not empty. If it is then use
    // the premiered date instead
    if (strtotime($task->parent_encored_on) < 0) {
      $encored_on = $task->parent_premiered_on;
    } else {
      $encored_on = $task->parent_encored_on;
    }

    // If one can not be found, then try to create one
    return $this->_client->create_child(
      $seasonId,
      'season',
      'episode',
      [
        'title' => $task->parent_title,
        'description_short' => $task->parent_description_short,
        'description_long' => $task->parent_description_long,
        'slug' => $task->parent_slug,
        'ordinal' => $task->getEpisodeNumber(),
        'premiered_on' => date('Y-m-d', strtotime($task->parent_premiered_on)),
        'encored_on' => date('Y-m-d', strtotime($encored_on))
      ]
    );
  }

  /**
   * Attempts to ingest a task into a provided container. The container may be
   * either an episode or a special. If an object already exists it will be
   * updated. If there is not then one will be created.
   *
   * @param Task $task The task to ingest
   * @param string $parentId The id of the container to ingest to
   * @param string $parentType The type of the container to ingest to
   * @return string A status string defined by the Task
   */
  private function _ingestToContainer(Task $task, $parentId, $parentType) {

    // Initially check the task for a content id. If a content id has been
    // determined for the task then perform the PATCH to request to ingest
    // the media files. Otherwise lookup or create the asset object to get
    // a content id and prep the asset by deleting any stored video files
    if (isset($task->pbs_content_id) && $task->pbs_content_id) {

      // Update the asset
      $updateResp = $this->_addAssetMediaFiles($task);

      // Check that the asset successfully updated
      if ($updateResp === true) {
        return Task::IN_PROGRESS;
      }

      // Log the asset failure to the task
      $task->failure_reason = json_encode($updateResp);
    } else {

      // Try to fetch the asset
      $assetResp = $this->_getAssetId($task, $parentId, $parentType);

      // Make sure that an asset was able to be fetched
      if ($assetResp && is_string($assetResp)) {

        // Update the task with the appropriate asset id
        $task->pbs_content_id = $assetResp;

        // Fetch the current representation of the task
        $response = $this->_client->get_updatable_object($task->pbs_content_id, 'asset');

        // An id has been determined, now make sure that the asset media
        // files are cleared
        $clearResult = $this->_clearAssetMediaFiles($task, $response);

        // If both fields are already cleared, then move the task to staged,
        // otherwise leave the task in staging
        if ($clearResult === true) {
          return Task::STAGED;
        } else {
          return Task::STAGING;
        }
      }

      // Log the asset failure to the task
      $task->failure_reason = json_encode($assetResp);
    }

    // Return an asset failure
    return Task::ASSET_FAILED;
  }

  /**
   * Attempts to find an asset container to update beneath the parent object.
   * If one can not be found then one wil be created.
   *
   * If both lookup and creation fail then the entire task will fail
   *
   * @param Task $task The task to update or create
   * @param string $parentId Parent object to create under if necessary
   * @param string $parentType Parent object type to create under if necessary
   * @return string An id string that uniquely defines an asset
   */
  private function _getAssetId(Task $task, $parentId, $parentType) {

    // Look up the Asset from the task
    $asset = $this->_client->get_asset($task->slug);

    // If an asset is found, return its id
    if ($asset !== null &&
      isset($asset['data']) &&
      isset($asset['data']['id']) &&
      !empty($asset['data']['id'])) {
      return $asset['data']['id'];
    } else {

      // If we can not find the asset, check the unpublished space
      $assetPriv = $this->_client->get_asset($task->slug, true);

      if ($assetPriv !== null &&
        isset($assetPriv['data']) &&
        isset($assetPriv['data']['id']) &&
        !empty($assetPriv['data']['id'])) {
        return $assetPriv['data']['id'];
      }
    }

    return $this->_createAsset($task, $parentId, $parentType);
  }

  /**
   * Attempts to create an Asset object underneath a provided parent object.
   * After creating an asset object, it will attempt to attach the video,
   * caption, and image files.
   *
   * If either step fails then the entire task will fail.
   *
   * @param Task $task The task to create an Asset from
   * @param string $parentId The id of the parent to assign the Asset to
   * @param string $parentType The type of the parent to assign the Asset to
   * @return string A status string defined by the Task
   */
  private function _createAsset(Task $task, $parentId, $parentType) {

    // Check the encore date to make sure it is not empty. If it is then use
    // the premiered date instead
    if (strtotime($task->encored_on) < 0) {
      $encored_on = $task->premiered_on;
    } else {
      $encored_on = $task->encored_on;
    }

    $params = [
      'encored_on' => date('Y-m-d', strtotime($encored_on)),
      'premiered_on' => date('Y-m-d', strtotime($task->premiered_on)),
      'object_type' => $task->object_type,
      'slug' => $task->slug
    ];

    if ($task->object_type !== 'full_length') {
      $params['description_short'] = $task->description_short;
      $params['description_long'] = $task->description_long;
      $params['title'] = $task->title;
    }

    // Generate an asset container to upload the asset to
    return $this->_client->create_child(
      $parentId,
      $parentType,
      'asset',
      $params
    );
  }

  /**
   * Performs a clear call to the API to attempt to remove any existing
   * media files from the asset. This does not result in immediate deletion
   * of the files and is unsafe to call immediately before setting files
   *
   * @param Task $task The task to remove files for
   * @param array $response The API representation of the asset for the task
   * @return bool True if both video and caption are cleared, false otherwise
   */
  private function _clearAssetMediaFiles(Task $task, $response) {

    // Get the current video and caption status
    $videoStatus = $this->_checkVideoStatus($response['data']);
    $captionStatus = $this->_checkCaptionStatus($response['data']);

    // If the video is not cleared, attempt to clear it
    if ($videoStatus !== IngestComponent::VIDEO_CLEAR) {

      // Attempt to perform a reset of the video
      $this->_client->update_object(
        $task->pbs_content_id,
        'asset',
        [
          'video' => null
        ]
      );
    }

    if ($captionStatus !== IngestComponent::CAPTION_CLEAR) {

      // Attempt to perform a reset of the caption
      $this->_client->update_object(
        $task->pbs_content_id,
        'asset',
        [
          'caption' => null
        ]
      );
    }

    return $videoStatus === IngestComponent::VIDEO_CLEAR &&
      $captionStatus === IngestComponent::CAPTION_CLEAR;
  }

  /**
   * Attempts to attach media files to a specific asset so that it begins
   * ingesting into PBS's system
   *
   * @param Task $task The task to add media files for
   * @return array|bool True on success and an error array on failure
   */
  private function _addAssetMediaFiles(Task $task) {

    // Check the encore date to make sure it is not empty. If it is then use
    // the premiered date instead
    if (strtotime($task->encored_on) < 0) {
      $encored_on = $task->premiered_on;
    } else {
      $encored_on = $task->encored_on;
    }

    $params = [
      'encored_on' => date('Y-m-d', strtotime($encored_on)),
      'premiered_on' => date('Y-m-d', strtotime($task->premiered_on)),
      'tags' => explode(',', $task->tags ?: '') ?: [],
      'auto_publish' => true,
      'availabilities' => [
        'public' => [
          'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
          'end' => null
        ],
        'all_members' => [
          'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
          'end' => null
        ],
        'station_members' => [
          'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
          'end' => null
        ]
      ],
      'images' => [
        ['profile' => 'asset-mezzanine-16x9', 'source' => $task->getImageUrl()]
      ],
      'video' => [
        'profile' => 'hd-16x9-mezzanine',
        'source' => $task->getVideoUrl()
      ],
      'caption' => $task->getCaptionUrl()
    ];

    if ($task->object_type !== 'full_length') {
      $params['description_short'] = $task->description_short;
      $params['description_long'] = $task->description_long;
      $params['title'] = $task->title;
    }

    return $this->_client->update_object(
      $task->pbs_content_id,
      'asset',
      $params
    );
  }

  /**
   * Given a Task and its associated Media Manager response, returns a computed
   * new status
   *
   * @param Task $task
   * @param array $response
   * @return string
   */
  public function getIngestTaskStatus(Task $task, array $response) {

    // Skip checking if a PBS Content Id has yet to be assigned
    if (!empty($task->pbs_content_id)) {

      // Get the video status
      $videoStatus = $this->_checkVideoStatus($response['data']);

      // Get the caption status
      $captionStatus = $this->_checkCaptionStatus($response['data']);

      // Check for a successful or failed ingest depending of the current
      // state of the task
      if ($task->status === Task::STAGING) {
        if ($videoStatus === IngestComponent::VIDEO_FAILED ||
            $captionStatus === IngestComponent::CAPTION_FAILED) {
          return Task::ASSET_FAILED;
        } elseif ($videoStatus === IngestComponent::VIDEO_CLEAR &&
                  $captionStatus === IngestComponent::CAPTION_CLEAR) {
          return Task::STAGED;
        } else {

          // Otherwise if the task is still in a staging state and has not
          // cleared or failed, send another clear request
          $this->_clearAssetMediaFiles($task, $response);
        }
      } elseif ($task->status === Task::IN_PROGRESS) {
        if ($videoStatus === IngestComponent::VIDEO_FAILED ||
            $videoStatus === IngestComponent::VIDEO_CLEAR ||
            $captionStatus === IngestComponent::CAPTION_FAILED ||
            $captionStatus === IngestComponent::CAPTION_CLEAR) {
          return Task::ASSET_FAILED;
        } elseif ($videoStatus === IngestComponent::VIDEO_DONE &&
                  $captionStatus === IngestComponent::CAPTION_DONE) {
          return Task::DONE;
        }
      }
    }

    // If the task doesn't have a PBS Content Id or no object in the API, then
    // the status can not have changed
    return $task->status;
  }

  /**
   * Inspects a Media Manager Asset Edit object and determines the status of
   * the video attached to the asset
   *
   * @param array $asset An array representation of an asset object from the
   *                     Media Manager API
   * @return string A status string indicated the status of video ingest
   */
  private function _checkVideoStatus($asset) {
    if (isset($asset['attributes'])) {
      if (isset($asset['attributes']['original_video'])) {

        // First check the error message, it may contain a failure despite the
        // ingest status not indicating that an error has occurred
        if (!empty($asset['attributes']['original_video']) &&
            isset($asset['attributes']['original_video']['ingestion_error']) &&
            $asset['attributes']['original_video']['ingestion_error'] !== '') {
          return IngestComponent::VIDEO_FAILED;
        }

        if (empty($asset['attributes']['original_video'])) {
          return IngestComponent::VIDEO_CLEAR;
        }

        if (isset($asset['attributes']['original_video']['ingestion_status'])) {
          switch ($asset['attributes']['original_video']['ingestion_status']) {
            case IngestComponent::VIDEO_DONE:
              return IngestComponent::VIDEO_DONE;
            case IngestComponent::VIDEO_FAILED:
              return IngestComponent::VIDEO_FAILED;
            default:
              return IngestComponent::VIDEO_IN_PROGRESS;
          }
        }
      }
    }

    return IngestComponent::VIDEO_FAILED;
  }

  /**
   * Inspects a Media Manager Asset Edit object and determines the status of
   * the video attached to the asset
   *
   * @param array $asset An array representation of an asset object from the
   *                     Media Manager API
   * @return string A status string indicated the status of video ingest
   */
  private function _checkCaptionStatus($asset) {
    if (isset($asset['attributes'])) {
      if (isset($asset['attributes']['original_caption'])) {

        // First check the error message, it may contain a failure despite the
        // ingest status not indicating that an error has occurred
        if (!empty($asset['attributes']['original_caption']) &&
          isset($asset['attributes']['original_caption']['ingestion_error']) &&
          $asset['attributes']['original_caption']['ingestion_error'] !== '') {
          return IngestComponent::CAPTION_FAILED;
        }

        if (empty($asset['attributes']['original_caption'])) {
          return IngestComponent::CAPTION_CLEAR;
        }

        if (isset($asset['attributes']['original_caption']['ingestion_status'])) {
          switch ($asset['attributes']['original_caption']['ingestion_status']) {
            case IngestComponent::CAPTION_DONE:
              return IngestComponent::CAPTION_DONE;
            case IngestComponent::CAPTION_FAILED:
              return IngestComponent::CAPTION_FAILED;
            default:
              return IngestComponent::CAPTION_IN_PROGRESS;
          }
        }
      }
    }

    return IngestComponent::CAPTION_FAILED;
  }

  /**
   * Given a task and a Media Manager response, updates the task with the status
   * of the asset
   *
   * @param Task $task
   * @param array $response
   */
  public function updateStatus(Task $task, array $response) {

    // Update the status of the ingest task
    $task->status = $this->getIngestTaskStatus($task, $response);
  }

  /**
   * Attaches a legacy tp media id from a Media Manager response to a Task
   *
   * @param Task $task
   * @param array $response
   */
  public function addLegacyTPMediaId(Task $task, array $response) {

    if (
      isset(
        $response,
        $response['data'],
        $response['data']['attributes'],
        $response['data']['attributes']['legacy_tp_media_id'])
    ) {
      $task->tp_media_id = $response['data']['attributes']['legacy_tp_media_id'];
    }
  }

  /**
   * Updates the state of an ingest task with response data from the API while a
   * task is preparing or in progress
   *
   * @param Task $task
   * @return string
   */
  public function updateIngestTask(Task $task) {

    // Skip checking if a PBS Content Id has yet to be assigned
    if (!empty($task->pbs_content_id)) {
      $response = $this->_client->get_updatable_object($task->pbs_content_id, 'asset');

      // Only perform parsing if there is data to parse
      if ($response &&
        isset($response['data']) &&
        isset($response['data']['id'])) {

        // Update the status of the task based on the response
        $this->updateStatus($task, $response);

        // If the task failed due to an asset error, attempt a retry
        if ($task->hasAssetFailed()) {

          // Attempt a retry
          $task->retry();
        } else {

          // Attach legacy media ids if they are available
          $this->addLegacyTPMediaId($task, $response);
        }
      }
    }

    // Mark with an indicator that its status has been checked and
    // save the task back to the DB
    $task->touch();

    return $task->status;
  }
}