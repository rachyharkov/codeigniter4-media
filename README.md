# Codeigniter4-Media
Codeigniter package for to handle media upload file task (at least help a bit for my current job). My main goal on this package is codeigniter 4 have a library that be able to handle task such as organize file upload with minimial line of code

# Installation

`composer require rachyharkov/codeigniter4-media`

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
    $this->user_model->addMediaFromRequest('photo')->toMediaCollection('profile_photo')->withInsertedData();
```

### Get File

```php

$data['user'] = $this->user_model->findWithMedia($id)->getCollection('profile_photo')->getFirstMedia();

return view('user/edit', $data);

```

above will return string of no_image url fallback if no object of with file meta information returned, all you must can is by using is_object() like this

```php
    <img src="<?= is_object($user->media) ? $user->media->file_path.'/'.$user->media->file_name : $user->media ?>" alt="User Photo Profile">
```

### Get All file of collection (upcoming)

```php
    $data['user'] = $this->user_model->findWithMedia($id)->getCollection('profile_photo', true);

    return view('user/edit', $data);
```

### Delete file collection

```php
    $this->user_model->delete($id); // just general delete method
    $this->user_model->mediaCollectionOf('profile_photo')->clearMediaCollection($id);
```

### API Mode

create method in your controller just like this, set asTemp to true if you want to return the file metadata (this is useful if you want to show the file right after upload process completed, make sure to return it)

🪄 **Backend - Store File**

```php
public function api_upload()
{
    $user_id = $this->request->getVar('user_id');
    return $this->user_model->addMediaFromRequest('file')->toMediaCollection('profile_photo')->asTemp(true);
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
        file_path: "uploads/profile_photo/temp"
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
        return $this->user_model->mediaCollectionOf('announcement_attachment')->clearTempMedia(request()->getVar('temp_id'));
    }
```

You will get this response

```php
    {
        status: "success",
        message: "File 1691502324_94b5e01970c97f5ac670.jpg deleted successfuly",
    }
```

🪄 **Frontend Implementation**
Example using jquery

Set your html like this

```html

<div class="form-group">
  <label for="file">File</label>
  <input type="file" class="form-control" id="file" name="file">
</div>

<ul id="list_file"></ul>
```

Write your javascript like this

```js

let array_uploaded_file = [];

function render_list_file() {
  let html = '';
  array_uploaded_file.forEach((item, index) => {
    html += `
    <li style="font-size: 10px;">
      <span>${item.name}</span>
      <button type="button" class="btn btn-sm btn-copy-link" data-id="${index}"><i class="fa fa-copy"></i></button>
      <button type="button" class="btn btn-sm btn-danger btn-delete-file" data-id="${index}"><i class="fa fa-trash"></i></button>
    </li>`;
  });
  $('#list_file').html(html);
}

function copy_link(index) {
  let url = array_uploaded_file[index].url;
  navigator.clipboard.writeText(url).then(function() {
    Toast.fire({
      icon: 'success',
      title: 'Success',
      timer: 4000,
      text: 'Link Copied Successfuly'
    })
  }, function() {
    alert('Failed to copy link');
  });
}

function upload_file(file, type) {

  formData.append('file', file);
  
  $.ajax({
    url: '<?= url_to('admin.users.api_upload') ?>',
    type: 'POST',
    data: formData,
    contentType: false,
    processData: false,
    success: function(resp) {
      if(resp.status == 'success') {
        array_uploaded_file.push({
          name: resp.data.file_name,
          url: '<?= base_url() ?>' + resp.data.file_path + '/' + resp.data.file_name,
          temp_id: resp.data.unique_name
        });
        render_list_file();
      }
    }
  });
}

function delete_file(index) {
  $.ajax({
    url: '<?= url_to('admin.users.api_delete') ?>',
    type: 'POST',
    data: {
      temp_id: array_uploaded_file[index].temp_id
    },
    success: function(resp) {
      if(resp.status == 'success') {
        array_uploaded_file.splice(index, 1);
        render_list_file();
        Toast.fire({
          icon: 'success',
          title: 'Success',
          timer: 4000,
          text: resp.message
        })
      }
    },
    error: function() {
      alert('Failed to delete file');
    }
  });
}

$('#file').on('change', function() {
  let file = $(this).prop('files')[0];
  upload_file(file, 'gambar');
});

$(document).on('click', '.btn-copy-link', function() {
  copy_link($(this).data('id'));

  $(this).html('<i class="fa fa-check text-success"></i>');
  setTimeout(() => {
    $(this).html('<i class="fa fa-copy"></i>');
  }, 2000);
});

$(document).on('click', '.btn-delete-file', function() {
  delete_file($(this).data('id'));
});

```

## Notes

Sorry if it looks completely messed up, i'm still develop an approach and functionality that might work like spatie media laravel.

Are you using this package and having a problem? feel free to open an issue.

and please, don't implement it with production yet, let me feel the pain first then u can use it after
