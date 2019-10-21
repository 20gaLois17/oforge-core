<?php

namespace Oforge\Engine\Modules\CRUD;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\CRUD\Services\GenericCrudService;
use Oforge\Engine\Modules\CRUD\Twig\CrudExtension;
use Oforge\Engine\Modules\TemplateEngine\Core\Services\TemplateRenderService;

// use Oforge\Engine\Modules\CRUD\Controller\Backend\CRUD\Test\ReadController;
// use Oforge\Engine\Modules\CRUD\Controller\Backend\CRUD\Test\WriteController;
// use Oforge\Engine\Modules\CRUD\Models\CrudTest;

/**
 * Class Bootstrap
 *
 * @package Oforge\Engine\Modules\CRUD
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->endpoints = [
            // '/backend/crudtest/read'  => ReadController::getBootstrapEndpointsArray(),
            // '/backend/crudtest/write' => WriteController::getBootstrapEndpointsArray(),
        ];
        $this->services  = [
            'crud' => GenericCrudService::class,
        ];
        $this->models    = [// CrudTest::class,
        ];
    }

    /**
     */
    public function install() {
        /** @var BackendNavigationService $backendNavigationService */
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        // $backendNavigationService->add(BackendNavigationService::CONFIG_ADMIN);
        // $backendNavigationService->add([
        //     'name'     => 'backend_crudtest',
        //     'order'    => 100,
        //     'parent'   => BackendNavigationService::KEY_ADMIN,
        //     'icon'     => 'glyphicon glyphicon glyphicon-th',
        //     'position' => 'sidebar',
        // ]);
        // $backendNavigationService->add([
        //     'name'     => 'backend_crudtest_read',
        //     'order'    => 1,
        //     'parent'   => 'backend_crudtest',
        //     'icon'     => 'fa fa-search',
        //     'path'     => 'backend_crudtest_read',
        //     'position' => 'sidebar',
        // ]);
        // $backendNavigationService->add([
        //     'name'     => 'backend_crudtest_write',
        //     'order'    => 2,
        //     'parent'   => 'backend_crudtest',
        //     'icon'     => 'fa fa-pencil',
        //     'path'     => 'backend_crudtest_write',
        //     'position' => 'sidebar',
        // ]);
    }

    public function load() {
        /** @var TemplateRenderService $templateRenderer */
        $templateRenderer = Oforge()->Services()->get('template.render');

        $templateRenderer->View()->addExtension(new CrudExtension());
    }

}
