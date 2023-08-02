<?php

namespace Rachyharkov\CodeigniterMedia;

use CodeIgniter\Model;

interface HasMedia {
  public function media(): Model;
  public function addMediaFromRequest($field): self;
  public function toMediaCollection(string $collectionName = 'default'): self;
}