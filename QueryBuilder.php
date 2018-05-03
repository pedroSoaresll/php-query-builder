<?php
/**
 * Created by PhpStorm.
 * User: pedro
 * Date: 09/02/2018
 * Time: 21:21
 */

namespace App\Builders;


use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model as Moloquent;

class QueryBuilder
{
    public $model;
    public $query;
    public $where;
    public $fields = [];
    public $relations = [];
    public $offset = 0;
    public $limit = null;
    public $range = ['offset' => '0', 'limit' => null];

    function __construct(Request $request, Moloquent $model)
    {
        $this->model = $model;
        $this->query = $request->query();
    }

    public function buildFields ($value) {
        $this->fields = explode(',', $value);
    }

    public function buildRelations ($value) {
        $this->relations = explode(',', $value);
    }

    public function buildRange ($value) {
        $valueRange = explode(',', $value);

        if (count($valueRange) == 1) {
            $this->range['limit'] = (int) $valueRange[0];
        } else {
            $this->range['offset'] = (int) $valueRange[0];
            $this->range['limit'] = (int) $valueRange[1];
        }

        $this->offset = $this->range['offset'];
        $this->limit = $this->range['limit'];
    }

    public function buildWhere ($value) {
        $listConditions = explode(',', $value);
        $i = 0;
        foreach ($listConditions as $condition) {
            $condition = explode(':', $condition);

            if ($condition[1] == 'null') $condition[1] = null;
            
            $this->where[] = [
                $condition[0] => $condition[1]
            ];

//            $condition[1] = $condition[0] == 'status' ? (int) $condition[1] : $condition[1];
            $this->model = $this->model->where($condition[0], $condition[1]);
        }
    }

    public function buildOrWhere ($value) {
        $listConditions = explode(',', $value);
        $i = 0;
        foreach ($listConditions as $condition) {
            $condition = explode(':', $condition);
            $this->where[] = [
                $condition[0] => $condition[1]
            ];

            $this->model = $this->model->orWhere($condition[0], $condition[1]);
        }
    }

    public function build () {
        if (empty($this->query)) return false;
        foreach ($this->query as $key => $value) {
            if (empty($value)) continue;

            if ($key == 'fields') $this->buildFields($value);
            else if ($key == 'relations') $this->buildRelations($value);
            else if ($key == 'range') $this->buildRange($value);
            else if ($key == 'where') $this->buildWhere($value);
            else if ($key == 'orWhere') $this->buildOrWhere($value);
        }

        return true;
    }

    public function getAll () {
        return $this->model
            ->with($this->relations)
            ->offset($this->offset)
            ->limit($this->limit)
            ->get($this->fields);
    }
}
