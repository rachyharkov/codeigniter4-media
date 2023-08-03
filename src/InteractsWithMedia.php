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

	public function of($id)
	{
		$this->model_id = $id;

		if ($this->operation == 'add')
		{
			$this->temp_media_data['model_id'] = $this->model_id;

			$this->generateFolder($this->default_path, $this->temp_media_data['collection_name']);
			
			$this->storeMedia();
		}
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

		return $this;
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
		return $this->media_builder->first();
	}

	/**
	 * get first media url
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

	public function clearMediaCollection(string $collectionName = 'default')
	{
		// dd($this->media_builder->where('collection_name', $collectionName)->findAll());
		$p = $this->media_builder->where('collection_name', $collectionName)->findAll();

		if (count($p) > 0) {
			foreach ($p as $k => $v) {
				$pathinfo = pathinfo(ROOTPATH .'public/'. $v->file_path . $v->file_name);

				if (file_exists($pathinfo['dirname'] . '/' . $pathinfo['basename'])) {
					unlink($pathinfo['dirname'] . '/' . $pathinfo['basename']);
				}

				if (is_dir($pathinfo['dirname'])) {
					// delete folder even if not empty
					shell_exec('rm -rf ' . escapeshellarg($pathinfo['dirname']));
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
