/**
 * Tus based client uploader.
 * 
 * It gives you the ability to upload a file to the reference tus server
 */

// Global axios

var tus = require("../../node_modules/tus-js-client/dist/tus");
var EventEmitter = require('eventemitter-light');
var cuid = require('cuid');
var assignIn = require('lodash.assignIn');
var _ = {
    assignIn : assignIn,
    remove : require('lodash.remove'),
    filter : require('lodash.filter')
}

/**
 * Creates a new Tus based file Uploader
 * 
 * @param {object} options
 */
module.exports = function (options) {

    var UploadStatus = {
        /**
         * Upload has been queued and waiting to be processed
         */
        QUEUED: 1,
        /**
         * Upload is in progress
         */
        UPLOADING: 2,
        /**
         * Upload is completed
         */
        COMPLETED: 3,
        /**
         * Upload has been cancelled
         */
        CANCELLED: 4,
        /**
         * File upload failed
         */
        FAILED: 5
    }

    var defaultOptions = {
        /**
         * The endpoint that will authorize the upload request
         * @var {string}
         * @default /uploadjobs/ 
         */
        endpoint: "/uploadjobs/",
        /**
         * Retry delays in case of upload error
         * @var {array}
         * @default [0,1000,3000,5000]
         */
        retryDelays: [0, 1000, 3000, 5000],
        /**
         * Automatically starts the upload after has been added to the queue
         * 
         * @default false
         * @var {boolean}
         */
        autoUpload: false,
        /**
         * The number of bytes for each file chunk sent
         * 
         * @default 5000
         * @var {integer}
         */
        chunkSize: 5000
    };

    var ee = Object.create(EventEmitter).constructor();

    var uploadsQueue = [];

    options = _.assignIn(defaultOptions, options || {});

    if (typeof document.querySelector === undefined) {
        throw new Error("TusUpload: Browser not supported.");
    }

    if (!options.endpoint) {
        throw new Error("TusUpload: Url not specified.");
    }

    if (!tus.isSupported) {
        throw new Error("TusUpload: Tus upload protocol not supported.");
    }



    /**
     * Handle the tus-client error event
     */
    function handleUploadError(error) {
        console.log('UploadError', this, "Failed because:", error);

        this.status = UploadStatus.FAILED;

        ee.emit('upload.failed', { upload: this, type: 'upload.failed', error: error });
    }

    /**
     * Handle the tus-client onChunkComplete event
     */
    function handleUploadProgress(chunkSize, bytesUploaded, bytesTotal) {
        var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2);
        console.log('UploadProgress', this, bytesUploaded, bytesTotal, percentage + "%");

        this.status = UploadStatus.UPLOADING;
        this.uploadPercentage = percentage;

        ee.emit('upload.progress', { upload: this, type: 'upload.progress', percentage: percentage, total: bytesTotal, transferred: bytesUploaded });
    }

    /**
     * Handle the tus-client success event
     */
    function handleUploadSuccess() {
        console.log('UploadComplete', this/*, "Download %s from %s", upload.file.name, upload.url*/);

        this.status = UploadStatus.COMPLETED;

        ee.emit('upload.completed', { upload: this, type: 'upload.completed' });
    }



    /**
     * Creates a new upload
     * 
     * @param {File} file the file to upload
     * @param {Object} metadata additional metadata to sent to the server 
     * @return {Upload}
     */
    function Upload(file, metadata) {

        this.id = cuid();

        this.metadata = _.assignIn({
            filename: file.name,
            upload_request_id: this.id
        }, metadata || {});

        // Create a new tus upload
        this.transport = new tus.Upload(file, {
            endpoint: options.tus_endpoint,
            retryDelays: options.retryDelays,
            chunkSize: options.chunkSize,
            metadata: this.metadata,
            onError: handleUploadError.bind(this),
            onChunkComplete: handleUploadProgress.bind(this),
            onSuccess: handleUploadSuccess.bind(this)
        });

        this.status = UploadStatus.QUEUED;
        this.uploadToken = null;
        this.uploadPercentage = 0;
        this.uploadRemainingTime = null;

        this.file = file;

        return this;
    }


    /**
     * Stop the upload
     * 
     * @return {Upload}
     */
    Upload.prototype.stop = function () {
        this.transport.abort();
        this.status = UploadStatus.CANCELLED;
        ee.emit('upload.cancelled', { upload: this, type: 'upload.cancelled' });
        return this;
    }

    /**
     * Starts the upload
     * 
     * @return {Upload}
     */
    Upload.prototype.start = function () {

        ee.emit('upload.started', { upload: upload, type: 'upload.started' });

        window.axios.post(options.endpoint, assignIn({
            id: this.id,
            filename: this.metadata.filename,
            filesize: this.file.size || '',
            filetype: this.file.type || '',
        }, this.metadata)).then(function (response) {

            this.uploadToken = response.data.upload_token;
            this.transport.options.metadata.token = this.uploadToken;
            this.transport.options.endpoint = response.data.location;

            // set the upload token in the metadata of the transport
            this.status = UploadStatus.UPLOADING;
            this.transport.start();

        }.bind(this)).catch(function (error) {
            console.log(error);
        }.bind(this));


        return this;
    }

    var TusUploadInner = {};

    /**
     * Add a file to the upload queue
     * 
     * @param {File} file https://developer.mozilla.org/en-US/docs/Web/API/File
     * @param {Object} metadata Application level metatada about the file that should be sent to the server
     * @return {Upload} the upload entry added to the queue
     */
    TusUploadInner.upload = function (file, metadata) {

        // Create a new upload
        var upload = new Upload(file, metadata || {});

        // add it to the queue
        uploadsQueue.push(upload);

        ee.emit('upload.queued', { upload: upload, type: 'upload.queued' });

        if (options.autoUpload) {
            // Immediately start the upload
            upload.start();
        }

        return upload;
    }

    TusUploadInner.add = TusUploadInner.upload;

    /**
     * Remove the upload, identified by its id, from the queue.
     * If the upload is already started, it will be cancelled.
     * 
     * @emits upload.removed 
     * @emits upload.cancelled if the upload was already in progress before the removal 
     * @return {Array|null} the removed elements or null, if nothing has been removed
     */
    TusUploadInner.remove = function(id){

        var removed = _.remove(uploadsQueue, function(n){
            return n.id === id;
        });

        if(removed && removed.length >= 1){

            removed.forEach(function(element) {
                if(element.status === UploadStatus.UPLOADING){
                    element.stop();
                }
            }, this);

            ee.emit('upload.removed', { upload: removed, type: 'upload.removed' });

            return removed;
        }

        return null;
    }
    
    /**
     * Access the upload queue
     * 
     * @param {Function} [filter=_.identity] The filtering function, if specified allows to filter the upload queue
     * @example
     * TusUploads.uploads({ 'status': TusUploads.UploadStatus.COMPLETED });
     */
    TusUploadInner.uploads = function(filter){
        return _.filter(uploadsQueue, filter);
    }

    /**
     * Add an event listener
     * 
     * @param {string} event the event name to listen for
     * @param {function} callback the callback invoked when the event is emitted. It will receive the event object as first parameter
     * @return {TusUploader}
     */
    TusUploadInner.on = function (event, callback) {

        ee.on(event, callback);

        return TusUploadInner;
    }

    /**
     * Remove an event listener
     * 
     * @param {string} event the event to unregister
     * @param {function} callback the callback used during event registration
     * @return {TusUploader}
     */
    TusUploadInner.off = function (event, callback) {

        ee.removeListener(event, callback);

        return TusUploadInner;
    }

    TusUploadInner.Status = UploadStatus;
    TusUploadInner.Upload = Upload;

    return TusUploadInner;
}

