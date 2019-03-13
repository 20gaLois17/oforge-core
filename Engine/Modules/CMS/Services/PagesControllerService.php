<?php

namespace Oforge\Engine\Modules\CMS\Services;

use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\I18n\Models\Language;
use Oforge\Engine\Modules\CMS\Models\Layout\Layout;
use Oforge\Engine\Modules\CMS\Models\Site\Site;
use Oforge\Engine\Modules\CMS\Models\Page\Page;
use Oforge\Engine\Modules\CMS\Models\Page\PagePath;

class PagesControllerService extends AbstractDatabaseAccess {
    private $entityManager = NULL;
    
    public function __construct() {
        parent::__construct(["language" => Language::class, "layout" => Layout::class, "site" => Site::class, "page" => Page::class, "pagePath" => PagePath::class]);
        
        $this->entityManager = Oforge()->DB()->getManager();
    }
    
    public function getAvailableLanuages()
    {
        $languageEntities = $this->repository('language')->findAll();
        
        $languages = [];
        foreach ($languageEntities as $languageEntity)
        {
            $language = [];
            $language["id"] = $languageEntity->getId();
            $language["iso"] = $languageEntity->getIso();
            $language["name"] = $languageEntity->getName();
            $language["active"] = $languageEntity->isActive();
            
            $languages[] = $language;
        }
        
        return $languages;
    }
    
    public function getDefaultLanguageForPage($id)
    {
        $pageEntity = $this->repository('page')->findOneBy(["id" => $id]);
        
        if ($pageEntity)
        {
            $siteEntity = $this->repository('site')->findOneBy(["id" => $pageEntity->getSite()]);
            
            if ($siteEntity)
            {
                return $siteEntity->getId();
            }
        }
        
        return 0;
    }
    
    private function findAndRemoveChildPages($parentId)
    {
        $pageEntities = $this->repository('page')->findBy(["parent" => $parentId]);
        
        foreach ($pageEntities as $pageEntity)
        {
            $this->findAndRemoveChildPages($pageEntity->getId());
            
            $this->entityManager->remove($pageEntity);
            $this->entityManager->flush();
        }
    }
    
    public function editPageData($post)
    {
        $pageTreeService = OForge()->Services()->get("page.tree.service");
        
        $selectedPageId       = isset($post["cms_edit_page_id"])        && $post["cms_edit_page_id"] > 0         ? $post["cms_edit_page_id"]        : 0;
        $selectedPageParentId = isset($post["cms_edit_page_parent_id"]) && $post["cms_edit_page_parent_id"] > 0  ? $post["cms_edit_page_parent_id"] : 0;
        $selectedPageName     = isset($post["cms_edit_page_name"])      && !empty($post["cms_edit_page_name"])   ? $post["cms_edit_page_name"]      : false;
        $selectedAction       = isset($post["cms_edit_page_action"])    && !empty($post["cms_edit_page_action"]) ? $post["cms_edit_page_action"]    : false;
        
        switch($selectedAction)
        {
            case 'create':
                // TODO: get selected layout and site instead of using default ones 
                $layoutEntity = $this->repository('layout')->findOneBy(["id" => 1]);
                $siteEntity   = $this->repository('site')->findOneBy(["id" => 1]);
                
                if ($layoutEntity && $siteEntity)
                {
                    $pageEntity = new Page;
                    $pageEntity->setLayout($layoutEntity->getId());
                    $pageEntity->setSite($siteEntity->getId());
                    $pageEntity->setParent($selectedPageParentId);
                    $pageEntity->setName($selectedPageName);
                    
                    $this->entityManager->persist($pageEntity);
                    $this->entityManager->flush();
                    
                    $pageId = $pageEntity->getId();
                }
                break;
            case 'rename':
                $pageEntity = $this->repository('page')->findOneBy(["id" => $selectedPageId]);
                
                if ($pageEntity)
                {
                    $pageEntity->setName($selectedPageName);
                    
                    $this->entityManager->persist($pageEntity);
                    $this->entityManager->flush();
                }
                break;
            case 'delete':
                $pageEntity = $this->repository('page')->findOneBy(["id" => $selectedPageId]);

                if ($pageEntity)
                {
                    $this->findAndRemoveChildPages($pageEntity->getId());
                    
                    $this->entityManager->remove($pageEntity);
                    $this->entityManager->flush();
                }
                break;
        }
        
        $data = [
            "js"   => ["cms_page_controller_jstree_config" => $pageTreeService->generateJsTreeConfigJSON()],
            "post" => $post
        ];
        
        $data["__newlyCreatedPageId"] = $pageId; // TODO: just used as development info
        $data["__selectedPageId"] = $selectedPageId; // TODO: just used as development info
        $data["__selectedPageParentId"] = $selectedPageParentId; // TODO: just used as development info
        $data["__selectedPageName"] = $selectedPageName; // TODO: just used as development info
        $data["__selectedAction"] = $selectedAction; // TODO: just used as development info
        
        return $data;
    }
    
