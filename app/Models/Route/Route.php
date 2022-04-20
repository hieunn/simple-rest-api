<?php

namespace App\Models\Route;

use App\Models\BaseModel\BaseModel;

class Route extends BaseModel
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'routes';

    protected $cols = [
        'name',
        'parent_id',
        'lft',
        'rgt'
    ];

    /**
     * Store new node
     *
     * @param $name
     * @param null $parentId
     * @throws \Exception
     */
    public function saveRoute($name, $parentId = null)
    {
        //set max range for node
        if (empty($parentId)) {
            $maxRange = 0;
        } else {
            $maxRange = $this->getMaxRange($parentId);
        }

        //Extend for other nodes in range
        if (!empty($parentId)) {
            $this->updateRange($maxRange);
        }

        //Save data for node
        if (!$this->isLoaded()) {
            $dataToSave = [
                'name' => $name,
                'lft' => $maxRange + 1,
                'rgt' => $maxRange + 2
            ];
        }

        //Update parent id for child node
        if (!empty($parentId)) {
            $dataToSave['parent_id'] = $parentId;
        }

        $this->save($dataToSave);

        //Extend for parent node
        if ($this->hasChilds()) {
            $this->updateRangeById($parentId, "rgt", $this->getRgt());
            $this->updateRangeById($this->id, "lft", $maxRange);
        }
    }

    /**
     * Get MAX value of range from left or right of node
     *
     * @param null $parentId
     * @return int|mixed
     * @throws \Exception
     */
    public function getMaxRange($parentId = null)
    {
        $childs = $this->find(['parent_id' => $parentId]);
        $range = "lft";
        $field = "id";

        if (count($childs) > 0) {
            $range = "rgt";
            $field = "parent_id";
        }

        $input = [];
        $sql = "SELECT MAX($range) as max_range FROM {$this->table}";

        if (!empty($parentId)) {
            $sql .= " WHERE $field = :id";
            $input = ['id' => $parentId];
        }

        try {
            $statement = $this->execute($sql, $input);

            if (!$statement) {
                return 0;
            }

            $result = $statement->fetch(\PDO::FETCH_ASSOC);

            return !empty($result['max_range']) ? $result['max_range'] : 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Extend range to left and right for other nodes in range
     *
     * @param int $currentRange
     * @return int
     * @throws \Exception
     */
    public function updateRange($currentRange = 0)
    {
        $sql = "
            UPDATE {$this->table} SET rgt = rgt + 2 WHERE rgt > :rgt;
            UPDATE {$this->table} SET lft = lft + 2 WHERE lft > :lft 
            ";

        $input = [
            'rgt' => $currentRange,
            'lft' => $currentRange,
        ];

        try {
            /** @var \PDOStatement $statement */
            $statement = $this->execute($sql, $input);

            if (!$statement) {
                return 0;
            }

            return $statement->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Update range to left or right for node by Id
     * @param $id
     * @param string $field
     * @param string $val
     * @return int
     * @throws \Exception
     */
    protected function updateRangeById($id, $field = '', $val = '')
    {
        $sql = " UPDATE {$this->table} SET {$field} = {$field} + {$val} WHERE id = :id";

        try {
            /** @var \PDOStatement $statement */
            $statement = $this->execute($sql, [
                'id' => $id,
            ]);

            if (!$statement) {
                return 0;
            }

            return $statement->rowCount();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Find node in range
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function findByRange($filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE rgt <= :max AND lft > :min";

        try {
            $statement = $this->execute($sql, $filters);

            if (!$statement) {
                return [];
            }

            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception($e);
        }
    }

    /**
     * Range to the left value
     *
     * @return mixed|null
     */
    public function getLft()
    {
        return $this->getProperty('lft');
    }

    /**
     * Range to the right value
     *
     * @return mixed|null
     */
    public function getRgt()
    {
        return $this->getProperty('rgt');
    }

    /**
     * Value of name
     *
     * @return mixed|null
     */
    public function getName()
    {
        return $this->getProperty('name');
    }

    /**
     * Value of parent_id
     *
     * @return mixed|null
     */
    public function getParentId()
    {
        return $this->getProperty('parent_id');
    }

    /**
     * Return true if node has parent
     *
     * @return bool
     */
    public function hasParentId()
    {
        return $this->getParentId() != null;
    }

    /**
     * Get childs from parent node
     *
     * @return array|false
     */
    public function getChilds()
    {
        if (!$this->isLoaded()) {
            return [];
        }

        try {
            $childs = $this->find(['parent_id' => $this->id]);
        } catch (\Exception $e) {
            return [];
        }

        return $childs;
    }

    /**
     * Return true if parent node has child nodes
     *
     * @return bool
     */
    public function hasChilds()
    {
        return count($this->getChilds()) > 0;
    }
}