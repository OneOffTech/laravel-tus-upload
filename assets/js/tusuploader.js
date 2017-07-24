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

/**
 * Creates a new Tus based file Uploader
 * 
 * @param {object} options
 */
module.exports = function(options){

    var UploadStatus = {
        PENDING: 1,
        UPLOADING: 2,
        RETRYING: 3,
        COMPLETED: 4,
        CANCELLED: 5,
        FAILED: 6,
    }

    var defaultOptions = {
        /**
         * The TUSd server endpoint
         * 
         * @var {string}
         */
        // tus_endpoint: "http://192.168.0.36:1080/uploads/", // might be in the response from the server
        /**
         * The endpoint that will authorize the upload request
         * @var {string}
         */
        endpoint: "/uploadjobs/",
        /**
         * 
         * @var {array}
         */
        retryDelays: [0, 1000, 3000, 5000],
        /**
         * Automatically starts the upload after has been added to the queue
         * 
         * @default false
         * @var {boolean}
         */
        autoUpload: false,
    };

    var uploadsQueue = [];

    options = assignIn(defaultOptions, options || {});

    if (typeof document.querySelector === undefined) {
        throw new Error("TusUpload: Browser not supported.");
    }

    if (!options.endpoint) {
        throw new Error("TusUpload: Url not specified.");
    }

    if(!tus.isSupported) {
        throw new Error("TusUpload: Tus upload protocol not supported.");
    }




    function handleUploadError(error){
        console.log('UploadError', this, "Failed because:", error);
    }

    
    function handleUploadProgress(bytesUploaded, bytesTotal){
        var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2);
        console.log('UploadProgress', this, bytesUploaded, bytesTotal, percentage + "%");
    }
    
    function handleUploadSuccess(){
        console.log('UploadComplete', this/*, "Download %s from %s", upload.file.name, upload.url*/);
    }



    /**
     * Creates a new upload
     * 
     * @param {File} file the file to upload
     * @param {Object} metadata additional metadata to sent to the server 
     * @return {Upload}
     */
    function Upload(file, metadata){

        this.id = cuid();

        this.metadata = assignIn({
            filename: file.name,
            upload_request_id: this.id
        }, metadata || {});

        // Create a new tus upload
        this.transport = new tus.Upload(file, {
            endpoint: options.tus_endpoint,
            retryDelays: options.retryDelays,
            metadata: this.metadata,
            onError: handleUploadError.bind(this),
            onProgress: handleUploadProgress.bind(this),
            onSuccess:  handleUploadSuccess.bind(this)
        });

        this.status = UploadStatus.PENDING;
        this.uploadToken = null;

        this.file = file;

        return this;
    }


    /**
     * Stop the upload
     * 
     * @return {Upload}
     */
    Upload.prototype.stop = function(){
        this.transport.abort();
        this.status = UploadStatus.CANCELLED;
        return this;
    }

    /**
     * Starts the upload
     * 
     * @return {Upload}
     */
    Upload.prototype.start = function(){

        window.axios.post(options.endpoint, assignIn({
            id: this.id,
            filename: this.metadata.filename,
            filesize: this.file.size || '',
            filetype: this.file.type || '',
        }, this.metadata )).then(function(response){

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
     * Upload a file
     * 
     * @param {File} file https://developer.mozilla.org/en-US/docs/Web/API/File
     * @param {Object} metadata Application level metatada about the file that should be sent to the server
     * @return {Upload} the upload entry added to the queue
     */
    TusUploadInner.upload = function(file, metadata){

        // Create a new upload
        var upload = new Upload(file, metadata);
        
        // add it to the queue
        uploadsQueue.push(upload);

        if(options.autoUpload){
            // Immediately start the upload
            upload.start();
        }

        return upload;
    }

    TusUploadInner.Status = UploadStatus;

    return TusUploadInner;
}

