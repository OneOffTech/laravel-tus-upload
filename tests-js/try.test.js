const TusUploader = require('../assets/js/tusuploader');

test('tusuploader is defined', () => {
  
  var uploader = new TusUploader({autoUpload: true});
  
  expect(TusUploader).not.toBeUndefined();
  expect(uploader).not.toBeUndefined();
});

test('tusuploader raise error if endpoint url is null', () => {
  expect(() => {
    var uploader = new TusUploader({autoUpload: true, endpoint:null});
  }).toThrowError(/Url/);
});

test('tusuploader add function accepts a file and adds it to the queue', done => {

  var filename = 'foo.txt';
  var uploader = new TusUploader({autoUpload: false});


  uploader.on('upload.queued', function(event){

    expect(event).not.toBeUndefined();
    expect(event.upload).not.toBeUndefined();
    expect(event.type).not.toBeUndefined();

    done();

  });

  var file = new File(["foo"], filename, {
    type: "text/plain",
  });

  var added = uploader.add(file, {filetype: "text/plain"});

  expect(added).not.toBeUndefined();
  expect(added).toHaveProperty('status', TusUploader.Status.QUEUED);
  expect(added).toHaveProperty('id');
  expect(added).toHaveProperty('file');
  expect(added).toHaveProperty('uploadToken');
  expect(added).toHaveProperty('metadata');

  expect(added.metadata).toMatchObject([
    { filename: filename, upload_request_id : added.id, filetype: "text/plain" }
  ]);
  expect(uploader.uploads()).toHaveLength(1);

});

test('tusuploader remove function accepts an upload id and removes it from the queue', () => {
  
    var filename = 'foo.txt';
    var uploader = new TusUploader({autoUpload: false});
    const handler = jest.fn();
  
  
    uploader.on('upload.removed', handler);
  
    var file = new File(["foo"], filename, {
      type: "text/plain",
    });
  
    var added = uploader.add(file, {filetype: "text/plain"});
  
    expect(added).not.toBeUndefined();
    expect(added).toHaveProperty('id');
    
    
    var removed = uploader.remove(added.id);
    
    expect(removed).not.toBeUndefined();
    expect(removed).toHaveLength(1);
    expect(removed).toContain(added);
    expect(uploader.uploads()).toHaveLength(0);

    expect(handler).toHaveBeenCalled();
  
  });



