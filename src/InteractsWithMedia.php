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

	protected $media_builder;

	protected $collectionName;

	protected $operation; // add, update, delete

	protected $temp_media_data;

	protected $default_path;

	protected $uploaded_path;

	protected $results;
	
	public function media(): Model
	{
		$model = new Media();
		return $model;
	}

	/**
	 * Find media by who owns it, and which collection you want to get
	 * @param string $id
	 * @param string $collectionName
	 * @param bool $return_result
	 * @return $this
	 */
	public function mediaOf(string $id, string $collectionName = 'default', bool $return_result = false)
	{
		$this->model_id = $id;
		$this->collectionName = $collectionName;

		$this->media_builder = $this->media()->where('model_id', $this->model_id)->where('collection_name', $this->collectionName);

		if ($return_result === true) {
			return $this->media_builder->findAll();
		}

		return $this;
	}

	/**
	 * Get input file from request and validate it
	 * @param $file
	 * @throws ValidationException
	 */

	public function addMediaFromRequest($field): self
	{
		$this->setOperation('add');
		$this->validateFile(request()->getFile($field));
		$this->media_file = request()->getFile($field);
		return $this;
	}


	/**
	 * clear temp media, need unique name of who owns it, you can get it from asTemp() method before, you may specify it as api request or not by passing true or false to see the response
	 * @param string|null $id
	 */
	public function clearTempMedia(string $unique_name = null, bool $is_api_request = true)
	{
		$media = $this->media()->where('unique_name', $unique_name)->first();
		if ($media)
			$pathinfo = pathinfo(ROOTPATH .'public/'. $media->file_path .'/'. $media->file_name);
			if (is_dir($pathinfo['dirname'])) {
				if(file_exists($pathinfo['dirname'] . '/' . $media->file_name)) {
					shell_exec('rm ' . escapeshellarg($pathinfo['dirname'] . '/' . $media->file_name));
				}
			}
			$this->media()->delete($media->id);

		if ($is_api_request === true) {
			$resp['status'] = 'success';
			$resp['message'] = 'File '.$unique_name.' deleted successfully';
			header('Content-Type: application/json');
			return response()->setJSON($resp)->setStatusCode(200, 'OK');
		}
	}


	/**
	 * add media to collection (folder and label)
	 * @param string $collectionName
	 * @return $this
	 * @throws ValidationException
	 */
	public function toMediaCollection(string $collectionName = 'default'): self
	{
		$randomNameWithoutExtension = explode('.', $this->media_file->getRandomName())[0];
		$this->temp_media_data = [
			'model_type' => get_class($this),
			'model_id' => null,
			'unique_name' => $randomNameWithoutExtension,
			'collection_name' => $collectionName,
			'file_name' => null,
			'file_type' => $this->media_file->getClientMimeType(),
			'file_size' => $this->media_file->getSize(),
			'file_path' => null,
			'file_ext' => $this->media_file->getExtension(),
			'orig_name' => $this->media_file->getClientName(),
		];

		$this->setDefaultPath();
		return $this;
	}

	/**
	 * add media to collection but as temp file without labelling who owns it, you may specify it as api request or not by passing true or false
	 * @param bool $is_api_request
	 * @return $this
	 */
	public function asTemp(bool $is_api_request = false)
	{
		$temp_path = $this->generateFolderTemp();
		
		$this->temp_media_data['file_path'] = $temp_path;
		$this->temp_media_data['file_name'] = $this->temp_media_data['unique_name'].'.'.$this->temp_media_data['file_ext'];

		$this->storeMedia();

		if ($is_api_request === true) {
			$resp['data'] = $this->temp_media_data;
			$resp['status'] = 'success';
			$resp['message'] = 'File uploaded successfully';
			header('Content-Type: application/json');
			return response()->setJSON($resp)->setStatusCode(201, 'Created');
		}

		return $this;
	}

	/**
   * Store media based on last inserted data (those inserted data is the owner of the stored media), this method doing it's own after execute codeigniter's insert() operation 
   * @return $this
   */
	public function withInsertedData()
	{
		$this->model_id = $this->insertID();
		$this->temp_media_data['model_id'] = $this->model_id;
		$this->generateFileName();
		$this->setDefaultPath();
		$this->generateFolder($this->default_path, $this->temp_media_data['collection_name']);
		$this->storeMedia();
		return $this;
	}

	
	/**
   * Replace media with new one, need id who belongs to. you may specify addMediaFromRequest('photo') then toMediaCollection('announcement_photo')
   * @return $this
   */
	public function withUpdatedData(string $id)
	{
		$this->model_id = $id;
		$this->temp_media_data['model_id'] = $this->model_id;
		$this->generateFileName();
		$this->setDefaultPath();
		$this->generateFolder($this->default_path, $this->temp_media_data['collection_name']);
		$this->updateMedia($this->model_id, $this->temp_media_data['model_type']);
		return $this;
	}

	protected function storeMedia()
	{
		if($this->temp_media_data['file_name'] == null) {
			$this->generateFileName();
		}

		$this->media()->insert($this->temp_media_data);
		$this->removeOldFile();
		
		$this->media_file->move(
			ROOTPATH . 'public/'. $this->temp_media_data['file_path'], 
			$this->temp_media_data['file_name']
		);

		// destroy the file object
		$this->media_file = null;
	}

	protected function updateMedia(string $id, string $model_type)
	{
		$this->media()->where('model_id', $id)->where('model_type', $model_type)->delete();
		$this->media()->insert($this->temp_media_data);
		$this->removeOldFile();
		
		$this->media_file->move(
			ROOTPATH . 'public/'. $this->temp_media_data['file_path'], 
			$this->temp_media_data['file_name']
		);

		// destroy the file object
		$this->media_file = null;
	}

	protected function setDefaultPath(string $root_path = 'uploads/')
	{
		$this->default_path = $root_path;
	}

	protected function generateFolder(string $path, string $collectionName)
	{
		if($this->model_id != null) {
			
			$user_path = $path . $collectionName . '/' . $this->model_id;
			if (!is_dir(ROOTPATH . 'public/'. $user_path)) {
				mkdir(ROOTPATH . 'public/'. $user_path, 0777, true);
			}

			$this->temp_media_data['file_path'] = $user_path;
		}
	}

	protected function generateFolderTemp()
	{
		$temp_path = $this->default_path . $this->temp_media_data['collection_name'] . '/temp';
		if (!is_dir(ROOTPATH . 'public/'. $temp_path)) {
			mkdir(ROOTPATH . 'public/'. $temp_path, 0777, true);
		}

		return $temp_path;
	}

	protected function moveFile(string $path_before, string $path_after)
	{
		shell_exec('mv ' . $path_before . ' ' . $path_after);
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
		$this->temp_media_data['file_name'] = 'default.'.$this->temp_media_data['file_ext'];
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
		return $this->media_builder->first();
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
	public function clearMediaCollection()
	{
		$p = $this->media_builder->findAll();
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

	protected function setOperation(string $operation)
	{
		$this->operation = $operation;
	}
}
