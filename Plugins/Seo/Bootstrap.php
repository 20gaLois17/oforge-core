<?php

namespace Seo;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\TemplateEngine\Core\Services\TemplateRenderService;
use Seo\Controller\Backend\BackendSeoUrlController;
use Seo\Middleware\SeoMiddleware;
use Seo\Models\SeoUrl;
use Seo\Services\SeoService;
use Seo\Services\SeoUrlService;

/**
 * Class Bootstrap
 *
 * @package Messenger
 */
class Bootstrap extends AbstractBootstrap {

    /**
     * Bootstrap constructor.
     */
    public function __construct() {
        $this->endpoints = [
            BackendSeoUrlController::class,
        ];

        $this->models = [
            SeoUrl::class,
        ];

        $this->services = [
            "seo" => SeoService::class,
        ];

    }

    protected $order = 0;

    public function load() {
        if (Oforge()->isAppReady()) {
            Oforge()->App()->add(new SeoMiddleware());
        }

        $urlService = Oforge()->Services()->get("url");

        $seoUrlService = new SeoUrlService($urlService);
        Oforge()->Services()->set("url", $seoUrlService);
    }

    public function activate() {
        /** @var BackendNavigationService $backendNavigationService */
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        $backendNavigationService->add(BackendNavigationService::CONFIG_CONTENT);
        $backendNavigationService->add([
            'name'     => 'backend_seo',
            'order'    => 5,
            'parent'   => BackendNavigationService::KEY_CONTENT,
            'icon'     => 'fa fa-external-link-square',
            'path'     => 'backend_seo',
            'position' => 'sidebar',
        ]);
    }

}
