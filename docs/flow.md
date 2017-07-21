## How the upload flow works

The upload process is separated in two distinct phases:

1. Granting the upload to the client
2. Transfering the file via Tus protocol

### Granting the upload to the client

The first phase has the primary objective to authenticate the user that is making the upload request.

The client sends a post request to `/uploadjobs` with the details of the upload, especially the request 
identifier and the filename. The controller imposes the `web` guard on the route to ensure a session for 
the user is active and checks if the Gate `upload-via-tus` grants the continuation of the upload, for the 
user and the specific data passed in the request input.

If the request is authorized and validated, an entry in the upload queue table will be added, with a 
specific `upload_token`. This token will ensure that the next phase is authorized to perform an upload.

When the upload queue entry is setup the `TusUploadStarted` is triggered.


### Transfering the file via Tus protocol

Once the authorization phase completed, the client can start sending data to the tus server.

_1 - start of the upload_

In this phase the client sends the request for starting an upload to the tus server with the 
`upload_token` as a metadata.

The tus server executes the `pre-create` hook, which invokes Laravel via an Artisan command, to 
confirm that the upload token is valid and the upload can continue.

_2 - uploading file content_

The client, in this phase, proceed with data sending as described in the Tus protocol. On the server
side the hook `post-receive` is triggered after each file chunk is received. The handling of this hook 
is, again, performed via Artisan command and will update the entry in the upload queue.

The rest of the application can listen for the `TusUploadProgress` event to gather the updated information 
on the upload progress as soon as they arrive.

_3 - completing the file upload_

When the upload is finished, the tus server invokes the `post-finish` hook. In this case the handler 
will mark the upload completed and trigger the `TusUploadCompleted` event.

The application, within the event handler, can do operations on the file. It is encouraged to move the 
physical file to a different location than the tus server upload directory, as some file systems 
might have a limit on the number of files you can store in a directory.

_4 - in case of upload cancel_

In case the client aborts the transfer, the tus server will call the `post-terminate` hook. This will 
mark the upload as cancelled and contextually trigger the `TusUploadCancelled` event. 
The partial uploaded file might not exists anymore at the time of the `TusUploadCancelled` event, 
because the tus server might have already deleted it.

