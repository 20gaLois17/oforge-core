<?php

namespace Helpdesk\Controller\Frontend;

use FrontendUserManagement\Abstracts\SecureFrontendController;
use Slim\Http\Request;
use Slim\Http\Response;

class FrontendHelpdeskController extends SecureFrontendController {
    public function indexAction(Request $request, Response $response) {
        // TODO
    }

    public function initPermissions() {
        parent::initPermissions(); // TODO: Change the autogenerated stub
    }
}