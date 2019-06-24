<?php

namespace Insertion\Services;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Insertion\Enum\AttributeType;
use Insertion\Models\AttributeKey;
use Insertion\Models\Insertion;
use Insertion\Models\InsertionType;
use Insertion\Models\InsertionTypeAttribute;
use Insertion\Models\InsertionTypeGroup;
use Insertion\Models\InsertionZipCoordinates;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Annotation\Cache\Cache;
use Oforge\Engine\Modules\Core\Annotation\Cache\CacheInvalidation;
use Oforge\Engine\Modules\Core\Helper\Statics;

/**
 * Class InsertionListService
 * @Cache()
 *
 * @package Insertion\Services
 */
class InsertionListService extends AbstractDatabaseAccess {
    public function __construct() {
        parent::__construct([
            'default'                => Insertion::class,
            'type'                   => InsertionType::class,
            'insertionTypeAttribute' => InsertionTypeAttribute::class,
            'key'                    => AttributeKey::class,
            'group'                  => InsertionTypeGroup::class,
        ]);
    }

    /**
     * @param $typeId
     * @param $params
     *
     * @Cache(slot="insertion", duration="2D")
     * @return array|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */

    public function search($typeId, $params) : ?array {
        $page     = isset($params["page"]) ? $params["page"] : 1;
        $pageSize = 10;

        $attributeKeys = array_keys($_GET);
        $keys          = $this->repository("key")->findBy(["name" => $attributeKeys]);

        $result = ["filter" => [], "query" => [], 'order' => $_GET["order"]];

        $queryBuilder = $this->entityManager()->createQueryBuilder()#
                             ->select('i')->from(Insertion::class, "i")#
                             ->where("i.insertionType = :type and i.active = true and i.moderation = true");
        $queryBuilder->setParameter("type", $typeId);

        if (isset($_GET["zip"]) && isset($_GET["zip_range"]) && !empty($_GET["zip"]) && $_GET["zip_range"]) {
            $coordinates = Oforge()->Services()->get("insertion.zip")->get($_GET["zip"]);
            if ($coordinates != null) {
                $queryBuilder->leftJoin("i.contact", "c");
                $queryBuilder->leftJoin(InsertionZipCoordinates::class, "zip", \Doctrine\ORM\Query\Expr\Join::WITH, 'zip.zip = c.zip');
                $queryBuilder->andWhere("zip.country = :country and ST_Distance_Sphere(POINT(zip.lng, zip.lat), POINT(:lng,:lat)) / 1000 < :zip_distance");
                $queryBuilder->setParameter("country", 'germany');
                $queryBuilder->setParameter("lat", $coordinates->getLat());
                $queryBuilder->setParameter("lng", $coordinates->getLng());
                $queryBuilder->setParameter("zip_distance", $_GET["zip_range"]);
            } else {
            }
        }

        $keyCount       = 1;
        $attributeCount = 1;
        if (sizeof($result) > 0) {
            foreach ($keys as $attributeKey) {
                if (isset($_GET[$attributeKey->getName()])) {
                    $value                                      = $_GET[$attributeKey->getName()];
                    $result['filter'][$attributeKey->getName()] = $value;

                    if (is_array($value)) { //should always be a multi selection or a range component

                        switch ($attributeKey->getFilterType()) {
                            case AttributeType::RANGE:
                                if (sizeof($value) == 2) {
                                    $queryBuilder->leftJoin("i.values", "v$attributeCount");

                                    $query = "v$attributeCount.attributeKey = ?" . $keyCount . " and v$attributeCount.value between ?" . ($keyCount + 1)
                                             . " and " . ($keyCount + 2);

                                    $queryBuilder->andWhere($query);

                                    $queryBuilder->setParameter($keyCount++, $attributeKey->getId());

                                    $min = $value[0];
                                    $max = $value[1];

                                    if ($min > $max) {
                                        $t   = $min;
                                        $min = $max;
                                        $max = $t;
                                    }

                                    $queryBuilder->setParameter($keyCount++, $min);
                                    $queryBuilder->setParameter($keyCount++, $max);

                                    $attributeCount++;
                                }
                                break;
                            default:
                                $firstV = true;
                                foreach ($value as $v) {
                                    $queryBuilder->leftJoin("i.values", "v$attributeCount");

                                    $query = "v$attributeCount.attributeKey = ?" . $keyCount . " and v$attributeCount.value = ?" . ($keyCount + 1);

                                    if ($firstV) {
                                        $firstV = false;
                                        $queryBuilder->andWhere($query);

                                    } else {
                                        $queryBuilder->orWhere($query);
                                    }

                                    $queryBuilder->setParameter($keyCount++, $attributeKey->getId());
                                    $queryBuilder->setParameter($keyCount++, $v);

                                    $attributeCount++;
                                }

                                break;

                        }

                    } else {
                        $queryBuilder->leftJoin("i.values", "v$attributeCount");

                        $queryBuilder->andWhere("v$attributeCount.attributeKey = ?" . $keyCount . " and v$attributeCount.value = ?" . ($keyCount + 1) . "");

                        $queryBuilder->setParameter($keyCount++, $attributeKey->getId());
                        $queryBuilder->setParameter($keyCount++, $value);

                        $attributeCount++;
                    }
                }
            }
        }

        if (isset($_GET["price"]) && is_array($_GET["price"]) && sizeof($_GET["price"]) == 2) {
            $query = "i.price between ?" . ($keyCount) . " and ?" . ($keyCount + 1);

            $queryBuilder->andWhere($query);

            $min = $_GET["price"][0];
            $max = $_GET["price"][1];

            if ($min > $max) {
                $t   = $min;
                $min = $max;
                $max = $t;
            }

            $queryBuilder->setParameter($keyCount++, $min);
            $queryBuilder->setParameter($keyCount++, $max);

            $result['filter']["price"] = [$min, $max];
        }

        if (isset($_GET["order"])) {
            switch ($_GET["order"]) {
                case 'price_asc':
                    $queryBuilder->orderBy("i.price ", "ASC");
                    break;
                case 'price_desc':
                    $queryBuilder->orderBy("i.price ", "desc");
                    break;
                case 'date_asc':
                    $queryBuilder->orderBy("i.createdAt", "ASC");
                    break;
                case  'date_desc';
                    $queryBuilder->orderBy("i.createdAt", "desc");
                    break;
            }
        }
        $query     = $queryBuilder->getQuery()->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $result["query"]["count"]     = $paginator->count();
        $result["query"]["pageSize"]  = $pageSize;
        $result["query"]["page"]      = $page;
        $result["query"]["pageCount"] = ceil((1.0) * $paginator->count() / $pageSize);
        $result["query"]["items"]     = [];

        /**
         * @var $type InsertionType
         */
        $type = $this->repository("type")->findOneBy(["id" => $typeId]);

        $attributes = $type->getAttributes();

        $valueMap     = [];
        $attributeMap = [];

        /**
         * @var $attribute InsertionTypeAttribute
         */
        foreach ($attributes as $attribute) {
            $attributeMap[$attribute->getAttributeKey()->getId()] = [
                "name" => $attribute->getAttributeKey()->getName(),
                "top"  => $attribute->isTop(),
            ];

            foreach ($attribute->getAttributeKey()->getValues() as $value) {
                $valueMap[$value->getId()] = $value->getValue();
            }
        }

        $result["values"] = $valueMap;

        foreach ($paginator as $item) {
            $data = [
                "id"        => $item->getId(),
                "contact"   => $item->getContact() != null ? $item->getContact()->toArray(0) : [],
                "content"   => [],
                "media"     => [],
                "values"    => [],
                "topvalues" => [],
                "price"     => $item->getPrice(),
                "tax"       => $item->isTax(),
                "createdAt" => $item->getCreatedAt(),

            ];

            foreach ($item->getContent() as $content) {
                $data["content"][] = $content->toArray(0);
            }

            foreach ($item->getMedia() as $media) {
                $data["media"][] = $media->toArray(0);
            }

            foreach ($item->getValues() as $value) {
                $data["values"][] = $value->toArray(0);;
            }

            /**
             * @var $attribute InsertionTypeAttribute
             */
            foreach ($attributes as $attribute) {
                if ($attribute->isTop()) {
                    foreach ($data["values"] as $value) {
                        if ($value["attributeKey"] == $attribute->getAttributeKey()->getId()) {
                            $data["topvalues"][] = [
                                "name"         => $attribute->getAttributeKey()->getName(),
                                "type"         => $attribute->getAttributeKey()->getType(),
                                "attributeKey" => $attribute->getAttributeKey()->getId(),
                                "value"        => $value["value"],
                            ];
                        }
                    }
                }
            }

            $result["query"]["items"][] = $data;
        }

        return $result;
    }

    public function getUserInsertions($user, $page = 1, $count = 10) : ?array {
        $insertions = $this->repository()->findBy(["user" => $user, "deleted" => false], null, $count, ($page - 1) * $count);

        $result = [];
        foreach ($insertions as $insertion) {
            $result[] = $insertion->toArray(2);
        }

        return $result;
    }
}
