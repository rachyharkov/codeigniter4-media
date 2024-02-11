# Codeigniter4-Media
Codeigniter package for to handle media upload file task (at least help a bit for my current job). My main goal on this package is codeigniter 4 have a library that be able to handle task such as organize file upload with minimial line of code

# Installation

## Composer

`composer require rachyharkov/codeigniter4-media`

## Setup Model
just set your model like this

```php
use CodeIgniter\Model;
use Rachyharkov\CodeigniterMedia\HasMedia;
use Rachyharkov\CodeigniterMedia\InteractsWithMedia;

class User extends Model implements HasMedia
{
    use InteractsWithMedia;

    // rest of codes
}
```

done

## How to use?

### Store single File

using name attribute of html input to let codeigniter4-media get the file, and add to specified collection (if no name entered, it will be using "default")

```php
    $this->user_model->insert($data);
    $this->user_model->addMediaFromRequest('photo')->toMediaCollection('profile_photo');
```

### Store single file - store thumbnail

It's useful if you want to store the thumbnail of the file, just add withThumbnail method after addMediaFromRequest method

```php
    $this->user_model->insert($data);
    $this->user_model->addMediaFromRequest('photo')->withThumbnail()->toMediaCollection('profile_photo');
```

## Store single File - with custom name

only use usingFileName method after addMediaFromRequest method, this will be useful if you want to rename the file before store it to database

```php
    $this->user_model->insert($data);
    $this->user_model->addMediaFromRequest('photo')->usingFileName('data_'.random(20))->toMediaCollection('profile_photo');
```

## Store Multi File - Different Name

store file from multi different request name (for example, you have 2 input file with different input file name attribute value, and you want to store it to same collection)

```php
    $this->user_model->insert($data);
    $this->user_model->addMediaWithRequestCollectionMapping([
      'file_input_photo' => 'profile_photo_collection',
      'file_input_profile_cover' => 'profile_photo_collection'
    ])
```

## Store Multi File - Same Name

This time using addMedia, make sure it's an file object (binary payload) to make addMedia accept value, in this example using multiple file input with same name attribute value

```php
    $this->product_model->insert($data);
    $product_images = $this->request->getFiles();

    foreach ($product_images['photos'] as $key => $p) {
        $this->product_model->addMedia($p)->toMediaCollection('product_image');
    }
```

### Get Single File - Metadata

```php

$this->data['user'] = $this->user_model->where('id', $id)->first();
$this->data['user']->photo = $this->user_model->mediaOf($this->data['user']->id,'profile_photo')->getFirstMedia();

return view('user/edit', $data);

```

above will return null if no file meta information returned, handle it like this

```php
    <img src="<?= $user->media ? $user->media->file_path.'/'.$user->media->file_name : $user->media ?>" alt="User Photo Profile">
```

### Get Single File - Just URL

This is the example of how to assign new object to existing object (for example user object) with new property (photo) that contains the url of file

```php

$this->data['user']->photo = $this->user_model->mediaOf($this->data['user']->id,'profile_photo')->getFirstMediaUrl();

return view('user/edit', $data);

```

It's possible to pass parameter with 'thumb' value to get the thumbnail of the file

```php
    $media->getFirstMediaUrl('thumb');
```

### Get All file of collection

Just return true on third parameter, if not specified, then you are trying to get the first file of collection indeed

```php
    $data['user']->collection_of_photo_profile = $this->user_model->mediaOf($user_id, 'profile_photo', true);

    return view('user/edit', $data);
```

### Delete file collection

```php
    $this->user_model->delete($id); // just general delete method
    $this->user_model->clearMediaCollection('profile_photo', $id);
```

### API Mode

create method in your controller just like this, set asTemp to true if you want to return the file metadata (this is useful if you want to show the file right after upload process completed, make sure to return it)

🪄 **Backend - Store File**

```php
public function api_upload()
{
    $user_id = $this->request->getVar('user_id');
    return $this->user_model->addMediaFromRequest('file')->toMediaCollection('profile_photo')->responseJson();
}
```

you will get this response

```php
{
    status: "success",
    message: "File uploaded successfuly",
    data: {
        collection_name: "profile_photo"
        file_ext: "jpg"
        file_name: "default"
        file_path: "uploads/profile_photo"
        file_size: 62431
        file_type: "image/jpeg"
        model_id: "200090"
        model_type: "App\\Models\\User"
        orig_name: "20211128_165410.jpg"
        unique_name:  "1691502324_94b5e01970c97f5ac670.jpg"
    }
}
```

🪄 **Backend - Delete File**

```php

    public function api_delete()
    {
        return $this->user_model->clearMediaCollection(request()->getVar('temp_id'), 'profile_photo')->responseJson();
    }
```

You will get this response

```php
    {
        status: "success",
        message: "File 1691502324_94b5e01970c97f5ac670.jpg deleted successfuly",
    }
```

## Notes

Sorry if it looks completely messed up, i'm still develop an approach and functionality that might work like spatie media laravel.

Are you using this package and having a problem? feel free to open an issue.

and please, don't implement it with production yet, let me feel the pain first then u can use it after

## License

MIT License

## Contributing

You can contribute to this package by discovering bugs and opening issues. If you want to contribute code, please create a pull request. But you need to test it first using the demo project [Here](https://github.com/rachyharkov/codeigniter-4-media-debug)
