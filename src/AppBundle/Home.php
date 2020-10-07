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
}