<?php

namespace App\Libraries\backend;

use App\Models\Media;
use CodeIgniter\Model;
use CodeIgniter\Validation\Exceptions\ValidationException;
use PhpParser\Node\Stmt\Return_;

trait InteractsWithMedia
{

	protected $media_file;

	protected $model_id = null;

	protected $media_id = null;

	protected $media_builder;

	protected $collectionName;

	protected $temp_media_data;

	protected $default_path;

	protected $operation;

	protected $uploaded_path;

	protected $uploaded_file_name = null;

	protected $results;
	
	public function media(): Model
	{
		$model = new Media();
		return $model;
	}

	/**
	 * Find media by who owns it, and which collection you want to get
	 * @param string $collectionName
	 * @param string $id (if not set, it will find all media in collection)
	 * @param bool $return_result (optional)
	 * @return $this
	 */
	public function mediaOf(string $collectionName = 'default', string $id = null)
	{
		$this->model_id = $id;
		$this->collectionName = $collectionName;

		$this->media_builder = $this->media()->where('collection_name', $this->collectionName);

		if ($this->model_id) {
			$this->media_builder->where('model_id', $this->model_id);
		}

		return $this;
	}

	public function getAllMedia()
	{
		return $this->media_builder->findAll();
	}

	/**
	 * Get input file from request and validate it
	 * @param $file
	 * @throws ValidationException
	 */

	public function addMediaFromRequest($field): self
	{
		$this->validateFile(request()->getFile($field));
		$this->media_file = request()->getFile($field);
		return $this;
	}

	/**
	 * Get all specified input files from request and validate it
	 * @param $array
	 * @return $this
	 */

		public function addMediaWithRequestCollectionMapping(array $array): self
		{
			foreach ($array as $key => $value) {
				$this->addMediaFromRequest($key)->toMediaCollection($value);
			}
			return $this;
		}


	/**
	 * clear temp media, need unique name of who owns it, you can get it from putItInCollection() method before, you may specify it as api request or not by passing true or false to see the response
	 * @param string|null $id
	 */
	public function clearTempMedia(string $unique_name = null, bool $is_api_request = true)
	{
		$media = $this->media()->where('unique_name', $unique_name)->first();
		if ($media)
			$pathinfo = pathinfo(ROOTPATH .'public/'. $media->file_path .'/'. $media->file_name . '.' . $media->file_ext);
			if (is_dir($pathinfo['dirname'])) {
				if(file_exists($pathinfo['dirname'] . '/' . $media->file_name . '.' . $media->file_ext)) {
					shell_exec('rm ' . escapeshellarg($pathinfo['dirname'] . '/' . $media->file_name . '.' . $media->file_ext));
				}
			}
			$this->media()->delete($media->id);

		if ($is_api_request === true) {
			$resp['status'] = 'success';
			$resp['message'] = 'File deleted successfully';
			header('Content-Type: application/json');
			return response()->setJSON($resp)->setStatusCode(200, 'OK');
		}
	}

	public function usingFileName(string $name): self
	{
		$this->uploaded_file_name = $name;
		return $this;
	}


	/**
	 * add media to collection (folder and label)
	 * @param string $collectionName
	 * @return $this
	 * @throws ValidationException
	 */
	public function toMediaCollection(string $collectionName = 'default')
	{
		$this->operation = 'add';

		$randomNameWithoutExtension = explode('.', $this->media_file->getRandomName())[0];
		$this->temp_media_data = [
			'model_type' => get_class($this),
			'model_id' => $this->model_id,
			'unique_name' => $randomNameWithoutExtension,
			'collection_name' => $collectionName,
			'file_name' => $this->uploaded_file_name,
			'file_type' => $this->media_file->getClientMimeType(),
			'file_size' => $this->media_file->getSize(),
			'file_path' => null,
			'file_ext' => $this->media_file->getExtension(),
			'orig_name' => basename($this->media_file->getClientName(), '.'.$this->media_file->getExtension()),
			'custom_properties' => null
		];

		
		$this->setDefaultPath();
		
		// used to store after insert data operation
		try {
			//https://forum.codeigniter.com/showthread.php?tid=78276&pid=382975#pid382975
			$this->model_id = $this->getInsertID();
			$this->temp_media_data['model_id'] = $this->model_id;
			$this->storeMedia();

			return $this;
		} catch (\Throwable $th) {  
			if($this->model_id) { // if model id is not null, then it means that the model is already inserted, so we can continue to store the media
				$this->temp_media_data['model_id'] = $this->model_id;

				$this->storeMedia();
	
				return $this;
			}
			// if model id is null, then it means the media not data-related, so we store it on collection
			$this->putItInCollection();
			
			return $this;
		}

	}

	/**
	 * add media to collection but as temp file without labelling who owns it, you may specify it as api request or not by passing true or false
	 * @param bool $is_api_request
	 * @return $this
	 */
	protected function putItInCollection()
	{
		$temp_path = $this->generateCollectionFolder();
		
		$this->temp_media_data['file_path'] = $temp_path;
		$this->temp_media_data['file_name'] = $this->temp_media_data['unique_name'];

		$this->storeMedia();

		return $this;
	}

