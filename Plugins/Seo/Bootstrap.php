<?php

namespace Seo;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\TemplateEngine\Core\Services\TemplateRenderService;
use Seo\Controller\Backend\BackendSeoUrlController;
use Seo\Middleware\SeoMiddleware;
use Seo\Models\SeoUrl;
use Seo\Services\SeoService;
use Seo\Twig\SeoExtension;

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
        Oforge()->App()->add(new SeoMiddleware());

        /**
         * @var $templateRenderer TemplateRenderService
         */
        $templateRenderer = Oforge()->Services()->get("template.render");

        $templateRenderer->View()->addExtension(new SeoExtension());
    }

    public function activate() {
        /** @var BackendNavigationService $sidebarNavigation */
        $sidebarNavigation = Oforge()->Services()->get('backend.navigation');
        $sidebarNavigation->put([
            'name'     => 'backend_seo',
            'order'    => 5,
            'parent'   => 'backend_content',
            'icon'     => 'fa fa-external-link-square',
            'path'     => 'backend_seo',
            'position' => 'sidebar',
        ]);
    }

}
