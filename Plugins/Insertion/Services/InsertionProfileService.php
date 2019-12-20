<?php

namespace Insertion\Services;

use FrontendUserManagement\Models\User;
use Insertion\Models\InsertionProfile;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Media\Services\MediaService;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use ReflectionException;

class InsertionProfileService extends AbstractDatabaseAccess {
    public function __construct() {
        parent::__construct([
            'default' => InsertionProfile::class,
        ]);
    }

    /**
     * @param int $user
     *
     * @return InsertionProfile|null
     * @throws ORMException
     */
    public function get(int $user) : ?InsertionProfile {
        /**
         * @var $result InsertionProfile
         */
        $result = $this->repository()->findOneBy(["user" => $user]);

        return $result;
    }

    /**
     * @param int $id
     *
     * @return InsertionProfile|null
     * @throws ORMException
     */
    public function getById($id): ?InsertionProfile {
        /**
         * @var $result InsertionProfile
         */
        $result = $this->repository()->find($id);

        return $result;
    }

    /**
     * @param User $user
     * @param array $params
     *
     * @throws ORMException
     * @throws ServiceNotFoundException
     * @throws ReflectionException
     */
    public function update(User $user, array $params) {
        /**
         * @var $result InsertionProfile
         */
        $result = $this->repository()->findOneBy(["user" => $user]);

        /** @var MediaService $mediaService */
        $mediaService = Oforge()->Services()->get('media');

        $create = $result == null;

        if ($create) {
            $result = new InsertionProfile();
        }

        if (isset($_FILES["background"])) {
            $media = $mediaService->add($_FILES["background"]);
            if ($media != null) {
                $oldId = null;

                if ($result->getBackground() != null) {
                    $oldId = $result->getBackground()->getId();
                }

                $result->setBackground($media);

                if ($oldId != null) {
                    $mediaService->delete($oldId);
                }
            }
        }

        $imprintWebsite = $params["imprint_website"];
        $disallowed = array('http://', 'https://');

        foreach($disallowed as $d) {
            if(strpos($imprintWebsite, $d) === 0) {
                $imprintWebsite = str_replace($d, '', $imprintWebsite);
            }
        }

        $result->fromArray([
            "description"          => $params["description"],
            "user"                 => $user,
            "imprintName"          => $params["imprint_name"],
            "imprintStreet"        => $params["imprint_street"],
            "imprintZipCity"       => $params["imprint_zipCity"],
            "imprintPhone"         => $params["imprint_phone"],
            "imprintEmail"         => $params["imprint_email"],
            "imprintFacebook"      => $params["imprint_facebook"],
            "imprintWebsite"       => $imprintWebsite,
            "imprintCompanyTaxId"  => $params["imprint_company_tax"],
            "imprintCompanyNumber" => $params["imprint_company_number"],
        ]);

        if ($create) {
            $this->entityManager()->create($result);
        } else {
            $this->entityManager()->update($result);
        }
    }
}
