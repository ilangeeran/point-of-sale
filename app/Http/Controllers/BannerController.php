<?php

namespace App\Http\Controllers;

use App\Banner;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\Util;

class BannerController extends Controller
{
    protected $util;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('banner.view') && ! auth()->user()->can('banner.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $banners = Banner::where('business_id', $business_id)
                        ->select(['title', 'image', 'position', 'id']);

            return Datatables::of($banners)
                ->editColumn('image', function ($row) {
                    return '<div style="display: flex;"><img src="'.$row->image_url.'" alt="Banner image" class="product-thumbnail-small"></div>';
                })
                ->addColumn(
                    'action',
                    '@can("banner.update")
                    <button data-href="{{action(\'App\Http\Controllers\BannerController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_banner_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("banner.delete")
                        <button data-href="{{action(\'App\Http\Controllers\BannerController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_banner_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->removeColumn('id', 'image_url')
                ->rawColumns([1, 3])
                ->make(false);
        }

        return view('banner.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('banner.create')) {
            abort(403, 'Unauthorized action.');
        }

        $quick_add = false;
        if (! empty(request()->input('quick_add'))) {
            $quick_add = true;
        }

        return view('banner.create')
                ->with(compact('quick_add'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('banner.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = $request->validate(
                [
                    'title' => 'sometimes|nullable|max:255',
                    'image' => 'required',
                ],
                [
                    'title.required' => __('validation.required', ['attribute' => __('banner.title')]),
                    'image.required' => __('validation.required', ['attribute' => __('banner.image')]),
                ]
            );

            $input = $request->only(['title', 'description', 'position']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            //upload logo
            $image_name = $this->util->uploadFile($request, 'image', 'banners', 'image');
            if (! empty($image_name)) {
                $input['image'] = $image_name;
            }

            $banner = Banner::create($input);
            $output = ['success' => true,
                'data' => $banner,
                'msg' => __('banner.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('banner.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $banner = Banner::where('business_id', $business_id)->find($id);

            return view('banner.edit')
                ->with(compact('banner'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('banner.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['title', 'description', 'position']);
                $business_id = $request->session()->get('user.business_id');

                $banner = Banner::where('business_id', $business_id)->findOrFail($id);
                $banner->title = $input['title'];
                $banner->description = $input['description'];
                $banner->position = $input['position'];

                //upload document
                $file_name = $this->util->uploadFile($request, 'image', 'banners', 'image');
                if (! empty($file_name)) {

                    //If previous image found then remove
                    if (! empty($banner->image_path) && file_exists($banner->image_path)) {
                        unlink($banner->image_path);
                    }

                    $banner->image = $file_name;
                }

                $banner->save();

                $output = ['success' => true,
                    'msg' => __('banner.banner_update_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('banner.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $banner = Banner::where('business_id', $business_id)->findOrFail($id);
                $banner->delete();

                $output = ['success' => true,
                    'msg' => __('banner.banner_delete_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
