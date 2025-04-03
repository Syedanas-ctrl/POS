<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action([\App\Http\Controllers\BusinessLocationController::class, 'updateZatcaConfig'], [$location->id]), 'method' => 'PUT', 'id' => 'business_location_zatca_form' ]) !!}

        {!! Form::hidden('hidden_id', $location->id, ['id' => 'hidden_id']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'business.edit_business_location' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="row">
                        <!-- Portal Mode -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('portal_mode', 'Portal Mode:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Choose between Developer (Testing) or Production portal"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-globe"></i>
                                    </span>
                                    {!! Form::select('portal_mode', ['developer' => 'Developer Portal', 'production' => 'Production Portal'], 'developer', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'portal_mode', 'placeholder' => 'Select Portal Mode']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- One-Time Password (OTP) -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('otp', 'One-Time Password:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter the OTP you received from ZATCA portal"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-key"></i>
                                    </span>
                                    {!! Form::text('otp', null, ['class' => 'form-control', 'placeholder' => 'Enter OTP', 'id' => 'otp']) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'messages.save' )</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->