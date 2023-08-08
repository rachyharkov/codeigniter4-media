#Codeigniter4-Media
Codeigniter package for to handle media upload file task (at least help a bit for my current job). My main goal on this package is codeigniter 4 have a library that be able to handle task such as organize file upload with minimial line of code

# Installation

`composer require rachyharkov/codeigniter4-media`

just set your model like this

```
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

`$this->users_model->addMediaFromRequest('photo_profile')->toMediaCollection('users')->of($id)`

### Get File(s)

`$this->users_model->findWithMedia($id)->getCollection('users',true) //will return all the file from users collection with owner of $id`

`$this->users_model->findWithMedia($id)->getCollection('users')->getFirstMediaUrl() // will return URL of the file or using getFirstMedia() to get the file data`

### Delete file - whole file in "users" collection

`$this->users_model->findWithMedia($id)->clearMediaCollection('users');`

## Notes

Sorry if it looks completely messed up, i'm still develop an approach and functionality that might work like spatie media laravel.

Are you using this package and having an issue? feel free to open an issue.

and please, don't implement it with production yet, let me feel the pain first then u can use it after
