<?php

namespace App\Libraries\backend;

use App\Models\Media;
use CodeIgniter\Model;
use CodeIgniter\Validation\Exceptions\ValidationException;

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

	protected $no_image = false;

	
	public function media(): Model
	{
		$model = new Media();
		return $model;
	}

	/**
	 * This method is used to find model with media, always include this method before using other methods
	 * @param string $id
	 * @return $this
	 */
	public function findWithMedia(string $id)
	{
		$this->model_id = $id;
		$this->media_builder = $this->media()->where('model_id', $this->model_id);
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
		return $this;
	}


	/**
	 * add media to collection (folder and label)
	 * @param string $collectionName
	 * @return $this
	 * @throws ValidationException
	 */
	public function toMediaCollection(string $collectionName = 'default'): self
	{

		$this->temp_media_data = [
			'model_type' => get_class($this),
			'model_id' => null,
			'unique_name' => $this->media_file->getRandomName(),
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

	public function of($id)
	{
		$this->model_id = $id;

		if ($this->operation == 'add')
		{
			$this->temp_media_data['model_id'] = $this->model_id;

			$this->generateFolder($this->default_path, $this->temp_media_data['collection_name']);
			
			$this->storeMedia();

			return $this;
		}

		// if ($this->operation == 'update')
		// {
		// 	$this->generateFolder($this->default_path, $this->collectionName);

		// 	$this->updateMedia();

		// 	return;
		// }

		// if ($this->operation == 'delete')
		// {
		// 	$this->deleteMedia();

		// 	return;
		// }

		return $this;
	}

	// public function returnData(bool $json = false, bool $api_response = false)
	// {
	// 	if ($json === true && $api_response === true) {
	// 		header('Content-Type: application/json');
	// 		return response()->setJSON($this->temp_media_data);
	// 	}

	// 	if ($json === true) {
	// 		return json_encode($this->temp_media_data);
	// 	}
	// 	return $this->temp_media_data;
	// }

	public function asTemp(bool $is_api_request = false)
	{
		$temp_path = $this->generateFolderTemp();
		
		$this->temp_media_data['file_path'] = $temp_path;

		$this->moveFile(
			ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'],
			ROOTPATH . 'public/' . $this->default_path . $this->temp_media_data['collection_name'] . '/' . $this->temp_media_data['unique_name']
		);

		if ($is_api_request === true) {
			header('Content-Type: application/json');
			return response()->setJSON($this->temp_media_data);
		}

		return $this;
	}

	protected function moveFile(string $path_before, string $path_after)
	{
		dd($path_before, $path_after);
		shell_exec('mv ' . $path_before . ' ' . $path_after);
	}

	/**
	 * get media collection
	 * @param string $collectionName use default if not set
	 * @param bool $returnResult return result or not
	 * @return $this|Model
	 * @throws ValidationException
	 */
	public function getCollection(string $collectionName = 'default', bool $returnResult = false)
	{
		if ($returnResult === true && $collectionName === '*') {
			return $this->media_builder = $this->media_builder->findAll();
		}

		if ($returnResult === true) {
			return $this->media_builder = $this->media_builder->where('collection_name', $collectionName)->findAll();
		}

		if($this->media_builder->where('collection_name', $collectionName)->countAllResults() > 0) {
			return $this;
		}

		$this->no_image = true;
		return $this;
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
		$this->media_file = $file;
	}

	protected function storeMedia()
	{
		
		$this->generateFileName();

		$this->media()->insert($this->temp_media_data);

		$this->removeOldFile();

		$this->media_file->move(
			ROOTPATH . 'public/'. $this->temp_media_data['file_path'], 
			$this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']
		);

		// destroy the file object
		$this->media_file = null;
	}

	protected function generateFileName()
	{
		$this->temp_media_data['file_name'] = 'default';
	}

	protected function checkFileExists()
	{
		if (file_exists(ROOTPATH . 'public/'. $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext'])) {
			return true;
		}
		return false;
	}

	protected function removeOldFile()
	{
		if ($this->checkFileExists()) {
			unlink(ROOTPATH . 'public/'. $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']);
		}
	}

	/**
	 * get media data, best used with where clause
	 * @param string $collectionName
	 * @return mixed
	 */
	public function getMedia(string $collectionName = 'default')
	{
		$data = $this->findAll();

		foreach ($data as $key => $value) {

			if ($this->returnType == 'array') {
				$data[$key]['media'] = $this->media()->where('model_id', $value->id)->where('collection_name', $collectionName)->findAll();
			} else {
				$data[$key]->media = $this->media()->where('model_id', $value->id)->where('collection_name', $collectionName)->findAll();
			}
		}
		return $data;
	}

	/**
	 * get first media data
	 * @return mixed
	 */
	public function getFirstMedia()
	{
		if ($this->no_image === true) {
			return $this->noImage();
		}
		return $this->media_builder->first();
	}

	/**
	 * get first media url
	 * @return string|null
	 */
	public function getFirstMediaUrl()
	{
		if ($this->no_image === true) {
			return $this->noImage();
		}

		$media = $this->getFirstMedia();
		if ($media) {
			return base_url($media->file_path .'/'. $media->file_name.'.'.$media->file_ext);
		}
		return null;
	}

	public function clearMediaCollection(string $collectionName = 'default')
	{
		$p = $this->media_builder->where('collection_name', $collectionName)->findAll();

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
