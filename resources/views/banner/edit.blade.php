<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\BannerController::class, 'update'], [$banner->id]), 'method' => 'PUT', 'id' => 'banner_edit_form', 'files' => true ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'banner.edit_banner' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('title', __( 'banner.title' ) . ':') !!}
        {!! Form::text('title', $banner->title, ['class' => 'form-control', 'placeholder' => __( 'banner.title' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'banner.short_description' ) . ':') !!}
        {!! Form::text('description', $banner->description, ['class' => 'form-control','placeholder' => __( 'banner.short_description' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('image', __('banner.image') . ':*') !!}
        {!! Form::file('image', ['id' => 'banner_image', 'accept' => 'image/*']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('position', __( 'banner.position' ) . ':') !!}
        {!! Form::number('position', $banner->position, ['class' => 'form-control', 'placeholder' => __( 'banner.position' ) ]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->