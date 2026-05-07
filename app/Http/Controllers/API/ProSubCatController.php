<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSelectResource;
use App\Http\Resources\ProductSubCategoryResource;

class ProSubCatController extends Controller
{
    // define middleware
    public function __construct()
    {
        $this->middleware('can:product-sub-category-list', ['only' => ['index', 'search']]);
        $this->middleware('can:product-sub-category-create', ['only' => ['create']]);
        $this->middleware('can:product-sub-category-edit', ['only' => ['update']]);
        $this->middleware('can:product-sub-category-create', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ProductSubCategoryResource::collection(ProductSubCategory::with('category')->latest()->paginate($request->perPage));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate request
        $this->validate($request, [
            'name' => 'required|string|max:50|unique:product_sub_categories',
            'category' => 'required',
            'note' => 'nullable|string|max:255',
        ]);
        try {
            // generate code
           $code = $request->code;
            // store sub category
            $ProductSubCategory =  ProductSubCategory::create([
                'name' => $request->name,
                  'code' => $request->code,
                'cat_id' => $request->category['id'],
                'note' => clean($request->note),
                'status' => $request->status,
            ]);

            // add activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($ProductSubCategory)
                ->withProperties([
                    'name' => $request->name,
                    'code' => '[' . config('config.proSubCatPrefix') . '-' . $code . ']',
                    'event' => 'Create',
                    'slug' => $ProductSubCategory->slug,
                    'routeName' => ''
                ])
                ->useLog('Product Sub Category Created')
                ->log('Product Sub Category Created');

            return $this->responseWithSuccess('Sub category added successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        try {
            $subCategory = ProductSubCategory::with('category')->where('slug', $slug)->first();

            return new ProductSubCategoryResource($subCategory);
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $subCategory = ProductSubCategory::where('slug', $slug)->first();
        // validate request
        $this->validate($request, [
            'name' => 'required|string|max:50|unique:product_sub_categories,name,' . $subCategory->id,
            'category' => 'required',
            'note' => 'nullable|string|max:255',
              'code' => 'required|string|max:50|unique:product_sub_categories,code',
        ]);

        try {
            // update sub category
            $subCategory->update([
                'name' => $request->name,
                'cat_id' => $request->category['id'],
                'note' => clean($request->note),
                'status' => $request->status,
            ]);

            // add activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($subCategory)
                ->withProperties([
                    'name' => $subCategory->name,
                    'code' => '[' . config('config.proSubCatPrefix') . '-' . $subCategory->code . ']',
                    'event' => 'Update',
                    'slug' => $subCategory->slug,
                    'routeName' => ''
                ])
                ->useLog('Product Sub Category Updated')
                ->log('Product Sub Category Updated');

            return $this->responseWithSuccess('Sub category updated successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        try {
            $subCategory = ProductSubCategory::where('slug', $slug)->first();

            // add activity log
            activity()
                ->causedBy(Auth::user())
                ->performedOn($subCategory)
                ->withProperties([
                    'name' => $subCategory->name,
                    'code' => '[' . config('config.proSubCatPrefix') . '-' . $subCategory->code . ']',
                    'event' => 'Delete'
                ])
                ->useLog('Product Sub Category Deleted')
                ->log('Product Sub Category Deleted');

            $subCategory->delete();

            return $this->responseWithSuccess('Sub category deleted successfully');
        } catch (Exception $e) {
            return $this->responseWithError($e->getMessage());
        }
    }

    /**
     * search resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $term = $request->term;
        $filterType = $request->filterType;
        $query = ProductSubCategory::with('category');

        // Apply status filter
        if ($filterType === 'active') {
            $query->where('status', 1);
        } elseif ($filterType === 'inactive') {
            $query->where('status', 0);
        }

        // Apply term search
        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', '%' . $term . '%')
                  ->orWhere('note', 'LIKE', '%' . $term . '%')
                  ->orWhereHas('category', function ($newQuery) use ($term) {
                      $newQuery->where('name', 'LIKE', '%' . $term . '%');
                  });
            });
        }

        $result = $query->latest()->paginate($request->perPage);

        return ProductSubCategoryResource::collection($result);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allSubCategories()
    {
        $subCategories = ProductSubCategory::where('status', 1)->latest()->get();

        return ProductSubCategoryResource::collection($subCategories);
    }

    // retun subcategories by category
    public function subCategoriesByCategory($slug)
    {
        if ($slug == 'all') {
            $subCategories = ProductSubCategory::latest()->get();
            $products = Product::latest()->get();
        } else {
            $category = ProductCategory::where('slug', $slug)->first();
            $subCategories = ProductSubCategory::where('cat_id', $category->id)->latest()->get();
            $products = Product::with('proSubCategory.category')->whereHas('proSubCategory', function ($newQuery) use ($category) {
                $newQuery->whereHas('category', function ($newQuery) use ($category) {
                    $newQuery->where('id', $category->id);
                });
            })->get();
        }

        return [
            'cats' => ProductSubCategoryResource::collection($subCategories),
            'products' => ProductResource::collection($products),
        ];
    }

    // retun subcategories by category
    public function allSubCategoriesByCategory($slug)
    {
        if ($slug == 'all') {
            $subCategories = ProductSubCategory::latest()->get();
            $products = Product::latest()->paginate(5);
        } else {
            $category = ProductCategory::where('slug', $slug)->first();
            $subCategories = ProductSubCategory::where('cat_id', $category->id)->latest()->get();
            $products = Product::with('proSubCategory.category')->whereHas('proSubCategory', function ($newQuery) use ($category) {
                $newQuery->whereHas('category', function ($newQuery) use ($category) {
                    $newQuery->where('id', $category->id);
                });
            })->paginate(5);
        }

        return [
            'cats' => ProductSubCategoryResource::collection($subCategories),
            'products' => ProductSelectResource::collection($products),
        ];
    }
}
