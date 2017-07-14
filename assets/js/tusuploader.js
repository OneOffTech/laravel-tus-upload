/**
 * Tus based client uploader.
 * 
 * It gives you the ability to upload a file to the reference tus server
 */

var tus = require("tus-js-client");
var Hogan = require('hogan.js');
var EventEmitter = require('eventemitter-light');
var cuid = require('cuid');
var lodash = require('lodash');


/**
 * 
 */
module.exports = function(selector, options){

    var UploadStatus = {
        PENDING: 1,
        UPLOADING: 2,
        RETRYING: 3,
        COMPLETED: 4,
        CANCELLED: 5,
        FAILED: 6,
    }

    var defaultOptions = {
        endpoint: "http://192.168.0.36:1080/uploads/",
        api_token: null,
        retryDelays: [0, 1000, 3000, 5000],
        /**
         * Automatically starts the upload after has been added to the queue
         */
        autoUpload: false,
    };

    var uploadsQueue = [];

    options = lodash.assignIn(defaultOptions, options || {});

    if (typeof document.querySelector === undefined) {
        throw new Error("TusUpload: Browser not supported.");
    }

    if (!options.endpoint) {
        throw new Error("TusUpload: Url not specified.");
    }

    if (!options.api_token) {
        throw new Error("TusUpload: API Token/Secret not specified.");
    }

    if(!tus.isSupported) {
        throw new Error("TusUpload: Tus upload protocol not supported.");
    }




    function handleUploadError(error){
        console.log('UploadError', this, "Failed because: " + error);
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

        // Create a new tus upload
        this.transport = new tus.Upload(file, {
            endpoint: options.endpoint,
            retryDelays: options.retryDelays,
            metadata: lodash.assignIn({
                filename: file.name,
                api_token: options.api_token,
                upload_request_id: this.id
            }, metadata || {}),
            onError: handleUploadError.bind(this),
            onProgress: handleUploadProgress.bind(this),
            onSuccess:  handleUploadSuccess.bind(this)
        });

        this.status = UploadStatus.PENDING;

        this.file = file;

        return this;
    }


    /**
     * 
     * @return {Upload}
     */
    Upload.prototype.stop = function(){
        this.transport.abort();
        this.status = UploadStatus.CANCELLED;
        return this;
    }

    /**
     * 
     * @return {Upload}
     */
    Upload.prototype.start = function(){
        
        this.transport.start();
        this.status = UploadStatus.UPLOADING;

        return this;
    }

    var TusUploadInner = {};

    /**
     * 
     * @param File file https://developer.mozilla.org/en-US/docs/Web/API/File
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

        // console.log(upload);

        return upload;
    }


    return TusUploadInner;
}

