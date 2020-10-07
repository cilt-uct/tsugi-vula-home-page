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
        $context['scripts'] = [ addSession('js/custom.js')];

        return $app['twig']->render('Home.twig', $context);
    }

    private function getValues($id, $total, $arr) {

        $result = array();
        $cloned = array_replace([], $arr);
        $max = $total;

        for ($x = 0; $x < count($cloned); $x ++) {
        
            $arr[$x]->v = rand(0, $max);
            $max -= $arr[$x]->v;
            $max = $max > 0 ? $max : 0;
        }
        $cloned[count($cloned)-1]->v = $max;

        return array("id" => $id, "results" => $cloned);
    }

    private function getInfo(Request $request, Application $app) {
        $result = $this->getJSON($app, $app['tsugi']->context->launch->ltiRawParameter('context_id','none'));

        // Testing: 
        // $result = $this->getJSON($app, '15179'); // 15179.jpeg

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
            default : {
                $contentType = 'text/plain';
            }
        }

        return new Response( file_get_contents( __DIR__ ."/../../static/". $file), 200, [ 
            'Content-Type' => $contentType
        ]);
    }

}