[![build status for the master branch](https://git.klink.asia/alessio.vertemati/laravel-tus-upload/badges/master/build.svg)](https://git.klink.asia/alessio.vertemati/laravel-tus-upload/commits/master) [![coverage report for the master branch](https://git.klink.asia/alessio.vertemati/laravel-tus-upload/badges/master/coverage.svg)](https://git.klink.asia/alessio.vertemati/laravel-tus-upload/commits/master)

# Laravel [Tus](http://tus.io/) based Upload

A package for handling resumable file uploads in a Laravel application via the [tus.io](http://tus.io/) resumable file upload protocol.

> **This package currently works only on Linux based OS.** If you want to try it on Windows 10, please take 
> into consideration to use the [Windows Subsystem for Linux](https://msdn.microsoft.com/en-us/commandline/wsl/install_guide)

## Features (some in development)

* [x] Resumable upload mechanism (with self distributed tusd binary)
* [x] Upload queue handling
* [x] Javascript Upload component
* [x] Hopefully easy setup

## Installation

To get started, install Laravel Tus Upload via the Composer package manager.

> Currently the package is not public, therefore you need to add a repository entry in your `composer.json` file

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://git.klink.asia/alessio.vertemati/laravel-tus-upload"
    }
]
```

Then you can require it

```json
"require": {
    "avvertix/laravel-tus-upload": "dev-master"
},
```

```
composer update avvertix/laravel-tus-upload
```

Next, register the TusUpload Service Provider in the providers array of your `config/app.php` configuration file:

```php
Avvertix\TusUpload\Providers\TusUploadServiceProvider::class,
```

**Database migrations**

The TusUpload service provider registers its own database migration directory with the framework, so 
you should migrate your database after registering the provider. 

```bash
php artisan migrate
```

The TusUpload migrations will create a [table to store the uploads queue](./docs/database.md).

In your `User` model you can, now, add the `HasUploads` trait in order to grab the current upload 
queue for a specific user.

**Authorizing an upload**

To overcome/prevent an un-authorized file upload the [upload creation endpoint](#api) is protected with the 
`web` guard and a gate. You see the [overall request flow](#upload-flow) for a better view on how the 
process works.

The Gate, named `upload-via-tus`, will let you verify deeply the upload action against the user that 
is performing it.

Currently you must define the [gate](https://laravel.com/docs/5.4/authorization#writing-gates) implementation.
You do it in the `boot` method in the `AuthServiceProvider` class, like the next code block

```php
/**
 * Register any authentication / authorization services.
 *
 * @return void
 */
public function boot()
{
    $this->registerPolicies();

    Gate::define('upload-via-tus', function ($user, $upload_request) {
        // $upload_request instanceof \Avvertix\TusUpload\Http\Requests\CreateUploadRequest
        // ...
    });
}
```

The callback will receive the `$user` that wants to do the upload and the `CreateUploadRequest`. The request might
contain custom metadata, according to the caller. Required inputs are the request `id`, the `filename`, while `filesize` 
might be set, even if null. The `filesize` can be null if the browser don't support the size property on the File object.
In addition the `filetype` attribute can be sent, if the file mime type is already known to client.

Additional metadata can be sent in the request. In this case the additional fields will be saved in 
the `metadata` field on the `TusUpload` object once the upload is authorized.

**Javascript and the frontend**

The package don't provide fancy Javascript based interactions, but only a library to perform the uploads.

The library is available in `public/js/tusuploader.js` and currently require [axios](https://github.com/mzabriskie/axios),
to make Ajax requests. Axios should be available on `window.axios`.

For an example on how to properly include axios you might want to take a look at the default 
[`bootstrap.js`](https://github.com/laravel/laravel/blob/v5.4.23/resources/assets/js/bootstrap.js#L22-L38)
file available in Laravel after a clean install.


### Advanced Configuration

Out of the box the package has some base defaults, like the location of the 
tusd executable, the upload folder and so on.

You can configure the tus related options via environment variables:

| variable                         | type    | description |
|----------------------------------|---------|-------------|
| `TUSUPLOAD_USE_PROXY`            | boolean | If the tusd server will run behind a proxy |
| `TUSUPLOAD_URL`                  | string  | The URL of the tus server endpoint if running behind a proxy |
| `TUSUPLOAD_HOST`                 | string  | The host on which the tusd server will listen for incoming connections |
| `TUSUPLOAD_PORT`                 | integer | The port on which the tusd server will listen for incoming connections |
| `TUSUPLOAD_HTTP_PATH`            | string  | The ULR path, on the `TUSUPLOAD_HOST` and `TUSUPLOAD_PORT`, where tusd will accept file uploads |
| `TUSUPLOAD_STORAGE_PATH`         | string  | Where the files are stored during and after the upload procedure |
| `TUSUPLOAD_STORAGE_MAXIMUM_SIZE` | number  | The maximum amount of space to use for storing the uploads, in bytes. |

In alternative, if you prefer, you can publish the configuration file in your Laravel installation.

```
php artisan vendor:publish --tag=tusupload-config
```

### Starting the Tus server

The tusd binary is already included in the package under the `/bin` folder. 
The binaries are available for MacOS, Windows and Linux, each executable 
has a suffix to distinguish between the OS version.

To execute the Tusd server launch the artisan `tus:start` command.

```bash
php artisan tus:start
```

This command will keep listening until killed.


### Running behind a proxy

If you are going to proxy requests to tusd, please refer to [Can I run tusd behind a reverse proxy?](https://github.com/tus/tusd#can-i-run-tusd-behind-a-reverse-proxy) for the proxy configuration.

In addition please specify the following configuration attributes in your `.env` file:

```
TUSUPLOAD_USE_PROXY=true
TUSUPLOAD_URL=http://example.dev/tus
TUSUPLOAD_HTTP_PATH=/tus/
TUSUPLOAD_HOST=0.0.0.0
```

where `http://example.dev/tus` is the absolute URL that the will be proxied to the tusd deamon.

## How it works (in brief)

A [tusd](https://github.com/tus/tusd) binary will listen for incoming uploads, sent by the javascript client. Via 
hooks the tusd executable calls the Laravel application to authorize the upload and to inform about the upload 
progress. At the end of the upload an event will be triggered to enable post-processing of the uploaded file.

For more information please refer to [docs/flow.md](./docs/flow.md) and [docs/database.md](./docs/database.md).

## Javascript library

*to be documented*

```html
<script src="./public/js/tusuploader.js"></script>
```

```js
var Uploader = new window.TusUploader({autoUpload: true});

var input = document.getElementById('file');

input.addEventListener("change", function(e) {
    // Get the selected file from the input element
    var file = e.target.files[0]

    // add it to the uploader queue
    var addedUpload = Uploader.upload(file);
});

```

## API

*to be documented*

## Events

### File Upload related events

All events have a single property called `upload` that contains the instance of 
the `TusUpload` being subject of the status change.

#### `TusUploadStarted`

The upload is started. At this stage the file don't exists yet and is safe to only consider the `filename` 
and eventual metadata sent by the client.

#### `TusUploadProgress`

The file upload is in progress. This event is triggered everytime a chunk of the file is uploaded. The 
`offset` value on the the `TusUpload` object will give the information on how many bytes have 
been transferred.

#### `TusUploadCompleted`

The file upload completed and is now safe to access the file content. The path on disk can be 
retrieved with the `path()` method on the `TusUpload` instance.

#### `TusUploadCancelled`

The user cancelled the upload. At this point the tus server might have already deleted the partial upload

### Server control events

#### `TusUploaderStarted`

Triggered when the server is listening for connections

#### `TusUploaderStopped`

Triggered when the server is being shutdown gracefully

## Faq

### Can be run on Windows?

Currently running tusd on Windows with the hook support is not possible, therefore if you are 
on Windows we encourage to use it through the [Windows Subsystem for Linux](https://msdn.microsoft.com/en-us/commandline/wsl/install_guide)

**tusd, the alternative start**

This is equal to the `tus:start` command with default options (assuming the start from the Laravel root folder)

```bash
# $PACKAGE_DIR is the directory in which the package content can be found
$PACKAGE_DIR/bin/tusd-linux --dir ./storage/app/uploads --hooks-dir $PACKAGE_DIR/hooks/linux -behind-proxy -base-path /uploads/
```

## What's the Tus Base Path

Tus base-path is the endpoint where tusd listen for file upload requests. To work it must end with `/`.

### I need a reverse proxy?

Probably yes. Tusd usually listen on a different port than the one configured for your application, if you want to have 
everything under the same port, you might want to use a proxy.

Please refer to [Can I run tusd behind a reverse proxy?](https://github.com/tus/tusd#can-i-run-tusd-behind-a-reverse-proxy) for
further explanation.


## Contributions

*to be documented*

## License

*to be decided*
