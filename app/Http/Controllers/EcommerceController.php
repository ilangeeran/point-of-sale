<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Brands;
use App\Category;
use App\Product;
use App\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EcommerceController extends Controller
{
    protected $business_id;

    public function __construct()
    {
        $this->business_id = env('BUSINESS_ID', 1);
    }

    public function brands()
    {
        try {
            $brands = Brands::where('business_id', $this->business_id)->get();

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($brands);
    }

    public function banners()
    {
        try {
            $banners = Banner::where('business_id', $this->business_id)->orderBy('position')->get();

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($banners);
    }

    public function categories()
    {
        try {
            // $categories = Category::where('business_id', $business_id)->get();
            $categories = Category::catAndSubCategories($this->business_id);

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($categories);
    }

    public function products(Request $request)
    {
        try {
            $limit = $request->limit ? $request->limit : 10;

            $query = Product::query();

            if($request->q) {
                $query->where('name', 'like', '%'.$request->q.'%');
            }

            $products = $query->active()->productForSales()->paginate($limit);

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($products);
    }

    public function wishlists(Request $request)
    {
        try {
            $limit = $request->limit ? $request->limit : 10;
            $user_id = Auth::id();
            
            $query = Product::join('wishlists', 'wishlists.product_id', '=', 'products.id')
                ->select('products.*')
                ->where('wishlists.user_id', $user_id)
                ->where('wishlists.business_id', $this->business_id);

            if($request->q) {
                $query->where('name', 'like', '%'.$request->q.'%');
            }

            $products = $query->active()->productForSales()->paginate($limit);

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($products);
    }

    public function toggleWishlists(Request $request)
    {
        try {
            $validator = $request->validate(
                [
                    'product_id' => 'required',
                ],
                [
                    'product_id.required' => __('validation.required', ['attribute' => __('product.product')]),
                ]
            );

            $input = $request->only(['product_id']);
            $input['user_id'] = Auth::id();
            $input['business_id'] = $this->business_id;
            
            $wishlist = Wishlist::where($input)->first();
            if($wishlist) {
                $wishlist->delete();

                $output = ['success' => true,
                    'msg' => __('product.removed_wishlist_success'),
                ];
            }else {
                $wishlist = Wishlist::create($input);

                $output = ['success' => true,
                    'msg' => __('product.added_wishlist_success'),
                ];
            }

        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
