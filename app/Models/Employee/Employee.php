<?php

namespace App\Models\Employee;

use App\Models\BaseModel\BaseModel;

class Employee extends BaseModel
{
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'employees';

    protected $cols = [
        'name',
        'parent_id',
        'lft',
        'rgt'
    ];
}