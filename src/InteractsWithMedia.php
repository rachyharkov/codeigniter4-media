<?php

namespace Rachyharkov\CodeigniterMedia;

use App\Models\Media;
use CodeIgniter\Model;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Validation;

trait InteractsWithMedia {

  protected $media_file;
  protected $uploaded_path;
  protected $collectionName = 'default';

  protected $media;

  protected $result_with_media_data;

  protected $model_id; 

  protected $media_builder;

  public function media() : Model
  {
    $model = new Media();
    return $model;
  }

  public function addMediaFromRequest($field) : self
  {
    $this->media_file = request()->getFile($field);
    // dd($this->media_file);
    $this->validateFile($this->media_file);
    return $this;
  }

  public function toMediaCollection(string $collectionName = 'default'): self
  {
    $this->collectionName = $collectionName;
    $this->storeMedia();
    return $this;
  }

  public function findWithMedia(string $id)
  {
    $this->model_id = $id;
    $this->media_builder = $this->media()->where('model_id', $this->model_id);

    return $this;
  }

  public function getCollection(string $collectionName = 'default')
  {
    $this->collectionName = $collectionName;
    $this->media_builder->where('collection_name', $this->collectionName);
    
    return $this->_exec();
  }

  protected function validateFile(object $file)
  {
    if (!$file->isValid()) {
      return;
    }
  }

  protected function storeMedia()
  {
    $path = 'uploads/' . $this->collectionName . '/';

    if (!is_dir(ROOTPATH . $path)) {
      mkdir(ROOTPATH . $path, 0777, true);
    }

    $randomName = $this->media_file->getRandomName();

    $this->uploaded_path = $path . $randomName;
    $temp_data = [
      'model_type' => get_class($this),
      'model_id' => $this->insertID(),
      'unique_name' => $this->media_file->getRandomName(),
      'collection_name' => $this->collectionName,
      'file_type' => $this->media_file->getClientMimeType(),
      'file_size' => $this->media_file->getSize(),
      'file_ext' => $this->media_file->getExtension(),
      'orig_name' => $this->media_file->getClientName(),
    ];
    $this->media()->insert($temp_data);

    $this->model_id = $this->insertID();

    $user_path = $path . $this->model_id . '/';
    if (!is_dir(ROOTPATH . $user_path)) {
      mkdir(ROOTPATH . $user_path, 0777, true);
    }

    $filename = 'default.'. $this->media_file->getExtension();

    $this->updateFilePath($user_path . $filename);
    $this->updateFileName($filename);

    $this->media_file->move($user_path, $filename);

    // destroy the file object
    $this->media_file = null;

    return $this;
  }

  protected function updateFilePath(string $path)
  {
    $this->media()->update($this->model_id, ['file_path' => $path]);
  }

  protected function updateFileName(string $filename)
  {
    $this->media()->update($this->model_id, ['file_name' => $filename]);
  }

  protected function deleteMedia()
  {
    $this->media()->delete();
  }

  protected function _exec()
  {
    $temp = $this->find($this->model_id);
    if($this->returnType == 'array') {
      $temp['media'] = $this->media_builder->findAll();
    } else {
      $temp->media = $this->media_builder->findAll();
    }
    // dd($temp);
    return $temp;
  }
}