<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List all categories belonging to the authenticated user with item counts.
     *
     * @param  Request  $request  The incoming request.
     * @return JsonResponse List of user categories.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->withCount('vaultItems')
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new category for the authenticated user.
     *
     * @param  Request  $request  The incoming request.
     * @return JsonResponse The newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = $request->user()->categories()->create($validated);

        return response()->json([
            'ok' => true,
            'category' => $category->loadCount('vaultItems'),
        ], 201);
    }

    /**
     * Update an existing category owned by the authenticated user.
     *
     * @param  Request  $request  The incoming request.
     * @param  Category  $category  The category to update.
     * @return JsonResponse The updated category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $this->authorizeOwnership($request, $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return response()->json([
            'ok' => true,
            'category' => $category->loadCount('vaultItems'),
        ]);
    }

    /**
     * Delete a category owned by the authenticated user.
     *
     * Note: Vault items assigned to this category will have their category_id nullified.
     *
     * @param  Request  $request  The incoming request.
     * @param  Category  $category  The category to delete.
     * @return JsonResponse The outcome status.
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizeOwnership($request, $category);

        // Nullify foreign key on associated vault items before deleting
        $category->vaultItems()->update(['category_id' => null]);
        $category->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    /**
     * Ensure the category belongs to the current user, otherwise abort with 404.
     *
     * @param  Request  $request  The incoming request.
     * @param  Category  $category  The category to check.
     * @return void
     */
    private function authorizeOwnership(Request $request, Category $category): void
    {
        if ($category->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
