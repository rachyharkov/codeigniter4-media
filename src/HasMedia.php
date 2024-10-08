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
   * add media
   * @param $file
   * @throws ValidationException
   */
  public function addMedia($file): self;


  /**
   * Get all specified input files from request and validate it
   * @param $array
   * @return $this
   */
  public function addMediaWithRequestCollectionMapping(array $array): self;
  
  /**
  * add media to collection with custom name
  * @param string $collectionName
  * @return $this
  * @throws ValidationException
  */
  public function toMediaCollection(string $collectionName = 'default');

  /**
   * if the file you want to store is an image, you can use this to make thumbnail
   * @return $this
   * @throws ValidationException
   */
  public function withThumbnail();

  /**
  * Find media by who owns it, and which collection you want to get
  * @param string $collectionName
  * @param int $id
  * @return $this
  */
  public function mediaOf(string $collectionName = 'default', int $id = null);

  /**
  * clear from media collection
  * @param string $collectionName
  */
  public function clearMediaCollection(string $collectionName = null);
  
  /**
	* get first media metadata
	* @return mixed
	*/
  public function getFirstMedia();

  /**
  * just return url of first media
  * @param string|null $type (optional - which type of media you want to get, ussuallly used for image)
  * @return string|null
  */
  public function getFirstMediaUrl(string $type = null);

  /**
   * get all media metadata
   * @return object
   */
  public function getAllMedia();

  /**
   * return data as JSON (Used for API)
   * @return string
   */
  public function responseJson();

  /**
   * return location of media after uploaded
   * @return string
   */
  public function getMediaLocation();


  /**
   * use specified name for uploaded file
   * @return $this
   */
  public function usingFileName(string $name): self;

  /**
   * attach custom properties to media
   * @return $this
   */
  public function withCustomProperties(array $properties): self;
}