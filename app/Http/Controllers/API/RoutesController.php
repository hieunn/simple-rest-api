<?php

use App\Http\Controllers\BaseController;
use App\Internal\Helper\ArrayHelper;
use App\Models\Route\Route;

class RoutesController extends BaseController
{
    public function index()
    {
        $input = $this->getParams();
        $list = $this->getList($input);
        $this->sendOutput($list);
    }

    public function getAction()
    {
        $this->sendOutput([]);
    }

    /**
     * Get list of node
     *
     * @param array $filters
     * @param bool $asTreeList
     * @return array
     * @throws Exception
     */
    public function getList($filters = [], $asTreeList = true)
    {
        $prepareData = $this->prepareDataforSearch($filters);

        if (!empty($prepareData['level'])) {
            return $this->getListByLevel($prepareData, $asTreeList);
        }

        //Find node by name
        $route = new Route();
        $list = $route->find($prepareData);

        if (empty($list)) {
            $this->sendOutput([]);
        }

        //Mapping array list as tree list
        $list = ArrayHelper::getIndexed($list, 'id');

        if ($asTreeList) {
            $list = $this->prepareListAsTree($list);
        }

        return $list;
    }

    /**
     * Find nodes by range and hierarchy level
     *
     * @param array $filters
     * @param bool $asTreeList
     * @return array
     * @throws Exception
     */
    protected function getListByLevel($filters = [], $asTreeList = true)
    {
        $route = new Route();
        $route->loadByFields($filters);

        if (!$route->isLoaded()) {
            return [];
        }

        //Return parent in case have no child
        if (!$route->hasChilds()) {
            return [$route->getName() => []];
        }

        $listChilds = $route->getChilds();
        $childsNumber = count($listChilds);

        //Prepare range for search
        $max = $route->getRgt() - (($childsNumber / $filters['level']) * $filters['level']);
        $min = $route->getLft();

        $search = [
            'max' => $max,
            'min' => $min
        ];

        $parentNode = $route->getData();
        $parentNode['parent_id'] = null;

        //Find nodes in range
        $list = $route->findByRange($search);
        $list[] = $parentNode;
        $list = ArrayHelper::getIndexed($list, 'id');

        if ($asTreeList) {
            $list = $this->prepareListAsTree($list, null, $filters['level']);
        }

        return $list;
    }

    /**
     * Process POST request to save new data
     *
     */
    public function postAction()
    {
        $input = $this->getParams();

        if (empty($input)) {
            $this->sendOutput([]);
        }

        //$arr = json_decode($input, true);

        foreach ($input as $child => $parent) {
            //Save parent node
            $parentNode = new Route();
            $parentNode->loadByFields(['name' => $parent]);

            //Create new node if it doesn't exist
            if (!$parentNode->isLoaded()) {
                $parentNode->saveRoute($parent);
            }

            //Save child node
            $parentId = $parentNode->getId();
            $childNode = new Route();
            $childNode->loadByFields(['name' => $child]);

            //1 child can belong to only one parent
            if ($childNode->isLoaded() && $childNode->hasParentId()) {
                continue;
            }

            $childNode->saveRoute($child, $parentId);
        }

        $list = $this->getList();
        $this->sendOutput($list);
    }

    /**
     * Prepare list as tree
     * @param $list
     * @param null $parentId
     * @param null $level
     * @return array
     */
    protected function prepareListAsTree($list, $parentId = null, &$level = null)
    {
        $result = array();

        foreach ($list as $item) {
            if ($item['parent_id'] == $parentId) {
                if (!is_null($level) && $level == 0) {
                    break;
                }

                $level--;
                $result[$item['name']] = $this->prepareListAsTree($list, $item['id'], $level);

                continue;
            }
        }
        return $result;
    }

    protected function prepareDataforSearch($filter = [])
    {
        $allowParams = [
            'name',
            'level'
        ];

        return ArrayHelper::only($filter, $allowParams);
    }
}