<?php

namespace App\Interfaces;

use CodeIgniter\Model;

interface HasMedia {
  public function media(): Model;

  /**
	 * Get input file from request and validate it
	 * @param $file
	 * @throws ValidationException
	 */
  public function addMediaFromRequest($field): self;
  
  /**
  * add media to collection with custom name
  * @param string $collectionName
  * @return $this
  * @throws ValidationException
  */
  public function toMediaCollection(string $collectionName = 'default'): self;

  /**
  * Find media by who owns it, and which collection you want to get
  * @param string $id
  * @param string $collectionName
  * @param bool $return_result
  * @return $this
  */
  public function mediaOf(string $id, string $collectionName = 'default');

  /**
  * clear media collection, need id of who owns it
  * @param string|null $id
  */
  public function clearMediaCollection(string $id);
  
  /**
	* get first media metadata
	* @return mixed
	*/
  public function getFirstMedia();

  /**
  * just return url of first media
  * @return string|null
  */
  public function getFirstMediaUrl();

  /**
  * add media to collection but as temp file without labelling who owns it, you may specify it as api request or not by passing true or false
  * @param bool $is_api_request
  * @return $this
  */
  public function asTemp(bool $is_api_request = false);

  /**
  * clear temp media, need unique name of who owns it, you can get it from asTemp() method before, you may specify it as api request or not by passing true or false to see the response
  * @param string|null $id
  */
  public function clearTempMedia(string $id_media = null);

  /**
   * Replace media with new one, you may specify addMediaFromRequest('photo') then toMediaCollection('announcement_photo')
   * @return $this
   */
  public function withInsertedData();

  /**
   * Store media based on last inserted data (those inserted data is the owner of the stored media), this method doing it's own after execute codeigniter's insert() operation 
   * @return $this
   */
  public function withUpdatedData(string $id);
}