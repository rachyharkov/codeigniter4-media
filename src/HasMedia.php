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
  * clear from media collection
  */
  public function clearMediaCollection(string $collectionName = 'default', string $id = null);
  
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
   * return data as JSON (Used for API)
   * @return string
   */
  public function responseJson();

}