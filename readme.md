

# Laravel [Tus](http://tus.io/) based Upload

A package for handling resumable file uploads in a Laravel application via the [tus.io](http://tus.io/) resumable file upload protocol.

This package contains a PHP component for controlling the Tus upload server and a javascript library for interacting with the server. The Tus upload server is the official [Tus server binary](https://github.com/tus/tusd), this package does not re-implement the tus protocol in pure PHP.

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

Now you need to specify, in `composer.json`, that you accepts dev packages

```json
"minimum-stability": "dev",
"prefer-stable": true,
```

With `"prefer-stable": true` composer will not try to download every development versions of 
your, already added, dependencies.

Now you can require the package with

```bash
composer require OneOffTech/laravel-tus-upload
```

Next, register the TusUpload Service Provider in the providers array of your `config/app.php` configuration file:

```php
OneOffTech\TusUpload\Providers\TusUploadServiceProvider::class,
```

> Laravel 5.5 auto-registration is not supported yet

**Routes registration**

The API routes are not anymore registered automatically by the service provider. This was done to enable 
new integration scenario and more customization options.

To register the routes call `\OneOffTech\TusUpload\Tus::routes()` from within your application `RouteServiceProvider`

```php
<?php

namespace App\Providers;

use OneOffTech\TusUpload\Tus;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    // ...

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        Tus::routes();

        parent::boot();
    }
}
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
The suggested location where the Gate can be defined is it in the `boot` method of the `AuthServiceProvider` class:

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
        // $upload_request instanceof \OneOffTech\TusUpload\Http\Requests\CreateUploadRequest
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
The binaries are available for MacOS, Windows and Linux. The included binaries have been compiled for 64 bit architecture. Each executable has a suffix to distinguish between the OS version.

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
var uploader = new window.TusUploader({autoUpload: true});

var input = document.getElementById('file');

input.addEventListener("change", function(e) {
    // Get the selected file from the input element
    var file = e.target.files[0]

    // add it to the uploader queue
    var addedUpload = uploader.upload(file);
});

```

### `TusUploader` object

The `TusUploader` object handles file upload and queue management. To create an instance of the `TusUploader` use 
the constructor function.

```js
var uploader = new window.TusUploader(options: { /*...*/ });
```

**arguments**

- `option: Object`:
 - `endpoint`: the URL path to which the library calls for authorizing a file upload
 - `retryDelays`: the array of delays, in milliseconds, that will be used in case the tus server is not replying to requests
 - `autoUpload`: a boolean indicating whenever the file added to the queue must be uploaded immediately

**methods**

- `add(file, metadata) : TusUploader.Upload` adds a file to the upload queue
- `remove(id) : TusUploader.Upload[]` remove a file, given its id, from the queue. It cancel the upload if already in progress
- `uploads(filter) : TusUploader.Upload` retrieve the upload queue. Optionally can be filtered using the filter predicate
- `on(event, callback)` register an event listener
- `off(event, callback)` unregister a previously registered event listener

### `TusUploader.Upload` object

The `TusUploader.Upload` object define a single file added to the queue of the uploads

**properties**

- `id`: the identifier associated to this upload
- `metadata`: the metadata information about the upload, by default the filename. It is enriched with the metadata added once the upload has been added to the queue
- `transport`: the instance of the TusClient that will handle the real file upload
- `status`: the file upload status, see TusUploader.Status
- `uploadToken`: the upload authentication token granted by the server
- `uploadPercentage`: the completion percentage
- `uploadSize`: the total file size in bytes
- `uploadTransferredSize`: the bytes received by the server
- `file`: the original File instance added to the queue

**methods**

- `start`: start the upload
- `stop`: stop and cancel the upload


### Events

- `upload.queued` a File was added to the upload queue
- `upload.started` a File upload, in the queue, was started
- `upload.progress` a File in the queue is being uploaded and this is the last progress report
- `upload.completed` a File upload completed
- `upload.cancelled`: upload was in progress, but has been interruped
- `upload.failed`: a File upload failed
- `upload.removed`: a queued upload has been removed before sending the file to the server


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

### What's the Tus Base Path

Tus base-path is the endpoint where tusd listen for file upload requests. To work it must end with `/`.

### I need a reverse proxy?

Probably yes. Tusd usually listen on a different port than the one configured for your application, if you want to have 
everything under the same port, you might want to use a proxy.

Please refer to [Can I run tusd behind a reverse proxy?](https://github.com/tus/tusd#can-i-run-tusd-behind-a-reverse-proxy) for
further explanation.


## Contributions

Thank you for considering contributing to the Laravel Tus Upload package!

The contribution guide is not available yet, but in the meantime you can still submit Pull Requests.

Development oriented documentation is located under the [`docs`](./docs/) folder in this repository.

## License

This project is licensed under the MIT license, see [LICENSE.txt](./LICENSE.txt).
