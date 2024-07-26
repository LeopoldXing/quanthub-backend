<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * create category if not exist
     *
     * @param $category
     * @param $operator_id
     * @return mixed
     */
    public function saveCategory($category, $operator_id): mixed {
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

    public function getCategories(): Collection {
        return DB::table('categories')
            ->select('name')
            ->orderBy(DB::raw('LEFT(name, 1)'))
            ->get();
    }

    /**
     * delete all unused categories
     *
     * @return void
     */
    public function removeUnusedCategories(): void {
        DB::table('categories')->whereNotIn('id', function ($query) {
            $query->select('category_id')->from('articles');
        })->delete();
    }

}
