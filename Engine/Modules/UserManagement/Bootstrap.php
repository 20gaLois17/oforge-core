<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 17.12.2018
 * Time: 09:54
 */

namespace Oforge\Engine\Modules\UserManagement;

use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementAlreadyExistException;
use Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException;
use Oforge\Engine\Modules\Core\Exceptions\ParentNotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Oforge\Engine\Modules\UserManagement\Controller\Backend\ProfileController;
use Oforge\Engine\Modules\UserManagement\Controller\Backend\UserManagementController;
use Oforge\Engine\Modules\UserManagement\Services\BackendUsersCrudService;
use Oforge\Engine\Modules\UserManagement\Services\BackendUserService;

/**
 * Class Bootstrap
 *
 * @package Oforge\Engine\Modules\UserManagement
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->dependencies = [
            \Oforge\Engine\Modules\CRUD\Bootstrap::class,
            \Oforge\Engine\Modules\Auth\Bootstrap::class,
        ];

        $this->endpoints = [
            UserManagementController::class,
            ProfileController::class,
        ];

        $this->services = [
            'backend.user'       => BackendUserService::class,
            'backend.users.crud' => BackendUsersCrudService::class,
        ];
    }

    /**
     * @throws ORMException
     * @throws ParentNotFoundException
     * @throws ServiceNotFoundException
     * @throws ConfigElementAlreadyExistException
     * @throws ConfigOptionKeyNotExistException
     */
    public function install() {
        I18N::translate('user_management', [
            'en' => 'User management',
            'de' => 'Benutzerverwaltung',
        ]);
        /** @var BackendNavigationService $backendNavigationService */
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        $backendNavigationService->add(BackendNavigationService::CONFIG_ADMIN);
        $backendNavigationService->add([
            'name'     => 'user_management',
            'order'    => 100,
            'parent'   => BackendNavigationService::KEY_ADMIN,
            'icon'     => 'fa fa-user',
            'path'     => 'backend_users',
            'position' => 'sidebar',
        ]);
    }

}
