<!-- PROJECT LOGO -->
<br />
<p align="center">
  <a href="https://github.com/rachyharkov/codeigniter4-media">
    <img src="./src/assets/codeigniter-4-media-banner.png" alt="Banner"  height="200">
  </a>
  
  <h3 align="center">CodeIgniter 4 Media Library</h3>
</p>

Codeigniter package for to handle media upload file task (at least help a bit for my current job). My main goal on this package is codeigniter 4 have a library that be able to handle task such as organize file upload with minimial line of code, also i'm inspired by Laravel Media Library, so i decided to make this package.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![Issues][issues-shield]][issues-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

<!-- TABLE OF CONTENTS -->
## Table of Contents

- [Table of Contents](#table-of-contents)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Publishing Resource](#publishing-resource)
  - [Setup Model](#setup-model)
- [How to use?](#how-to-use)
  - [Store single File](#store-single-file)
  - [Store single file - store thumbnail](#store-single-file---store-thumbnail)
  - [Store single File - with custom name](#store-single-file---with-custom-name)
  - [Store Multi File - Different Name](#store-multi-file---different-name)
  - [Store Multi File - Same Name](#store-multi-file---same-name)
  - [Get Single File - Metadata](#get-single-file---metadata)
  - [Get Single File - Just URL](#get-single-file---just-url)
  - [Get All file of collection](#get-all-file-of-collection)
  - [Query result with more data? Just assign it](#query-result-with-more-data-just-assign-it)
  - [Delete file collection](#delete-file-collection)
  - [Delete and upload? Of course you can do that](#delete-and-upload-of-course-you-can-do-that)
  - [API Mode](#api-mode)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)
- [Support on Trakteer](#support-on-trakteer)


<!-- GETTING STARTED -->
## Getting Started

This is an example of how you may give instructions on setting up your project locally.
To get a local copy up and running follow these simple example steps.

### Prerequisites

- PHP 7.2+
- CodeIgniter Framework (4.* recommended)
- Composer
- PHP sockets extension enabled

### Installation

```sh
composer require rachyharkov/codeigniter4-media @dev
```
### Publishing Resource
You need to publish the resources for the default configuration
```sh
php spark media:publish
```

### Setup Model
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

### Store single File - with custom name

only use usingFileName method after addMediaFromRequest method, this will be useful if you want to rename the file before store it to database

```php
$this->user_model->insert($data);
$this->user_model->addMediaFromRequest('photo')->usingFileName('data_'.random(20))->toMediaCollection('profile_photo');
```

### Store Multi File - Different Name

store file from multi different request name (for example, you have 2 input file with different input file name attribute value, and you want to store it to same collection)

```php
$this->user_model->insert($data);
$this->user_model->addMediaWithRequestCollectionMapping([
      'file_input_photo' => 'profile_photo_collection',
      'file_input_profile_cover' => 'profile_photo_collection'
    ])
```

### Store Multi File - Same Name

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

$user = $this->user_model->where('id', $id)->first();
$user->photo = $this->user_model->mediaOf('profile_photo')->getFirstMedia();

return view('user/edit', $data);

```

above will return null if no file meta information returned, handle it like this

```php
    <img src="<?= $user->media ? $user->media->file_path.'/'.$user->media->file_name : $user->media ?>" alt="User Photo Profile">
```

### Get Single File - Just URL

This is the example of how to assign new object to existing object (for example user object) with new property (photo) that contains the url of file

```php
$user = $this->user_model->where('id', $id)->first();
$user->photo = $this->user_model->mediaOf('profile_photo')->getFirstMediaUrl();

return view('user/edit', $data);

```

It's possible to pass parameter with 'thumb' value to get the thumbnail of the file

```php
$media->getFirstMediaUrl('thumb');
```

### Get All file of collection



```php
$user = $this->user_model->where('id', $id)->first();
$user->collection_of_photo_profile = $this->user_model->mediaOf('profile_photo');

    return view('user/edit', $data);
```

### Query result with more data? Just assign it
The second parameter `mediaOf()` accept id of media who owned by the record
```php
$users  = $this->users_model->findAll();
foreach($users as $key => $value) {
    $users[$key]->photo = $this
                            ->ticket_log_model
                            ->mediaOf('profile_photo', $value->id)
                            ->getFirstMediaUrl();
}
```

### Delete file collection

```php
$this->product_model->where('id', $product->id)->delete();
$this->user_model->clearMediaCollection('profile_photo');
```

### Delete and upload? Of course you can do that
Just like this after you find or update the record

```php
$this->user_model->find($id);

    //or

$this->user_model->update($data, $id);

$this->user_model->clearMediaCollection('profile_photo');
$this->user_model->addMediaFromRequest('photo')->toMediaCollection('profile_photo');
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

On your controller, create method like this (make sure to return the responseJson method)

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

<!-- ROADMAP -->
## Roadmap

See the [open issues](https://github.com/rachyharkov/codeigniter4-media/issues) for a list of proposed features (and known issues).


<!-- CONTRIBUTING -->
## Contributing

Contributions are what makes the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request


<!-- LICENSE -->
## License

Distributed under the MIT License. See `LICENSE` for more information.

<!-- CONTACT -->
## Contact

Rachmad Nur Hayat - [https://rachmad.dev](https://rachmad.dev) - rachmadnurhayat@gmail.com


## Support on Trakteer
love what i made?, please support me on [Trakteer](https://trakteer.id/rachyharkov)

from overseas? you can [Paypal](https://paypal.me/rachyharkov?country.x=ID&locale.x=en_US) me

<p align="center">
    <a href="https://teer.id/rachmadnh" target="_blank"> <img align="left" src="https://trakteer-assets.sgp1.digitaloceanspaces.com/images/mix/navbar-logo.png?date=18-11-2023" height="30" alt="rachyharkov" /></a>
    </a>
    <a href="https://teer.id/rachmadnh" target="_blank"> <img align="left" src="https://static-00.iconduck.com/assets.00/paypal-icon-2048x547-tu0aql1a.png" height="30" alt="rachyharkov" /></a>
    </a>
</p>


<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/rachyharkov/codeigniter4-media.svg?style=flat-square
[contributors-url]: https://github.com/rachyharkov/codeigniter4-media/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/rachyharkov/codeigniter4-media.svg?style=flat-square
[forks-url]: https://github.com/rachyharkov/codeigniter4-media/network/members
[stars-shield]: https://img.shields.io/github/stars/rachyharkov/codeigniter4-media.svg?style=flat-square
[stars-url]: https://github.com/rachyharkov/codeigniter4-media/stargazers
[issues-shield]: https://img.shields.io/github/issues/rachyharkov/codeigniter4-media.svg?style=flat-square
[issues-url]: https://github.com/rachyharkov/codeigniter4-media/issues
[license-shield]: https://img.shields.io/github/license/rachyharkov/codeigniter4-media.svg?style=flat-square
[license-url]: https://github.com/rachyharkov/codeigniter4-media/blob/main/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=flat-square&logo=linkedin&colorB=555
[linkedin-url]: https://www.linkedin.com/in/rachmad-nur-hayat-731a391b2/
[product-screenshot]: images/screenshot.png

[ico-version]: https://img.shields.io/packagist/v/rachyharkov/codeigniter4-media.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/rachyharkov/codeigniter4-media.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/rachyharkov/codeigniter4-media
[link-downloads]: https://packagist.org/packages/rachyharkov/codeigniter4-media
[link-author]: https://github.com/rachyharkov