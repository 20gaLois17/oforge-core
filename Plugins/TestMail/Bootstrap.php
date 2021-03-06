<?php

namespace TestMail;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use TestMail\Controller\Backend\SendMailController;
use TestMail\Controller\Backend\ShowMailController;
use TestMail\Controller\Backend\TestMailController;

/**
 * Class Bootstrap
 *
 * @package TestMail
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->endpoints = [
            TestMailController::class,
            ShowMailController::class,
            SendMailController::class,
        ];
    }

    public function activate() {
        I18N::translate('backend_testmail', ['en' => 'TestMail', 'de' => 'TestMail']);
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        $backendNavigationService->add(BackendNavigationService::CONFIG_CONTENT);
        $backendNavigationService->add([
            'name'     => 'backend_testmail',
            'order'    => 5,
            'parent'   => BackendNavigationService::KEY_CONTENT,
            'icon'     => 'fa fa-envelope',
            'path'     => 'backend_testmail',
            'position' => 'sidebar',
        ]);
    }
}
