<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\BannerController::class, 'store']), 'method' => 'post', 'id' => 'banner_add_form', 'files' => true ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'banner.add_banner' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('title', __( 'banner.title' ) . ':') !!}
        {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => __( 'banner.title' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'banner.short_description' ) . ':') !!}
        {!! Form::text('description', null, ['class' => 'form-control','placeholder' => __( 'banner.short_description' )]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('image', __('banner.image') . ':*') !!}
        {!! Form::file('image', ['id' => 'banner_image', 'accept' => 'image/*']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('position', __( 'banner.position' ) . ':') !!}
        {!! Form::number('position', null, ['class' => 'form-control', 'placeholder' => __( 'banner.position' ) ]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->