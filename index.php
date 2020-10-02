<?php
require_once "../config.php";

error_reporting(E_ALL & ~E_NOTICE);

// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

// Handle the incoming post first
if ( SettingsForm::handleSettingsPost() ) {
    header('Location: '.addSession('index.php') ) ;
    return;
}

// Render the View
$OUTPUT->header();
?>

<link rel="stylesheet" href="css/custom.css">

<?php
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

$dir = "svn/homepage/";
?>

<div class="row">
    <div class="col-sm-6">
      <!--<h3>VULA File Management Admin UI</h3>-->
      <div class="form-group form-inline">
      <br/>
        <label>Search:</label>
        <input type="text" class="form-control xsmall" onkeyup="searchTable()" id="myInput" placeholder="file name / expiry date / url"/>
      </div>
    </div>
    <div class="col-sm-6 text-right">
        <h3><button type="button" class="btn btn-default scheduler btn-sm" id="uploadFile" data-toggle='modal' data-target='#uploadModal'><i class="fa fa-upload"></i> Upload</button></h3>
        <div class="justify-content-center p-3" id="catFilterBtnContainer">
          <span class="mr-4 small">Category:</span>
          <a type="button" class="btn-floating btn_filter btn btn-sm btn-info" id="eventsFilter" onclick="filterTable('events')">events</a>
          <a type="button" class="btn-floating btn_filter btn btn-sm btn-info" id="cetFilter" onclick="filterTable('cet')">cet</a>
          <a type="button" class="btn-floating btn_filter btn btn-sm btn-info" id="srcFilter" onclick="filterTable('src')">src</a>
        </div>
        <div class="justify-content-right" id="statusFilterBtnContainer">
          <span class="mr-4 small text-right">Status:</span>
          <a type="button" class="btn-floating btn_filter btn-sm btn btn-info" id="activeFilter" onclick="filterTable('active')">Active</a>
          <a type="button" class="btn-floating btn_filter btn-sm btn btn-info" id="archiveFilter" onclick="filterTable('archive')">Archive</a>
          <a type="button" class="btn-floating btn_filter btn-sm btn btn-info active" id="allFilter" onclick="filterTable('all')">All</a><br/>
        </div><br/>
  </div>

<table class="table table-hover table-condensed table-bordered table-striped" id="filesTable">
  <thead>
    <th onclick="sortTable(0)">Category</th>
    <th>Thumbnail</th>
    <  <th onclick="sortTable(2)">File name</th>
    <th onclick="sortTable(3)">Created</th>
    <th onclick="sortTable(4)">Expires</th>
    <th onclick="sortTable(5)">URL</th>
    <th onclick="sortTable(6)">Status</th>
    <th>Submitter</th>
    <th>Comments</th>
    <th>Actions</th>
  </thead>
<tbody>

<?php
$myfile = fopen($dir."files", "r") or die("Unable to open file!");

while($row = fgetcsv($myfile, null, ",")) {
  $category = $row[0];
  $filename = $row[1];
  $expiry_date = explode(" ", $row[2], 2)[0];
  $url = $row[3];
  $fileSize = $row[4];
  $fileDimesnions = $row[5];
  $submitter = $row[6];
  $jiraIssue = $row[7];
  $date_created = explode(" ", $row[8], 2)[0];
  $file_path = $dir.$filename;
  $file_id = explode(".", $filename, 2)[0];

  //set the status
  if($expiry_date >= date("Y-m-d")) { $status = 'Active';} else {$status = "Archive";}

  if(file_exists($file_path) && $expiry_date != null){

  $str .= "<tr>";
  $str .= "<td>".$category."</td>";
  if(!file_exists($file_path)) {
    $str .= "<td></td>";
  } else {
    $str .= "<td class='text-center'><a data-toggle='modal' data-target='#imageModal' id='".$filename."' data-url='".$file_path."'>
            <img src='".$file_path."' width='50px' class='img-rounded'/></a></td>";
  }
  $str .= "<td>".$filename."</td>";
  $str .= "<td>".$date_created."</td>";
  $str .= "<td>".$expiry_date."</td>";
  $str .= "<td><a href='".$url."'>".$url."</a></td>";
  $str .= "<td>".$status."</td>";
  $str .= "<td>".$submitter."</td>";
  $str .= "<td><a href='https://jira.cilt.uct.ac.za/browse/'.$jiraIssue. target='_blank'>".$jiraIssue."</a></td>";
  $str .= "<td>
            <a title='edit file' id='".$file_id."' data-toggle='modal' data-target='#editModal' data-file='".$filename."' data-expiry='".$expiry_date."' data-url='".$url."' data-category='".$category."'>
            <i class='fas fa-pencil-alt'></i></a>&nbsp;<a title='delete file' data-toggle='modal' data-target='#deleteModal' id='".$file_id."' data-file='".$filename."'><i class='fa fa-times'></i></a>&nbsp;";
  if(file_exists($file_path)) {
    $str .= "<a href='".$file_path."' download><i class='fa fa-download'></i></a>";
  }
  
  $str .= "</td></tr>";
}
}
$str .= "</tbody></table>";
echo $str;
fclose($myfile);

