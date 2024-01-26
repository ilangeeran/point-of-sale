@extends('layouts.app')
@section('title', 'Banners')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'banner.banners' )
        <small>@lang( 'banner.manage_banners' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'banner.all_banners' )])
        @can('banner.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\BannerController::class, 'create'])}}" 
                        data-container=".banners_modal">
                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('banner.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="banners_table">
                    <thead>
                        <tr>
                            <th>@lang( 'banner.title' )</th>
                            <th>@lang( 'banner.image' )</th>
                            <th>@lang( 'banner.position' )</th>
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade banners_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
