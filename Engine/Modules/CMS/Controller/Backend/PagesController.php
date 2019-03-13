<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 07.01.2019
 * Time: 15:57
 */

namespace Oforge\Engine\Modules\CMS\Controller\Backend;

use Slim\Http\Request;
use Slim\Http\Response;
use Oforge\Engine\Modules\Core\Abstracts\AbstractController;

class PagesController extends AbstractController {
    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function indexAction(Request $request, Response $response) {
        $pagesControllerService = OForge()->Services()->get("pages.controller.service");
        
        switch ($_POST["cms_form"])
        {
            case "cms_page_jstree_form":
                $data = $pagesControllerService->editPageData($_POST);
                break;
            case "cms_page_builder_form":
            default:
                if ($pagesControllerService->checkForValidPagePath($_POST))
                {
                    $data = $pagesControllerService->editContentData($_POST);
                }
                else
                {
                    $data = $pagesControllerService->editPagePathData($_POST);
                }
                break;
        }
        
        Oforge()->View()->assign($data);
    }
}