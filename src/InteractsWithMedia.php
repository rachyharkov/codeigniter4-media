<?php

namespace App\Libraries\backend;

use App\Models\Media;
use CodeIgniter\Model;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Services;
use Exception;
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

	protected $with_thumbnail = false;

	// protected $thumbnail_size = [100, 100];

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
	 * @param int $id who owns it
	 * @return $this
	 */
	public function mediaOf(string $collectionName = 'default', int $id = null)
	{
		if(!$id){
			$this->getModelID();
		} else {
			$this->model_id = $id;
		}

		$this->collectionName = $collectionName;

		$this->media_builder = $this->media()->where('collection_name', $this->collectionName)->orderBy('id', 'DESC');

		if ($this->model_id) {
			$this->media_builder->where('model_id', $this->model_id);
			// dd($this->media_builder->get()->getResult());
		}

		return $this;
	}

	public function getAllMedia()
	{
		$result 			= $this->media_builder->findAll();

		foreach ($result as $key => $value) {
			$result[$key]->file_url = base_url($value->file_path . '/' . $value->file_name . '.' . $value->file_ext);

			if (preg_match('/image/', $value->file_type)) {
				$result[$key]->file_url_thumb = base_url($value->file_path . '/' . $value->file_name . '-thumb.' . $value->file_ext);
			}
		}

		return $result;
	}

	/**
	 * Get input file from request and validate it
	 * @param $file
	 * @throws ValidationException
	 */

	public function addMediaFromRequest($field): self
	{
		try {
			$this->validateFile(request()->getFile($field));
		} catch (\Throwable $th) {
			throw new Exception('File is not valid');
		}
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

	public function addMedia($file): self
	{
		$this->validateFile($file);
		$this->media_file = $file;
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
			$pathinfo = pathinfo(ROOTPATH . 'public/' . $media->file_path . '/' . $media->file_name . '.' . $media->file_ext);
		if (is_dir($pathinfo['dirname'])) {
			if (file_exists($pathinfo['dirname'] . '/' . $media->file_name . '.' . $media->file_ext)) {
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
			'orig_name' => basename($this->media_file->getClientName(), '.' . $this->media_file->getExtension()),
			'custom_properties' => null
		];


		$this->setDefaultPath();


		$id = $this->getModelID();

		if ($id === false) {
			$this->putItInCollection();
		} else {
			$this->model_id = $id;
			$this->temp_media_data['model_id'] = $this->model_id;
			$this->storeMedia();
		}

		return $this;
	}

	public function withThumbnail(): self
	{
		if (!$this->isImage()) {
			return new Exception('This file is not an image');
		}

		$this->with_thumbnail = true;
		return $this;
	}

	protected function isImage(): bool
	{
		return strpos($this->media_file->getClientMimeType(), 'image') !== false;
	}

	protected function getModelID()
	{
		$id = $this->getInsertID();

		if ($id != 0) {
			$this->model_id = $id;
			return $id;
		}

		if ($this->model_id) {
			return $this->model_id;
		}

		$last_query = $this->db->getLastQuery()->getQuery();
		// if update query
		if (strpos($last_query, 'UPDATE') !== false) {
			$matches = [];
			$table_name = '"' . str_replace('.', '"."', $this->table) . '"."'.$this->primaryKey.'"'; // "schema"."table"."column"
			preg_match('/' . $table_name . ' IN \((.*?)\)/', $last_query, $matches);

			$this->model_id = str_replace("'", '', $matches[1]);
			return $this->model_id;
		}

		if (strpos($last_query, 'DELETE') !== false) {
			$matches = [];

			preg_match('/"' . $this->primaryKey . '" = \'(.*?)\'/', $last_query, $matches);

			// dd($last_query, $matches);

			$this->model_id = str_replace('"', '', $matches[1]);
			return $this->model_id;
		}

		try {
			$id = $this->getIdValue($this->db->query($this->db->getLastQuery())->getFirstRow());

			if ($id) {
				$this->model_id = $id;
				return $id;
			}
		} catch (Exception $e) {
			return false;
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
		if ($this->temp_media_data['file_name'] == null) {
			$this->generateFileName();
		}

		$this->media()->insert($this->temp_media_data);

		$last_insert_id = $this->media()->insertID();

		$this->generateFolder($this->default_path, $this->temp_media_data['collection_name'], $last_insert_id);

		$this->media()->update($last_insert_id, ['file_path' => $this->temp_media_data['file_path']]);

		$this->removeOldFile();

		$this->media_file->move(
			ROOTPATH . 'public/' . $this->temp_media_data['file_path'],
			$this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']
		);

		if ($this->with_thumbnail) {
			$this->generateThumbnail();
		}

		// destroy the file object
		$this->media_file = null;
	}

	protected function generateThumbnail()
	{
		$image_size = getimagesize(ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']);

		Services::image()
			->withFile(ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext'])
			// do not change the size, just make it smaller
			->fit($image_size[0] / 2, $image_size[1] / 2, 'center')
			->save(ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '-thumb.' . $this->temp_media_data['file_ext']);
	}

	protected function setDefaultPath(string $root_path = 'uploads/')
	{
		$this->default_path = $root_path;
	}

	protected function generateFolder(string $path, string $collectionName, string $id)
	{
		if ($this->model_id != null) {

			$user_path = $path . $collectionName . '/' . $id;
			if (!is_dir(ROOTPATH . 'public/' . $user_path)) {
				mkdir(ROOTPATH . 'public/' . $user_path, 0755, true);
			}

			$this->temp_media_data['file_path'] = $user_path;
		}
	}

	protected function generateCollectionFolder()
	{
		$temp_path = $this->default_path . $this->temp_media_data['collection_name'];
		if (!is_dir(ROOTPATH . 'public/' . $temp_path)) {
			mkdir(ROOTPATH . 'public/' . $temp_path, 0755, true);
		}

		return $temp_path;
	}

	protected function moveFile(string $path_before, string $path_after, string $file_name)
	{
		if (!is_dir(ROOTPATH . 'public/' . $path_after)) {
			mkdir(ROOTPATH . 'public/' . $path_after, 0755, true);
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
		if (file_exists(ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'])) {
			return true;
		}
		return false;
	}

	protected function removeOldFile()
	{
		if ($this->checkFileExists()) {
			unlink(ROOTPATH . 'public/' . $this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name']);
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

		$data->file_url = base_url($data->file_path . '/' . $data->file_name . '.' . $data->file_ext);

		if ($data->file_type == 'image/jpeg' || $data->file_type == 'image/png') {
			$data->thumb_url = base_url($data->file_path . '/' . $data->file_name . '-thumb.' . $data->file_ext);
		}

		return $data;
	}

	/**
	 * just return url of first media
	 * @param string|null $type (optional - which type of media you want to get, ussuallly used for image)
	 * @return string|null
	 */
	public function getFirstMediaUrl(string $type = null)
	{
		$media = $this->getFirstMedia();
		if ($media) {

			if ($type == 'thumb') {
				return base_url($media->file_path . '/' . $media->file_name . '-thumb.' . $media->file_ext);
			}

			return base_url($media->file_path . '/' . $media->file_name . '.' . $media->file_ext);
		}
		return null;
	}

	/**
	 * clear from media collection
	 * @param string $collectionName
	 */
	public function clearMediaCollection(string $collectionName = null)
	{
		$this->operation = 'delete';

		$id = $this->getModelID();

		$p = $this->media()->where('model_type', get_class($this));

		if ($collectionName) {
			$p->where('collection_name', $collectionName);
		}

		if ($id) {
			$p->where('model_id', $id);
		}

		$p = $p->findAll();

		if (count($p) > 0) {
			foreach ($p as $k => $v) {
				$pathinfo = pathinfo(ROOTPATH . 'public/' . $v->file_path . $v->file_name);

				if (is_dir($pathinfo['dirname'])) {
					shell_exec('rm -rf ' . escapeshellarg($pathinfo['dirname'] . '/' . $v->id));
				}
				$this->media()->delete($v->id);
			}
		}

		return $this;
	}

	/**
	 * return data as JSON (Used for API) after upload
	 * @return string
	 */
	public function responseJson()
	{

		$message = null;

		if ($this->operation == 'add') {
			$message = 'File uploaded successfully';
		}

		if ($this->operation == 'delete') {
			$message = 'File deleted successfully';
		}
		$this->temp_media_data['file_url'] = base_url($this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext']);

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
		return response()
			->setJSON(['location' => base_url($this->temp_media_data['file_path'] . '/' . $this->temp_media_data['file_name'] . '.' . $this->temp_media_data['file_ext'])])
			->setStatusCode(201, 'Created');
	}
}
