<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['exams', 'questions'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::slug($validated['name'], '_'));
        } else {
            $validated['code'] = strtoupper($validated['code']);
        }

        $category = Category::create($validated);
        AuditLogService::log('create_category', 'Category', $category->id);

        return back()->with('success', "Category '{$category->name}' added.");
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50', "unique:categories,code,{$category->id}"],
            'description' => ['nullable', 'string'],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $category->update($validated);

        AuditLogService::log('update_category', 'Category', $category->id);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        AuditLogService::log('delete_category', 'Category', null, ['name' => $name]);

        return back()->with('success', "Category '{$name}' deleted.");
    }
}
