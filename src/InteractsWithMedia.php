<?php

namespace Rachyharkov\CodeigniterMedia;

use App\Models\Media;
use CodeIgniter\Model;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Config\Validation;

trait InteractsWithMedia {

  protected $media_file;
  protected $uploaded_path;

  public function media() : Model
  {
    $model = new Media();
    return $model->where('model', get_class($this))
      ->where('model_id', $this->id);
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

    // dd($this->media_file->getRandomName());
    $this->uploaded_path = $path . $randomName;
    $this->media()->insert([
        'model_type' => get_class($this),
        'model_id' => $this->insertID(),
        'unique_name' => $this->media_file->getRandomName(),
        'collection_name' => $this->collectionName,
        'file_name' => $this->media_file->getName(),
        'file_type' => $this->media_file->getClientMimeType(),
        'file_size' => $this->media_file->getSize(),
        'file_ext' => $this->media_file->getExtension(),
        'file_path' => $this->uploaded_path,
        'orig_name' => $this->media_file->getClientName(),
    ]);
    
    $this->media_file->move($path, $this->media_file->getRandomName());
    return $this;
  }

  protected function deleteMedia()
  {
    $this->media()->delete();
  }
}