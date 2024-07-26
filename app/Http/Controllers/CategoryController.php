<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService) {
        $this->categoryService = $categoryService;
    }

    public function saveCategory($category, $operatorId): JsonResponse {
        return response()->json($this->categoryService->saveCategory($category, $operatorId), 201);
    }

    public function getAllCategories(): JsonResponse {
        return response()->json($this->categoryService->getCategories()->map(function ($category) {
            return $category->name;
        }), 200);
    }
}
