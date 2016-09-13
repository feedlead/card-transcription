<?php
# returns json status of ok or error with message
# anyone can post to this, but unless the images are in the bucket,which can only be added to by authorized,
# the post is ignored

#ideally, this message would be passed by a private messaging system like aws sqs, but
# have to write this assuming no way to set up a cron job or independent task that runs in background

require_once '../users/init.php';
require_once $abs_us_root.$us_url_root.'/users/includes/header_json.php';
require_once $abs_us_root.$us_url_root.'lib/aws/aws-autoloader.php';
require_once $abs_us_root.$us_url_root.'pages/helpers/pages_helper.php';

$ret = $_POST;
/* post contains
 *  $msg = [
         'client_id' => $client_id,
         'profile_id' => $profile_id,
         'front' => $front_key_name,
         'back' => $back_key_name,
         'timestamp' => time(),
         'bucket' => $to_bucket_name,
         'front_width'  => $row->front_width,
         'front_height'  => $row->front_height,
         'back_width'  => $row->back_width,
         'back_height'  => $row->back_height,
         'front_type' => $row->front_file_type,
         'back_type' => $row->back_file_type,
         'uploader_email' => $row->uploader_email,
         'uploader_lname' => $row->uploader_lname,
         'uploader_fname' => $row->uploader_fname,
         'uploaded_at'  => $row->created_at
        ];
 */



$db = DB::getInstance();
$fields=array(
     'client_id' => $_POST['client_id'],
     'profile_id' => $_POST['profile_id'],
     'uploaded_at' => $_POST['uploaded_at'],
     'created_at' => time(),
     'modified_at' => time(),
     'uploader_email' => $_POST['uploader_email'],
     'uploader_lname' => $_POST['uploader_lname'],
     'uploader_fname' => $_POST['uploader_fname']

);
$what = $db->insert('ht_jobs',$fields);
if (!$what) {
    printErrorJSONAndDie('could not create job: '. $db->error());
}
$jobid = $db->lastId();

// Create an SDK class used to share configuration across clients.
// api key and secret are in environmental variables
$sharedConfig = [
    'region'  => getenv('AWS_REGION'),
    'version' => 'latest'
];

$sdk = new Aws\Sdk($sharedConfig);

// Use an Aws\Sdk class to create the S3Client object.
$s3Client = $sdk->createS3();

# get our bucket for this server (maybe same or different)
$our_bucket = $settings->s3_bucket_name;
$their_bucket = $_POST['bucket'];

$front_key_name = $_POST['front'];
$front_type = $_POST['front_type'];
$front_width = $_POST['front_width'];
$front_height = $_POST['front_height'];

$back_key_name = $_POST['back'];
$back_type = $_POST['back_type'];
$back_width = $_POST['back_width'];
$back_height = $_POST['back_height'];

$updatetime =  $_POST['uploaded_at'];
$uploaded_date_string = date('Ymd',$updatetime);
$clientID = $_POST['client_id'];
$profileID = $_POST['profile_id'];
//img1234567a_id0268_p02_YYYYMMDD.jpg
$new_front_key_name = "img{$jobid}a_id{$clientID}_p{$profileID}_{$uploaded_date_string}.{$front_type}";
$new_back_key_name = "img{$jobid}b_id{$clientID}_p{$profileID}_{$uploaded_date_string}.{$front_type}";

try {
    @$s3Client->copyObject(array(
        'Bucket'     => $our_bucket,
        'Key'        => $new_front_key_name,
        'CopySource' => "{$their_bucket}/{$front_key_name}",
    ));
} catch (S3Exception $e) {
    $db->update('ht_jobs', $jobid, ['error_message' => $e->getMessage()]);
    printErrorJSONAndDie('could not move front image in bucket: '. $e->getMessage());
}

try {
    $front_url = @$s3Client->getObjectUrl($our_bucket, $new_front_key_name);
} catch (S3Exception $e) {
    $db->update('ht_jobs', $jobid, ['error_message' => $e->getMessage()]);
    printErrorJSONAndDie('could not get front image url: '. $e->getMessage());
}

try {
    @$s3Client->copyObject(array(
        'Bucket'     => $our_bucket,
        'Key'        => $new_back_key_name,
        'CopySource' => "{$their_bucket}/{$back_key_name}",
    ));
} catch (S3Exception $e) {
    $db->update('ht_jobs', $jobid, ['error_message' => $e->getMessage()]);
    printErrorJSONAndDie('could not move back image in bucket: '. $e->getMessage());
}

try {
    $back_url = @$s3Client->getObjectUrl($our_bucket, $new_back_key_name);
} catch (S3Exception $e) {
    $db->update('ht_jobs', $jobid, ['error_message' => $e->getMessage()]);
    printErrorJSONAndDie('could not get back image url: '. $e->getMessage());
}



#move each image to have proper name, and make ht_image

$fields=array(
    'ht_job_id' => $jobid,
    'side' => 0,
    'image_type' => $front_type,
    'bucket_name' => $our_bucket,
    'key_name' => $new_front_key_name,
    'image_url' => $front_url,
    'image_height' => $front_height,
    'image_width' => $front_width,
    'created_at' => time(),
    'modified_at' => time()

);
$what = $db->insert('ht_images',$fields);
if (!$what) {
    $db->update('ht_jobs', $jobid, ['error_message' => $db->error()]);
    printErrorJSONAndDie('could not create front image: '. $db->error());
}

$fields=array(
    'ht_job_id' => $jobid,
    'side' => 1,
    'image_type' => $back_type,
    'bucket_name' => $our_bucket,
    'key_name' => $new_back_key_name,
    'image_url' => $back_url,
    'image_height' => $back_height,
    'image_width' => $back_width,
    'created_at' => time(),
    'modified_at' => time()

);
$what = $db->insert('ht_images',$fields);
if (!$what) {
    $db->update('ht_jobs', $jobid, ['error_message' => $db->error(),'modified_at'=>time()]);
    printErrorJSONAndDie('could not create back image: '. $db->error());
}

/*
 *  `ht_job_id`, `side`, `image_type`, `bucket_name`, `key_name`, `image_url`, `image_height`, `image_width`
 */

//notifications can happen when they are logged in so this is all this call does
// if got here then signal this in the job
$what = $db->update('ht_jobs', $jobid, ['is_initialized' => 1]);
if (!$what) {
    $db->update('ht_jobs', $jobid, ['error_message' => $db->error(),'modified_at'=>time()]);
    printErrorJSONAndDie('could not toggle initialized flag for jobs: '. $db->error());
}

$ret['message']= "started job {$jobid}";
printOkJSONAndDie($ret);