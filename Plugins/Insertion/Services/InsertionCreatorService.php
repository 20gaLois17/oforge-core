<?php

namespace Insertion\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FrontendUserManagement\Services\UserService;
use Insertion\Models\AttributeKey;
use Insertion\Models\AttributeValue;
use Insertion\Models\Insertion;
use Insertion\Models\InsertionAttributeValue;
use Insertion\Models\InsertionContent;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Media\Services\MediaService;

class InsertionCreatorService extends AbstractDatabaseAccess {
    public function __construct() {
        parent::__construct([
            'default' => Insertion::class,
        ]);
    }

    public function processPostData($typeId) : ?array {
        if (!isset($_SESSION['insertion' . $typeId])) {
            $_SESSION['insertion' . $typeId] = [];
        }

        if (isset($_POST["current_page"])) {
            $currentPage = $_POST["current_page"];

            $_SESSION['insertion' . $typeId][$currentPage] = $_POST;

            if (isset($_FILES["images"])) {
                /** @var MediaService $configService */
                $mediaService = Oforge()->Services()->get('media');

                if (!isset($_SESSION['insertion' . $typeId][$currentPage]["images"])) {
                    $_SESSION['insertion' . $typeId][$currentPage]["images"] = [];
                }

                $imgs = [];

                foreach ($_SESSION['insertion' . $typeId][$currentPage]["images"] as $image) {
                    if (isset($_POST["images_interactions"])) {
                        if (isset($image["id"]) && isset($_POST["images_interactions"][$image["id"]])
                            && $_POST["images_interactions"][$image["id"]] == "delete") {
                            $mediaService->delete($image["id"]);
                        } else {
                            array_push($imgs, $image);
                        }
                    }
                }

                $_SESSION['insertion' . $typeId][$currentPage]["images"] = $imgs;

                foreach ($_FILES["images"]["error"] as $key => $error) {
                    if ($error == UPLOAD_ERR_OK) {
                        $file = [];

                        foreach ($_FILES["images"] as $k => $v) {
                            $file[$k] = $_FILES["images"][$k][$key];
                        }

                        $media                                                     = $mediaService->add($file);
                        $_SESSION['insertion' . $typeId][$currentPage]["images"][] = $media->toArray();
                    }
                }
            }
        }

        return $_SESSION['insertion' . $typeId];
    }

    public function clearProcessedData($typeId) {
        unset($_SESSION['insertion' . $typeId]);
    }

    public function getProcessedData($typeId) {
        return $_SESSION['insertion' . $typeId];
    }

    public function create(?array $data) {

    }
}
