# Laravel [Tus](http://tus.io/) based Upload

A package for handling resumable file uploads in a Laravel appliaction. Uses the [tus.io](http://tus.io/) resumable file upload protocol.

**This package currently works only on Linux based OS.** If you want to try it on Windows 10, please take 
into consideration to use the [Windows Subsystem for Linux](https://msdn.microsoft.com/en-us/commandline/wsl/install_guide)

## Features (some in development)

* [x] Resumable upload mechanism (with self distributed tusd binary)
* [x] Upload queue (mostly)
* [ ] Upload page template
* [ ] Javascript Upload component (basic)

## How it works

A [tusd](https://github.com/tus/tusd) binary will listen for incoming uploads, sent by the javascript client. Via 
hooks the tusd executable calls the Laravel application to authorize the upload and to monitor the upload 
progress. At the end of the upload an event will be triggered to enable post-processing of the uploaded file.

Authentication currently requires a token assigned to the user and passed to the javascript client. (this might 
change in the future).

## Installation

To get started, install Laravel Tus Upload via the Composer package manager:

```
# This will be the intended installation path, currently the package is not public
composer require alessio.vertemati/laravel-tus-upload
```

Next, register the TusUpload service provider in the providers array of your `config/app.php` configuration file:

```php
Avvertix\TusUpload\Providers\TusUploadServiceProvider::class,
```

The TusUpload service provider registers its own database migration directory with the framework, so 
you should migrate your database after registering the provider. 

The TusUpload migrations will create the tables your application needs to store the uploads queue:

```bash
php artisan migrate
```

In your `User` model you can, now, add the `HasUploads` trait in order to grab the current upload 
queue for a specific user.

### Configuration

Out of the box the package has some base defaults, like the location of the 
tusd executable, the upload folder and so on.

If you want to override the options publish the configuration file in your Laravel installation.

```
php artisan vendor:publish --tag=tusupload-config
```

If you don't need to configure all options, the configuration via environment variables is 
also supported, here are the ones you might want to change.

- `TUSUPLOAD_USE_PROXY`: (boolean) If the tusd server will run behind a proxy
- `TUSUPLOAD_HOST`: (string) The host on which the tusd server will listen for incoming connections
- `TUSUPLOAD_PORT`: (string) The port on which the tusd server will listen for incoming connections
- `TUSUPLOAD_HTTP_PATH`: (string) The path on the (host and port), where tusd will accept connections
- `TUSUPLOAD_STORAGE_PATH`: (string) Where the files are stored during and after the upload procedure
- `TUSUPLOAD_STORAGE_MAXIMUM_SIZE`: (number of bytes) The maximum amount of space to use for storing 
   the uploads.

### Authenticating an Upload Request

The connection between tusd and Laravel happens via command line hooks, therefore usual authentication 
methods via HTTP request cannot be used.

To overcome/prevent an un-authorized file upload we require to have in place an `api` guard 
with a driver that supports authentication with an `api_token` field, like the `token` driver.

The `api` guard will be used also for authenticating requests to the offered API for checking and 
listing the uploads of a user.

If you don't have already a `token` based API, add the `api_token` field to the user table.

```php
// migration to add the api_token field to the users table
$table->string('api_token', 60)->unique();
```

Make sure, also, that in the `auth.php` configuration file the `api.driver` configuration is set to `token`.

```php
'guards' => [
    // ...

    'api' => [
        'driver' => 'token',
        'provider' => 'users',
    ],
],
```

The javascript client will send the API token together with the upload request, so it can be verified 
before letting the upload begin. In this way we confirm that the user exists on the system.

Furthermore, depending on your application logic, we use a Gate named `tusupload-can-upload`, if defined, 
will let you verify deeply the upload action against the user that is performing it.

You can define it with:

```php
Gate::define('tusupload-can-upload', function ($user, array $upload_metadata) {
    // ...
});
```

The callback will receive the `$user` that wants to do the upload and the metadata of the file to be uploaded, 
in an associative array form.

### Starting the Tus server

The tusd binary is already included in the package in the `/bin` folder. The binaries are available for MacOS,
 Windows and Linux, each executable has a suffix to distinguish between the OS version.

To execute the Tusd server with all the parameters and configuration the artisan `tus:start` command is available.

```bash
php artisan tus:start
```

This command will keep listening until killed.

## Javascript library

*to be documented*

## API

*to be documented*

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