    public function checkForValidPagePath($post)
    {
        $selectedPage       = isset($post["cms_page_jstree_selected_page"]) && $post["cms_page_jstree_selected_page"] > 0 ? $post["cms_page_jstree_selected_page"] : 0;
        $selectedLanguage   = isset($post["cms_page_selected_language"])    && $post["cms_page_selected_language"] > 0    ? $post["cms_page_selected_language"]    : $post["cms_page_selected_language"] = $this->getDefaultLanguageForPage($selectedPage);
        
        $pagePathEntity = $this->repository('pagePath')->findOneBy(["page" => $selectedPage, "language" => $selectedLanguage]);
        
        if ($pagePathEntity)
        {
            return TRUE;
        }
        
        return FALSE;
    }
    
    public function editPagePathData($post)
    {
        $pageTreeService = OForge()->Services()->get("page.tree.service");
        
        $data = [
            "js"                      => ["cms_page_controller_jstree_config" => $pageTreeService->generateJsTreeConfigJSON()],
            "post"                    => $post,
            "cms_page_builder_action" => "edit_page_path"
        ];
        
        return $data;
    }
    
    public function editContentData($post)
    {
        $pageTreeService    = OForge()->Services()->get("page.tree.service");
        $pageBuilderService = OForge()->Services()->get("page.builder.service");
        $contentTypeService = OForge()->Services()->get("content.type.service");
        
        $selectedPage       = isset($post["cms_page_jstree_selected_page"]) && $post["cms_page_jstree_selected_page"] > 0 ? $post["cms_page_jstree_selected_page"] : 0;
        $selectedLanguage   = isset($post["cms_page_selected_language"])    && $post["cms_page_selected_language"] > 0    ? $post["cms_page_selected_language"]    : $post["cms_page_selected_language"] = $this->getDefaultLanguageForPage($selectedPage);
        $selectedElement    = isset($post["cms_page_selected_element"])     && !empty($post["cms_page_selected_element"]) ? $post["cms_page_selected_element"]     : 0;
        $selectedAction     = isset($post["cms_page_selected_action"])      && !empty($post["cms_page_selected_action"])  ? $post["cms_page_selected_action"]      : 'edit';
        
        $data = [
            "js"                => ["cms_page_controller_jstree_config" => $pageTreeService->generateJsTreeConfigJSON()],
            "languages"         => $this->getAvailableLanuages(),
            "pages"             => $pageTreeService->getPageArray(),
            "contentTypeGroups" => $contentTypeService->getContentTypeGroupArray(),
            "selectedElement"   => $selectedElement,
            "post"              => $post
        ];
        
        if ($selectedPage)
        {
            $pageArray        = $pageBuilderService->getPageArray($selectedPage);
            $pageContents     = $pageArray["paths"][$selectedLanguage]["pageContent"];
            
            if ($selectedElement)
            {
                $data["contents"] = $pageBuilderService->getContentDataArrayById($pageContents, $selectedElement);
                
                $selectedElementIdArray = explode("-", $selectedElement);
                $selectedElementId = end($selectedElementIdArray);
                
                $data["__selectedElement"] = $selectedElement; // TODO: just used as development info
                $data["__selectedElementId"] = $selectedElementId; // TODO: just used as development info
                $data["__selectedElementTypeId"] = $data["contents"]["typeId"]; // TODO: just used as development info
                $data["__selectedAction"] = $selectedAction; // TODO: just used as development info
                
                if (is_numeric($selectedElementId) && $selectedElementId > 0)
                {
                    $selectedElementTypeId = $data["contents"]["typeId"];
                    
                    if (!is_numeric($selectedElementTypeId))
                    {
                        $selectedElementTypeId = 0;
                    }
                    
                    switch ($selectedAction)
                    {
                        case "submit":
                            // persist new content element data to database and reload content data from database
                            $data["contentElementData"] = $contentTypeService->setContentDataArray($selectedElementId, $selectedElementTypeId, $post)->getContentDataArray($selectedElementId, $selectedElementTypeId);
                            $data["contents"]           = $pageBuilderService->getContentDataArrayById($pageContents, $selectedElement);
                            break;
                        case "delete":
                            break;
                        default:
                            // action equals 'edit' or is unknown
                            $data["contentElementData"] = $contentTypeService->getContentDataArray($selectedElementId, $selectedElementTypeId);
                            break;
                    }
                }
            }
            else
            {
                $data["contents"] = $pageBuilderService->getContentDataArray($pageContents);
            }
            
            $data["pageBuilderData"] = $pageArray; // TODO: just used as development info
        }
        
        return $data;
    }
}