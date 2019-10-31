<?php

namespace FrontendUserManagement\Services;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Exception;
use FrontendUserManagement\Models\User;
use FrontendUserManagement\Models\UserDetail;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Media\Services\MediaService;

/**
 * Class UserDetailsService
 *
 * @package FrontendUserManagement\Services
 */
class UserDetailsService extends AbstractDatabaseAccess {

    public function __construct() {
        parent::__construct(["default" => UserDetail::class, "user" => User::class]);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function save(array $data) {
        try {
            $detail = $this->get($data['userId']);

            if ($detail == null) {
                $detail = UserDetail::create($data);
                $user   = $this->repository("user")->find($data['userId']);
                $detail->setUser($user);
                $this->entityManager()->create($detail);
            } else {
                $detail->fromArray($data);
                $this->entityManager()->update($detail);
            }

            return true;

        } catch (Exception $ex) {
            Oforge()->Logger()->get()->addError('Could not persist / flush userDetails', ['msg' => $ex->getMessage(), 'stack' => $ex->getTraceAsString()]);
        }

        return false;
    }

    /**
     * @param $userID
     *
     * @return UserDetail|null
     * @throws ORMException
     */
    public function get($userID) : ?UserDetail {
        /** @var UserDetail|null $detail */
        $detail = $this->repository()->findOneBy(['user' => $userID]);

        return $detail;
    }

    public function deleteImage(User $user) {


    }

    /**
     * @param User $user
     * @param $file
     *
     * @return User
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function updateImage(User $user, $file) {
        /** @var MediaService $mediaService */
        $mediaService = Oforge()->Services()->get('media');
        $media        = $mediaService->add($file);

        if ($media != null && $user != null) {
            if ($user->getDetail() != null) {
                $oldId = null;

                if ($user->getDetail()->getImage() != null) {
                    $oldId = $user->getDetail()->getImage()->getId();
                }

                $user->getDetail()->setImage($media);
                $this->entityManager()->update($user->getDetail());

                if ($oldId != null) {
                    $mediaService->delete($oldId);
                }
            } else {
                $detail = new UserDetail();
                $detail->setUser($user);
                $detail->setImage($media);

                $this->entityManager()->create($detail);
                $user->setDetail($detail);
            }
        }

        return $user;
    }

    /**
     * @return string
     * @throws ORMException
     * @throws DBALException
     */
    public function generateNickname() {
        do {
            $query     = "SELECT n.value FROM frontend_user_nickname_generator as n WHERE sort_order = 1 ORDER BY RAND() LIMIT 1";
            $sqlResult = $this->entityManager()->getEntityManager()->getConnection()->executeQuery($query);
            $attribute = $sqlResult->fetchAll()[0]['value'];

            $query     = "SELECT n.value FROM frontend_user_nickname_generator as n WHERE sort_order = 2 ORDER BY RAND() LIMIT 1";
            $sqlResult = $this->entityManager()->getEntityManager()->getConnection()->executeQuery($query);
            $race      = $sqlResult->fetchAll()[0]['value'];

            $nickname     = $attribute . $race . rand(0, 9) . rand(0, 9) . rand(0, 9);
            $existingUser = $this->repository()->findOneBy(['nickName' => $nickname]);
        } while (!is_null($existingUser));

        return $nickname;
    }
}
