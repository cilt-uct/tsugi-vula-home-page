<?php

namespace AppBundle;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Core\Settings;
use \Tsugi\Util\Net;

class Home {
    public function getPage(Request $request, Application $app) {
        global $CFG, $PDOX;

        $context = array();
        $context['styles']  = [ addSession('static/custom.css') ];
        $context['scripts'] = [ addSession('static/custom.js') ];
        $context['getUrl'] = addSession('info');
        $context['editFile'] = addSession('edit');
        $context['addFile'] = addSession('add');
        $context['deleteFile'] = addSession('delete');
        $context['getFilteredInfo'] = addSession('filteredInfo');

        // $context['jira'] = addSession('static/grid.svg');

        return $app['twig']->render('Home.twig', $context);
    }

    public function getInfo(Request $request, Application $app) {
        $i = 0; // to drop

        $result = array();
        $myfile = fopen("svn/homepage/files", "r") or die("Unable to open file!");

        while($row = fgetcsv($myfile, null, ",")) {
            $result[$i]['category'] = $row[0];
            $result[$i]['filename'] = $row[1]; 
            $result[$i]['expires'] = date('Y-m-d', strtotime($row[2])); // just the date
            $result[$i]['url'] = $row[3]; 
            $result[$i]['fileSize'] = $row[4]; 
            $result[$i]['fileDimensions'] = $row[5]; 
            $result[$i]['submitter'] =  explode("<", $row[6], 2)[0]; // just the name
            $result[$i]['jiraIssue'] = $row[7]; 
            $result[$i]['created'] =  date('Y-m-d', strtotime($row[8])); // just the date

            $i++; // to drop
            //array_push($result, []);
        }

        fclose($myfile);

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
    }

    public function getFile(Request $request, Application $app, $file = '') {
        if (empty($file)) {
            $app->abort(400);
        }

        switch (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            case 'css' : {
                $contentType = 'text/css';
                break;
            }
            case 'js' : {
                $contentType = 'application/javascript';
                break;
            }
            case 'xml' : {
                $contentType = 'text/xml';
                break;
            }
            case 'svg' : {
                $contentType = 'image/svg+xml';
                break;
            }
            case 'png' : {
                $contentType = 'image/svg+xml';
                break;
            }            
            default : {
                $contentType = 'text/plain';
            }
        }

        return new Response( file_get_contents( __DIR__ ."/../../static/". $file), 200, [ 
            'Content-Type' => $contentType
        ]);
    }
    
