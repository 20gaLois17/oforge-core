<?php

namespace Oforge\Engine\Modules\I18n\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Helper\CsvHelper;
use Oforge\Engine\Modules\I18n\Models\Snippet;

/**
 * Class InternationalizationService
 *
 * @package Oforge\Engine\Modules\I18n\Services
 */
class InternationalizationService extends AbstractDatabaseAccess {
    /** @var array $cache */
    private $cache;

    public function __construct() {
        parent::__construct(Snippet::class);
    }

    /**
     * @param string $key
     * @param string $language
     * @param string|array|null $defaultValue
     *
     * @return string
     * @throws ORMException
     */
    public function get(string $key, string $language, $defaultValue = null) : string {
        if (!isset($this->cache[$language][$key])) {
            /** @var LanguageService $languageService */
            $languageService = Oforge()->Services()->get('i18n.language');
            $languageIsos       = array_keys($languageService->getFilterDataLanguages(true));

            // if (is_array($defaultValue)) {
            //     if (empty($defaultValue)) {
            //         return $key;
            //     }
            //     $result = null;
            //     if (isset($defaultValue[$language])) {
            //         $result = self::$i18nService->get($key, $language, $defaultValue[$language]);
            //     } else {
            //         $result = self::$i18nService->get($key, $language, null);
            //     }
            //     foreach ($defaultValue as $languageIso => $languageDefaultValue) {
            //         if ($language === $languageIso) {
            //             continue;
            //         }
            //         $tmpResult = self::$i18nService->get($key, $languageIso, $defaultValue[$languageIso]);
            //         if ($result === null) {
            //             $result = $tmpResult;
            //         }
            //     }
            //
            //     return $result;
            // }

            /** @var Snippet $snippet */
            $snippet = $this->repository()->findOneBy([
                'name'  => $key,
                'scope' => $language,
            ]);
            if (!isset($snippet)) {
                $snippet = Snippet::create([
                    'name'  => $key,
                    'scope' => $language,
                    'value' => isset($defaultValue) ? $defaultValue : $key,
                ]);
                $this->entityManager()->create($snippet);
            }
            if (!isset($this->cache[$language])) {
                $this->cache[$language] = [];
            }
            $this->cache[$language][$key] = $snippet->getValue();
        }

        return $this->cache[$language][$key];
    }

    /**
     * Creates / Updates Text-Snippets from given .csv file.<br>
     * A Text-Snippet consists of three parameters: Scope, Name and Value.<br>
     * Therefore a line in the .csv file should look like this (optional enclosure): "scope";"name";"value".<br>
     * As of this point, the function can only be called from the console<br>
     * ( i.e. 'php /bin/console oforge:service:run i18n:importFromCsv mysnippets.csv' ).
     *
     * @param string $filepath Absolute path to file.
     * @param array $options Optional csv reader config, see CsvHelper::DEFAULT_CONFIG.
     *
     * @return array Statistics array with lines (number of processed lines), skipped (skipped lines with errors), created & updated (created or updated snippets)
     * @see CsvHelper::DEFAULT_CONFIG for config keys.
     */
    public function importFromCsv(string $filepath, array $options = []) : array {
        $options = array_merge($options, [
            'header-row' => false,
        ]);

        $statistics = [
            'lines'         => 0,
            'lines_empty'   => 0,
            'lines_skipped' => 0,
            'created'       => 0,
            'updated'       => 0,
        ];

        $rowCallable = function ($row) use (&$statistics) {
            if (!is_array($row) || empty($row)) {
                $statistics['lines_empty']++;

                return;
            }
            if (count($row) !== 3) {
                $statistics['lines_skipped']++;

                return;
            }
            $statistics['lines']++;
            $languageIso = $row[0];
            $key         = $row[1];
            $value       = $row[2];

            $snippet = $this->repository()->findOneBy([
                'scope' => $languageIso,
                'name'  => $key,
            ]);
            if (!isset($snippet)) {
                if (empty($value) && $value !== 0) {
                    $value = $key;
                }
                $snippet = Snippet::create([
                    'scope' => $languageIso,
                    'name'  => $key,
                    'value' => $value,
                ]);
                $this->entityManager()->create($snippet);
                $statistics['created']++;
            } elseif (!empty($value)) {
                $snippet->setValue($value);
                $this->entityManager()->update($snippet);
                $statistics['updated']++;
            }
        };
        try {
            CsvHelper::read($filepath, $rowCallable, $options);
        } catch (\Exception $exception) {
            Oforge()->Logger()->logException($exception);
        }

        return $statistics;
    }

    /**
     * @param string $key
     * @param string $language
     *
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function exists(string $key, string $language) : bool {
        if (!isset($this->cache[$key])) {
            /** @var Snippet $snippet */
            $snippet = $this->repository()->findOneBy([
                'name'  => $key,
                'scope' => $language,
            ]);

            return isset($snippet);
        }

        return true;
    }

}