	public function withCustomProperties(array $properties): self
	{
		$this->temp_media_data['custom_properties'] = json_encode($properties);
		return $this;
	}

	protected function storeMedia()
	{
		if($this->temp_media_data['file_name'] == null) {
			$this->generateFileName();
		}
		$this->media()->insert($this->temp_media_data);

		$last_insert_id = $this->media()->insertID();

		$this->generateFolder($this->default_path, $this->temp_media_data['collection_name'], $last_insert_id);

		$this->media()->update($last_insert_id, ['file_path' => $this->temp_media_data['file_path']]);
		
		$this->removeOldFile();
		
		$this->media_file->move(
			ROOTPATH . 'public/'. $this->temp_media_data['file_path'], 
			$this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']
		);

		// destroy the file object
		$this->media_file = null;
	}

	protected function setDefaultPath(string $root_path = 'uploads/')
	{
		$this->default_path = $root_path;
	}

	protected function generateFolder(string $path, string $collectionName, string $id)
	{
		if($this->model_id != null) {
			
			$user_path = $path . $collectionName . '/' . $id;
			if (!is_dir(ROOTPATH . 'public/'. $user_path)) {
				mkdir(ROOTPATH . 'public/'. $user_path, 0755, true);
			}

			$this->temp_media_data['file_path'] = $user_path;
		}
	}

	protected function generateCollectionFolder()
	{
		$temp_path = $this->default_path . $this->temp_media_data['collection_name'];
		if (!is_dir(ROOTPATH . 'public/'. $temp_path)) {
			mkdir(ROOTPATH . 'public/'. $temp_path, 0755, true);
		}

		return $temp_path;
	}

	protected function moveFile(string $path_before, string $path_after, string $file_name)
	{
		if (!is_dir(ROOTPATH . 'public/'. $path_after)) {
			mkdir(ROOTPATH . 'public/'. $path_after, 0755, true);
		}

		shell_exec('mv ' . $path_before . '/' . $file_name . ' ' . $path_after . '/' . $file_name);
		
		$this->temp_media_data['file_path'] = $path_after;
	}

	protected function noImage()
	{
		$this->setDefaultPath();
		return base_url($this->default_path . 'no-image.jpg');
	}


	protected function validateFile(object $file)
	{
		if (!$file->isValid()) {
			return false;
		}
	}


	protected function generateFileName()
	{
		$this->temp_media_data['file_name'] = 'default';
	}

	protected function checkFileExists()
	{
		if (file_exists(ROOTPATH . 'public/'. $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'])) {
			return true;
		}
		return false;
	}

	protected function removeOldFile()
	{
		if ($this->checkFileExists()) {
			unlink(ROOTPATH . 'public/'. $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name']);
		}
	}

	/**
	 * get first media metadata
	 * @return mixed
	 */
	public function getFirstMedia()
	{
		$data = $this->media_builder->first();

		if (!$data) {
			return null;
		}

		$data->file_url = base_url($data->file_path .'/'. $data->file_name.'.'.$data->file_ext);
		return $data;
	}

	/**
	 * just return url of first media
	 * @return string|null
	 */
	public function getFirstMediaUrl()
	{
		$media = $this->getFirstMedia();
		if ($media) {
			return base_url($media->file_path .'/'. $media->file_name.'.'.$media->file_ext);
		}
		return null;
	}

	/**
   * clear from media collection
   */
	public function clearMediaCollection(string $collectionName = 'default', string $id = null)
	{
		$this->operation = 'delete';	

		$this->model_id = $id;
		$p = $this->media()->where('collection_name', $collectionName);

		if ($this->model_id) {
			$p->where('model_id', $this->model_id);
		}

		$p = $p->findAll();

		if (count($p) > 0) {
			foreach ($p as $k => $v) {
				$pathinfo = pathinfo(ROOTPATH .'public/'. $v->file_path . $v->file_name);

				if (is_dir($pathinfo['dirname'])) {
					shell_exec('rm -rf ' . escapeshellarg($pathinfo['dirname'] . '/' . $v->model_id));
				}
				$this->media()->delete($v->id);
			}
		}
	}

	/**
	 * return data as JSON (Used for API) after upload
	 * @return string
	 */
	public function responseJson()
	{

		$message = null;

		if($this->operation == 'add') {
			$message = 'File uploaded successfully';
		}
		
		if($this->operation == 'delete') {
			$message = 'File deleted successfully';
		}
		$this->temp_media_data['file_url'] = base_url($this->temp_media_data['file_path'] .'/'. $this->temp_media_data['file_name'].'.'.$this->temp_media_data['file_ext']);

		$resp['data'] = $this->temp_media_data;
		$resp['status'] = 'success';
		$resp['message'] = $message;
		header('Content-Type: application/json');
		return response()->setJSON($resp)->setStatusCode(201, 'Created');
	}

	/**
	 * return blob data (Used for API) after upload
	 */
	public function getMediaLocation()
	{
		header('Content-Type: application/json');
		return response()->setJSON(['location' => base_url($this->temp_media_data['file_path'] .'/'. $this->temp_media_data['file_name'].'.'.$this->temp_media_data['file_ext'])])->setStatusCode(201, 'Created');
	}
}
