<?php

namespace App\Http\Controllers;

use App\Banner;
use App\Brands;
use App\Category;
use App\Product;
use Illuminate\Http\Request;

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
}
