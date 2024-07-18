<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    /**
     * create category if not exist
     *
     * @param $category
     * @param $operator_id
     * @return mixed
     */
    public function saveCategory($category, $operator_id) {
        if (!empty($category)) {
            $res = Category::firstOrCreate(
                ['name' => $category],
                ['created_by' => $operator_id, 'updated_by' => $operator_id]
            );
        } else {
            $res = Category::firstOrCreate(
                ['name' => 'unknown'],
                ['created_by' => $operator_id, 'updated_by' => $operator_id]
            );
        }

        return $res;
    }
}
