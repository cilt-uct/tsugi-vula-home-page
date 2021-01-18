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
        $context['getUserProfile'] = addSession('getUser');

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
        return false;
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
        $date = new \DateTime();
        $filename = htmlentities($fileInfo->filename);
        $expiry = $fileInfo->expiry;
        $fileNotes = htmlentities($fileInfo->comment);

        if($fileInfo) {
            $set = "";
            if($expiry < date("Y-m-d")) {
                $yesterday = date("Y-m-d", strtotime( '-1 days' ) );
                $set =" SET `file_status`='Archive', `expires` = '".$yesterday."',`comment`='$fileNotes'";
            } else {
                $set ="SET `file_status`='Archive',`comment`='$fileNotes'";
            }
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}vula_files" .$set." WHERE `file_name` = '$filename'"
            );
        }
        return false;
    }

    public function fetchUserProfile(Request $request, Application $app) {
        global $CFG, $PDOX;
        $currentUserInfo = json_decode($request->getContent());
        $userid = $currentUserInfo->userid;

       
        $stmt = $PDOX->queryDie(
            "SELECT * FROM {$CFG->dbprefix}vula_authorization WHERE `username` = '$userid'"         
        );
        $rows = $stmt->fetchAll();

        $result = array();
        foreach($rows as $row) {
            $result[] = array(
                'username' => $row['username'],
                'name' => $row['name'],
                'email' => $row['email_address'],
                'user_role' => $row['role']
            );
        }

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
    }

    public function getFilteredData(Request $request, Application $app) {

        global $CFG, $PDOX;
        $result = array();
        $filterInfo = json_decode($request->getContent());
        $search_field = $filterInfo->searchField;
        $active_status = $filterInfo->active_status;
        $active_category = $filterInfo->active_category;
        $page_no = $filterInfo->pageno;
        $limit = 20;
        $page = 1;
        $where = "";
        $and = "";

        if($page_no > 1) {
            $start = (($page_no - 1) * $limit);
            $page = $page_no;
        } else {
            $start = 0;
        }

        $query = "SELECT * FROM {$CFG->dbprefix}vula_files";
        $count_query = "SELECT COUNT(*) FROM {$CFG->dbprefix}vula_files";


        if($search_field != '') {
            $search_query = ' (`file_name` LIKE "'.$search_field.'%"
                OR `created` LIKE "'.$search_field.'%" 
                OR `url` LIKE "'.$search_field.'%"
                OR `submitter` LIKE "'.$search_field.'%" 
                OR `jira_issue` LIKE "'.$search_field.'%" 
                OR `expires` LIKE "'.$search_field.'%")
            ';

            if($active_category != 'all') {
                $and = " AND `category` = '$active_category'";
            }

            $active_stmt = $PDOX->queryDie($count_query. " WHERE `expires` >= CURDATE() AND" .$search_query);
            $total_active = $active_stmt->fetchColumn();

            $inactive_stmt = $PDOX->queryDie($count_query. " WHERE `expires` < CURDATE() AND" .$search_query);
            $total_inactive = $inactive_stmt->fetchColumn();

            $archive_stmt = $PDOX->queryDie($count_query. " WHERE `file_status` = 'Archive' AND" .$search_query);
            $total_archive = $archive_stmt->fetchColumn();

            $total_records_stmt = $PDOX->queryDie($count_query. " WHERE " .$search_query);
            $total_records = $total_records_stmt->fetchColumn();

            if($active_status != "all") {
                if($active_status == 'active') {

                    $where = ' WHERE `expires` >= CURDATE() AND'.$search_query;

                    $active_events_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'events'");
                    $active_events = $active_events_stmt->fetchColumn();

                    $active_cet_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'cet'");
                    $active_cet = $active_cet_stmt->fetchColumn();

                    $active_src_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'src'");
                    $active_src = $active_src_stmt->fetchColumn();

                    $active_all_stmt = $PDOX->queryDie($count_query. $where);
                    $active_all = $active_all_stmt->fetchColumn();

                } else if($active_status == 'inactive') {
                    
                    $where = ' WHERE `expires` < CURDATE() AND `file_status` != "Archive" AND'.$search_query;

                    $inactive_events_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'events'");
                    $inactive_events = $inactive_events_stmt->fetchColumn();

                    $inactive_cet_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'cet'");
                    $inactive_cet = $inactive_cet_stmt->fetchColumn();

                    $inactive_src_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'src'");
                    $inactive_src = $inactive_src_stmt->fetchColumn();

                    $inactive_all_stmt = $PDOX->queryDie($count_query. $where);
                    $inactive_all = $inactive_all_stmt->fetchColumn();

                } else if($active_status == 'archive') {

                    $where = ' WHERE `file_status` = "Archive" AND ' .$search_query;

                    $archive_events_stmt = $PDOX->queryDie($count_query. $where." AND `category` = 'events'");
                    $archive_events = $archive_events_stmt->fetchColumn();
    
                    $archive_cet_stmt = $PDOX->queryDie($count_query. $where." AND `category` = 'cet'");
                    $archive_cet = $archive_cet_stmt->fetchColumn();
    
                    $archive_src_stmt = $PDOX->queryDie($count_query. $where." AND `category` = 'src'");
                    $archive_src = $archive_src_stmt->fetchColumn();
    
                    $archive_all_stmt = $PDOX->queryDie($count_query. $where);
                    $archive_all = $archive_all_stmt->fetchColumn();
                }
            } else {

                $all_events_stmt = $PDOX->queryDie($count_query. " WHERE" .$search_query. " AND `category` = 'events'");
                $all_events = $all_events_stmt->fetchColumn();

                $all_cet_stmt = $PDOX->queryDie($count_query. " WHERE" .$search_query. " AND `category` = 'cet'");
                $all_cet = $all_cet_stmt->fetchColumn();

                $all_src_stmt = $PDOX->queryDie($count_query. " WHERE" .$search_query. " AND `category` = 'src'");
                $all_src = $all_src_stmt->fetchColumn();

                $all_all_stmt = $PDOX->queryDie($count_query. " WHERE" .$search_query);
                $all_all = $all_all_stmt->fetchColumn();
            }
        } else {
            $active_stmt = $PDOX->queryDie($count_query. " WHERE `expires` >= CURDATE() AND `file_status` != 'Archive'");
            $total_active = $active_stmt->fetchColumn();
    
            $inactive_stmt = $PDOX->queryDie($count_query. " WHERE `expires` < CURDATE() AND `file_status` != 'Archive'");
            $total_inactive = $inactive_stmt->fetchColumn();
    
            $archive_stmt = $PDOX->queryDie($count_query. " WHERE `file_status` = 'Archive'");
            $total_archive = $archive_stmt->fetchColumn();
    
            $total_records_stmt = $PDOX->queryDie($count_query);
            $total_records = $total_records_stmt->fetchColumn();

            if($active_status == 'active') {

                $where = " WHERE `expires` >= CURDATE() AND `file_status` != 'Archive'";
            
                if($active_category != 'all') {
                    $and = " AND `category` = '$active_category'";
                }

                $active_events_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'events'");
                $active_events = $active_events_stmt->fetchColumn();

                $active_cet_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'cet'");
                $active_cet = $active_cet_stmt->fetchColumn();

                $active_src_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'src'");
                $active_src = $active_src_stmt->fetchColumn();

                $active_all_stmt = $PDOX->queryDie($count_query. $where);
                $active_all = $active_all_stmt->fetchColumn();

            } else if($active_status == 'inactive') {

                $where = " WHERE `expires` < CURDATE() AND `file_status` != 'Archive'";

                if($active_category != 'all') {
                    $and = " AND `category` = '$active_category'";
                } 

                $inactive_events_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'events'");
                $inactive_events = $inactive_events_stmt->fetchColumn();

                $inactive_cet_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'cet'");
                $inactive_cet = $inactive_cet_stmt->fetchColumn();

                $inactive_src_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'src'");
                $inactive_src = $inactive_src_stmt->fetchColumn();

                $inactive_all_stmt = $PDOX->queryDie($count_query. $where);
                $inactive_all = $inactive_all_stmt->fetchColumn();

            } else if($active_status == 'archive') {

                $where = " WHERE `file_status` = 'Archive'";

                if($active_category != 'all') {
                    $and = " AND `category` = '$active_category'";
                } 

                $archive_events_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'events'");
                $archive_events = $archive_events_stmt->fetchColumn();

                $archive_cet_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'cet'");
                $archive_cet = $archive_cet_stmt->fetchColumn();

                $archive_src_stmt = $PDOX->queryDie($count_query. $where. " AND `category` = 'src'");
                $archive_src = $archive_src_stmt->fetchColumn();

                $archive_all_stmt = $PDOX->queryDie($count_query. $where);
                $archive_all = $archive_all_stmt->fetchColumn();

            } else if($active_status == 'all') {

                if($active_category != 'all') {
                    $where = " WHERE `category` = '$active_category'";
                } 

                $all_events_stmt = $PDOX->queryDie($count_query. " WHERE `category` = 'events'");
                $all_events = $all_events_stmt->fetchColumn();

                $all_cet_stmt = $PDOX->queryDie($count_query. " WHERE `category` = 'cet'");
                $all_cet = $all_cet_stmt->fetchColumn();

                $all_src_stmt = $PDOX->queryDie($count_query. " WHERE `category` = 'src'");
                $all_src = $all_src_stmt->fetchColumn();

                $all_all_stmt = $PDOX->queryDie($count_query);
                $all_all = $all_all_stmt->fetchColumn();
            }
        }

        
        $query .= $where. ' ' .$and.' ORDER BY `expires` DESC ';
        $filter_query = $query . 'LIMIT '.$start.', '.$limit.'';

        $filter_stmt = $PDOX->queryDie($query);
        $total_data = $filter_stmt->rowCount();

        $total_stmt = $PDOX->queryDie($filter_query);

        $result_rows = $total_stmt->fetchAll();
        $total_filter_data = $total_stmt->rowCount();

        $total_links = ceil($total_data/$limit);
        $previous_link = '';
        $next_link = '';
        $page_link = '';

        if($total_links > 4) {
            if($page < 5) {
                for($count = 1; $count <= 5; $count++) {
                    $page_array[] = $count;
                }
                $page_array[] = '...';
                $page_array[] = $total_links;
            }
            else  {
                $end_limit = $total_links - 5;
                if($page > $end_limit) {
                    $page_array[] = 1;
                    $page_array[] = '...';
                    for($count = $end_limit; $count <= $total_links; $count++) {
                        $page_array[] = $count;
                    }
                } else {
                    $page_array[] = 1;
                    $page_array[] = '...';
                    for($count = $page - 1; $count <= $page + 1; $count++) {
                        $page_array[] = $count;
                    }
                    $page_array[] = '...';
                    $page_array[] = $total_links;
                }
            }
        } else {
            for($count = 1; $count <= $total_links; $count++) {
                $page_array[] = $count;
            }
        }

        $pagination_result = "";
        for($count = 0; $count < count($page_array); $count++) {
            if($page == $page_array[$count]) {
                $page_link .= '
                    <li class="page-item active">
                    <a class="page-link" href="#" data-page_number="'.$page_array[$count].'" 
                    data-active_status="'.$active_status.'" data-active_category="'.$active_category.'">'.$page_array[$count].' <span class="sr-only">(current)</span></a>
                    </li>
                ';
                $previous_id = $page_array[$count] - 1;
                if($previous_id > 0) {
                    $previous_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$previous_id.'" 
                    data-active_status="'.$active_status.'" data-active_category="'.$active_category.'">Previous</a></li>';
                } else {
                    $previous_link = '
                        <li class="page-item disabled">
                            <a class="page-link" href="#">Previous</a>
                        </li>
                    ';
                }

                $next_id = $page_array[$count] + 1;
                if($next_id > $total_links) {
                    $next_link = '
                        <li class="page-item disabled">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    ';
                } else {
                    $next_link = '<li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$next_id.'" 
                    data-active_status="'.$active_status.'" data-active_category="'.$active_category.'">Next</a></li>';
                }
            } else {
                if($page_array[$count] == '...') {
                    $page_link .= '
                        <li class="page-item disabled">
                            <a>...</a>
                        </li>
                    ';
                } else {
                    $page_link .= '
                        <li class="page-item"><a class="page-link" href="javascript:void(0)" data-page_number="'.$page_array[$count].'" 
                        data-active_status="'.$active_status.'" data-active_category="'.$active_category.'">'.$page_array[$count].'</a></li>
                    ';
                }
            }
        }

        $pagination_result .= $previous_link . $page_link . $next_link;

        foreach($result_rows as $row) {
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
                'active_status' => $active_status,
                'total_records'=>$total_records, 'total_active'=>$total_active, 'total_inactive'=>$total_inactive, 'total_archive'=>$total_archive,
                'active_events'=>$active_events,'active_cet'=>$active_cet, 'active_src'=>$active_src, 'active_all'=>$active_all,
                'inactive_events'=>$inactive_events,'inactive_cet'=>$inactive_cet, 'inactive_src'=>$inactive_src, 'inactive_all'=>$inactive_all,
                'archive_events'=>$archive_events,'archive_cet'=>$archive_cet, 'archive_src'=>$archive_src, 'archive_all'=>$archive_all,
                'all_events'=>$all_events,'all_cet'=>$all_cet, 'all_src'=>$all_src, 'all_all'=>$all_all,
                'pagination'=>$pagination_result
            );

        }

        return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
    }
}