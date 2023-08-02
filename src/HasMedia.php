<?php

namespace Rachyharkov\CodeigniterMedia;

use CodeIgniter\Model;

interface HasMedia {
  public function media(): Model;
  public function addMediaFromRequest($field): self;
  public function toMediaCollection(string $collectionName = 'default'): self;
  public function findWithMedia(string $id);
  public function getCollection(string $collectionName = 'default');
}