?>

<div class="modal fade" id="imageModal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    <img src="" />
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4></h4>
      </div>
      <div class="modal-body text-center">
         <p>Are you sure you want to delete this file?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dangerbtn-sm" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success btn-sm">Delete</button>
      </div>
    </div>
  </div>
</div>

<div id="uploadModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Upload a file</h4>
      </div>
      <div class="modal-body">
      <form>
      <table class="table borderless">
      <tr>
          <td><label>Submitter:</label></td>
          <td><input type="text" class="form-control" id="file_submitter" name="file_submitter"></td>
        </tr>
        <tr>
          <td><label>Category:</label></td>
          <td>
            <select class="form-control" id="file_category" name="file_category">
              <option selected disabled>Select from list</option>
              <option>events</option>
              <option>src</option>
              <option>cet</option>
            </select>
          </td>
        </tr>
        <tr>
          <td><label>File name:</label></td>
          <td><input type="file" class="form-control-file" id="file1" name="file1"></td>
        </tr>
        <tr>
          <td><label>Expiry date:</label></td>
          <td><input type="date" id="file_expiry" name="file_expiry" class="form-control"/></td>
        </tr>
        <tr>
          <td><label>File URL:</label></td>
          <td><input type="url" id="file_url" name="file_url" class="form-control"/></td>
        </tr>
        <tr>
          <td><label>Comments:</label></td>
          <td><textarea id="file_comments" name="file_comments" class="form-control" rows="5"></textarea></td>
        </tr>
      </table>            
    </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success btn-sm">Save</button>
      </div>
    </div>
    </div>
</div>

<div id="editModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
      <form>
      <table class="table borderless">
        <tr>
          <td><label>Submitter:</label></td>
          <td><input type="text" class="form-control" id="file_submitter" name="file_submitter"></td>
        </tr>
        <tr>
          <td><label>Category:</label></td>
          <td>
            <select class="w3-input w3-border form-control" id="file_category" name="file_category">
              <option>events</option>
              <option>src</option>
              <option>cet</option>
            </select>
          </td>
        </tr>
        <tr>
          <td><label>File name:</label></td>
          <td><input type="text" id="file_name" name="file_name" class="w3-input w3-border form-control" disabled/></td>
        </tr>
        <tr>
          <td><label>Expiry date:</label></td>
          <td><input type="date" id="file_expiry" name="file_expiry" class="w3-input w3-border form-control"/></td>
        </tr>
        <tr>
          <td><label>File URL:</label></td>
          <td><input type="text" id="file_url" name="file_url" class="w3-input w3-border form-control"/></td>
        </tr>
        <tr>
          <td><label>Comments:</label></td>
          <td><textarea id="file_comments" name="file_comments" class="w3-input w3-border form-control" rows="5"></textarea></td>
        </tr>
      </table>            
    </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success btn-sm">Save</button>
      </div>
    </div>
    </div>
</div>

<script type="text/javascript" src="https://code.jquery.com/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="js/custom.js"></script>

<?php
$OUTPUT->footer();