    public function editFileInfo(Request $request, Application $app) {
      
        global $CFG, $PDOX;
        $fileInfo = json_decode($request->getContent());
        $filename = htmlentities($fileInfo->filename);

        if($fileInfo) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}vula_files 
                 SET `category` = '$fileInfo->category',
                    `comment` = '$fileInfo->comments',
                    `expires` = '$fileInfo->expiry',
                    `url` = '$fileInfo->url',
                    `file_status` = '$fileInfo->status'
                 WHERE `file_name` = '$filename'"
            );
        }

        return new Response(json_encode($fileInfo), 200, ['Content-Type' => 'application/json']);
    }

    public function addFile(Request $request, Application $app) {
        global $CFG, $PDOX;
        $fileInfo = json_decode($request->getContent());
        $fileToUpload = $fileInfo->fileToUpload;
        $imageFileType = $fileInfo->fileType;
        $_name = $fileInfo->filename;
        $target_dir = "svn/homepage/";
        $fileType = explode('/', $imageFileType, 2)[1];
        $todays_date = date("Y-m-d h:i:s");

        // Valid file extensions
        $extensions_arr = array("jpg","jpeg","png","gif");

        // Check extension
        if( in_array($fileType,$extensions_arr)) {
            $image = $fileInfo->imageBase64;
        }


        if($fileInfo) {
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}vula_files 
                 (`category`, `file_name`, `expires`, `url`, `file_size`, `file_dimensions`, `jira_issue`, `comment`, `image_file`)
                 VALUES ( '$fileInfo->category', 
                           '$fileInfo->filename', 
                           '$fileInfo->expiry', 
                           '$fileInfo->url', 
                           '$fileInfo->file_size', 
                           '$fileInfo->file_dimensions',
                           '$fileInfo->jira_issue', 
                           '$todays_date',
                           '$fileInfo->comments',
                           '{$image}'
                        )"
            );

            move_uploaded_file($_name,$target_dir.$_name);
        }

        return new Response(json_encode($fileInfo), 200, ['Content-Type' => 'application/json']);
    }

    public function deleteFile(Request $request, Application $app) {
        global $CFG, $PDOX;
        $fileInfo = json_decode($request->getContent());
        $filename = htmlentities($fileInfo->filename);
        $fileNotes = htmlentities($fileInfo->comment);

        if($fileInfo) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}vula_files 
                SET `file_status`='Archive',`comment`='$fileInfo->comment'
                WHERE `file_name` = '$fileInfo->filename'"
            );
        }
    }

    public function getFilteredData(Request $request, Application $app) {

        global $CFG, $PDOX;
        $result = array();
        $where = "";
        $and = "";
        $count_query = "SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files";


        //1. get posted fields : search field, active status, active category
        $filterInfo = json_decode($request->getContent());
        $search_field = $filterInfo->searchField;
        $active_status = $filterInfo->active_status;
        $active_category = $filterInfo->active_category; 
        $total_records_per_page = 20;
       

        //2. get counts per status
        $active_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` >= CURDATE()");
        $total_active = $active_stmt->fetchColumn();

        $inactive_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` < CURDATE()");
        $total_inactive = $inactive_stmt->fetchColumn();

        $archive_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `file_status` = 'Archive'");
        $total_archive = $archive_stmt->fetchColumn();

        $total_records_stmt = $PDOX->queryDie($count_query);
        $total_records = $total_records_stmt->fetchColumn();

       
        //3. filter records by search input
        if($search_field != "") {
            $active_status = 'all';
            $active_category = 'all';
            $where = " WHERE `expires` >= CURDATE() AND (`file_name` LIKE '$search_field%' OR `created` LIKE '$search_field%' 
            OR `url` LIKE '$search_field%' OR `submitter` LIKE '$search_field%' OR `jira_issue` LIKE '$search_field%' OR `expires` LIKE '$search_field%')";

            $stmt = $PDOX->queryDie(
                "SELECT * FROM {$CFG->dbprefix}vula_files $where $and order by `expires` DESC"
            );
            $rows = $stmt->fetchAll();

            $total_stmt = $PDOX->queryDie(
                "SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files $where order by `expires` DESC"
            );
            $total_rows = $total_stmt->fetchColumn();

            $active_events_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files  $where  AND `category` = 'events'");
            $active_events = $active_events_stmt->fetchColumn();

            $active_cet_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files $where  AND `category` = 'cet'");
            $active_cet = $active_cet_stmt->fetchColumn();

            $active_src_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files  $where  AND `category` = 'src'");
            $active_src = $active_src_stmt->fetchColumn();

            foreach($rows as $row) {
                $result[] = array(
                    'category' => $row['category'],
                    'filename' => $row['file_name'],
                    'expires' => explode(" ", $row['expires'], 2)[0],
                    'url' => $row['url'],
                    'fileSize' => $row['file_size'],
                    'fileDimensions' => $row['file_dimensions'],
                    'fileStatus' => $row['file_status'],
                    'submitter' =>  explode("<", $row['submitter'], 2)[0],
                    'submitterEmail' =>  rtrim(explode("<", $row['submitter'], 2)[1], ">"),
                    'jiraIssue' => $row['jira_issue'],
                    'created' => explode(" ", $row['created'], 2)[0],
                    'comments' => $row['comment'],
                    'imgFile' => $row['image_file'],
                    'total_records'=>$total_rows, 'total_active'=>$total_rows, 'total_archive'=>0, 'total_inactive'=>0,
                    'active_events'=>$active_events,'active_cet'=>$active_cet, 'active_src'=>$active_src, 'active_all'=>$total_rows,
                   
                );
            }

        } else {
            //4. filter records by status and category
            if($active_status == 'active') {

                $where = " WHERE `expires` >= CURDATE()";

                if($active_category == 'all') {
                    $and = "";
                } else {
                    $and = " AND `category` = '$active_category'";
                } 

                $active_events_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` >= CURDATE() AND `category` = 'events'");
                $active_events = $active_events_stmt->fetchColumn();

                $active_cet_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` >= CURDATE() AND `category` = 'cet'");
                $active_cet = $active_cet_stmt->fetchColumn();

                $active_src_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` >= CURDATE() AND `category` = 'src'");
                $active_src = $active_src_stmt->fetchColumn();

                $active_all_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` >= CURDATE()");
                $active_all = $active_all_stmt->fetchColumn();

            } else if($active_status == 'inactive') {

                $where = " WHERE `expires` < CURDATE()";

                if($active_category == 'all') {
                    $and = "";
                } else {
                    $and = " AND `category` = '$active_category'";
                } 

                $inactive_events_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` < CURDATE() AND `category` = 'events'");
                $inactive_events = $inactive_events_stmt->fetchColumn();

                $inactive_cet_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` < CURDATE() AND `category` = 'cet'");
                $inactive_cet = $inactive_cet_stmt->fetchColumn();

                $inactive_src_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` < CURDATE() AND `category` = 'src'");
                $inactive_src = $inactive_src_stmt->fetchColumn();

                $inactive_all_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `expires` < CURDATE()");
                $inactive_all = $inactive_all_stmt->fetchColumn();

            } else if($active_status == 'archive') {

                $where = " WHERE `file_status` = 'Archive'";

                if($active_category == 'all') {
                    $and = "";
                } else {
                    $and = " AND `category` = '$active_category'";
                } 

                $archive_events_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `file_status` = 'Archive' AND `category` = 'events'");
                $archive_events = $archive_events_stmt->fetchColumn();

                $archive_cet_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `file_status` = 'Archive' AND `category` = 'cet'");
                $archive_cet = $archive_cet_stmt->fetchColumn();

                $archive_src_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `file_status` = 'Archive' AND `category` = 'src'");
                $archive_src = $archive_src_stmt->fetchColumn();

                $archive_all_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `file_status` = 'Archive'");
                $archive_all = $archive_all_stmt->fetchColumn();

            } else if($active_status == 'all') {

                $where = "";

                if($active_category == 'all') {
                    $and = "";
                } else {
                    $and = " AND `category` = '$active_category'";
                } 


                $all_events_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `category` = 'events'");
                $all_events = $all_events_stmt->fetchColumn();

                $all_cet_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `category` = 'cet'");
                $all_cet = $all_cet_stmt->fetchColumn();

                $all_src_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files WHERE `category` = 'src'");
                $all_src = $all_src_stmt->fetchColumn();

                $all_all_stmt = $PDOX->queryDie("SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files");
                $all_all = $all_all_stmt->fetchColumn();

            }

            //2. get filtered data
            $stmt = $PDOX->queryDie(
                "SELECT * FROM {$CFG->dbprefix}vula_files $where $and order by `expires` DESC"
            );
            $rows = $stmt->fetchAll();

            foreach($rows as $row) {
                $result[] = array(
                    'category' => $row['category'],
                    'filename' => $row['file_name'],
                    'expires' => explode(" ", $row['expires'], 2)[0],
                    'url' => $row['url'],
                    'fileSize' => $row['file_size'],
                    'fileDimensions' => $row['file_dimensions'],
                    'fileStatus' => $row['file_status'],
                    'submitter' =>  explode("<", $row['submitter'], 2)[0],
                    'submitterEmail' =>  rtrim(explode("<", $row['submitter'], 2)[1], ">"),
                    'jiraIssue' => $row['jira_issue'],
                    'created' => explode(" ", $row['created'], 2)[0],
                    'comments' => $row['comment'],
                    'imgFile' => $row['image_file'],
                    'total_records'=>$total_records, 'total_active'=>$total_active, 'total_inactive'=>$total_inactive, 'total_archive'=>$total_archive,
                    'active_events'=>$active_events,'active_cet'=>$active_cet, 'active_src'=>$active_src, 'active_all'=>$active_all,
                    'inactive_events'=>$inactive_events,'inactive_cet'=>$inactive_cet, 'inactive_src'=>$inactive_src, 'inactive_all'=>$inactive_all,
                    'archive_events'=>$archive_events,'archive_cet'=>$archive_cet, 'archive_src'=>$archive_src, 'archive_all'=>$archive_all,
                    'all_events'=>$all_events,'all_cet'=>$all_cet, 'all_src'=>$all_src, 'all_all'=>$all_all
                );
            }
       }

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);

    } 
}