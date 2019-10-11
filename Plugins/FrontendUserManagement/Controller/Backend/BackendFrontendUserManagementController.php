<?php

namespace FrontendUserManagement\Controller\Backend;

use Doctrine\ORM\QueryBuilder;
use FrontendUserManagement\Models\User;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\CRUD\Controller\Backend\BaseCrudController;
use Oforge\Engine\Modules\CRUD\Enum\CrudDataTypes;
use Oforge\Engine\Modules\CRUD\Enum\CrudFilterComparator;
use Oforge\Engine\Modules\CRUD\Enum\CrudFilterType;
use Oforge\Engine\Modules\CRUD\Enum\CrudGroupByOrder;

/**
 * Class CategoryController
 *
 * @package FrontendUserManagement\Controller\Backend\FrontendUserManagement
 * @EndpointClass(path="/backend/frontendusers", name="backend_frontend_user_management", assetScope="Backend")
 */
class BackendFrontendUserManagementController extends BaseCrudController {
    /** @var string $model */
    protected $model = User::class;
    /** @var array $modelProperties */
    protected $modelProperties = [
        [
            'name'  => 'id',
            'type'  => CrudDataTypes::INT,
            'label' => ['key' => 'plugin_frontend_user_management_property_id', 'default' => 'Id'],
            'crud'  => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
        ],
        [
            'name'  => 'email',
            'type'  => CrudDataTypes::EMAIL,
            'label' => ['key' => 'plugin_frontend_user_management_property_email', 'default' => 'Account email'],
            'crud'  => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
        ],
        [
            'name'     => 'contactEmail',
            'type'     => CrudDataTypes::CUSTOM,
            'label'    => ['key' => 'plugin_frontend_user_management_property_contact_email', 'default' => 'Contact email'],
            'crud'     => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderContactEmail.twig',
            ],
        ],
        [
            'name'     => 'image_id',
            'type'     => CrudDataTypes::CUSTOM,
            'lable'    => ['key' => 'plugin_frontend_user_management_property_profile_image', 'default' => 'Profile image'],
            'crud'     => [
                'index'  => 'off',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderProfileImage.twig',
            ],
        ],
        [
            'name'     => 'firstName',
            'type'     => CrudDataTypes::CUSTOM,
            'label'    => ['key' => 'plugin_frontend_user_management_property_first_name', 'default' => 'First name'],
            'crud'     => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderFirstName.twig',
            ],
        ],
        [
            'name'     => 'lastName',
            'type'     => CrudDataTypes::CUSTOM,
            'label'    => ['key' => 'plugin_frontend_user_management_property_last_name', 'default' => 'Last name'],
            'crud'     => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderLastName.twig',
            ],
        ],
        [
            'name'     => 'nickName',
            'type'     => CrudDataTypes::STRING,
            'label'    => ['key' => 'plugin_frontend_user_management_property_nickname', 'default' => 'Nickname'],
            'crud'     => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderNickName.twig',
            ],
        ],
        [
            'name'     => 'phoneNumber',
            'type'     => CrudDataTypes::CUSTOM,
            'label'    => ['key' => 'plugin_frontend_user_management_property_phone_number', 'default' => 'Phone number'],
            'crud'     => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'off',
                'update' => 'off',
                'delete' => 'readonly',
            ],
            'renderer' => [
                'custom' => 'Plugins/FrontendUserManagement/Backend/BackendFrontendUserManagement/CRUD/RenderPhoneNumber.twig',
            ],
        ],
        [
            'name'  => 'active',
            'type'  => CrudDataTypes::BOOL,
            'label' => [
                'key'     => 'active',
                'default' => [
                    'en' => 'Active',
                    'de' => 'Aktiviert',
                ],
            ],
            'crud'  => [
                'index'  => 'editable',
                'view'   => 'readonly',
                'create' => 'editable',
                'update' => 'editable',
                'delete' => 'readonly',
            ],
        ],
    ];
    /**
     * @var array $crudActions Keys of 'add|edit|delete'
     */
    protected $crudActions = [
        'index'  => true,
        'create' => false,
        'view'   => true,
        'update' => false,
        'delete' => true,
    ];

    /** @var array $indexFilter */
    protected $indexFilter = [
        'contactEmail' => [
            'type'              => CrudFilterType::TEXT,
            'label'             => ['key' => 'plugin_frontend_user_management_filter_email', 'default' => 'Search in email'],
            'compare'           => CrudFilterComparator::LIKE,
            'customFilterQuery' => 'customFilterQuery',
        ],
        'lastName'     => [
            'type'              => CrudFilterType::TEXT,
            'label'             => ['key' => 'plugin_frontend_user_management_filter_last_name', 'default' => 'Search in last name'],
            'compare'           => CrudFilterComparator::LIKE,
            'customFilterQuery' => 'customFilterQuery',
        ],
        'nickName'     => [
            'type'              => CrudFilterType::TEXT,
            'label'             => ['key' => 'plugin_frontend_user_management_filter_nickname', 'default' => 'Search in nickname'],
            'compare'           => CrudFilterComparator::LIKE,
            'customFilterQuery' => 'customFilterQuery',
        ],
    ];
    /** @var array $indexOrderBy */
    protected $indexOrderBy = [
        'id' => CrudGroupByOrder::ASC,
    ];

    public function __construct() {
        parent::__construct();
    }

    public function initPermissions() {
        parent::initPermissions();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $queryValues
     */
    protected function customFilterQuery(QueryBuilder $queryBuilder, array $queryValues) {
        $and  = $queryBuilder->expr()->andX();
        $keys = ['contactEmail', 'lastName', 'nickName'];
        foreach ($keys as $key) {
            if (isset($queryValues[$key])) {
                $and->add($queryBuilder->expr()->like('d.' . $key, ':' . $key));
                $queryBuilder->setParameter($key, '%' . $queryValues[$key] . '%');
            }
        }
        if (!empty($and->getParts())) {
            $queryBuilder->leftJoin('e.detail', 'd');
            $queryBuilder->where($and);
        }
    }

}
