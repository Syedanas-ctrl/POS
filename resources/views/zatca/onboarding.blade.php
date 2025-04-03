@extends('layouts.app')
@section('title', 'ZATCA Integration')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">ZATCA Integration</h1>
    <br>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-solid'])
                <div class="box-header with-border">
                    <h3 class="box-title">Awesome Shop</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Status information box -->
                            <div class="alert alert-success">
                                <p>Portal Mode : Developer Portal, Status : Success</p>
                            </div>
                        </div>
                    </div>

                    {!! Form::open(['url' => action([\App\Http\Controllers\ZatcaController::class, 'saveIntegration']), 'method' => 'post', 'id' => 'zatca_integration_form']) !!}
                    <div class="row">
                        <!-- Portal Mode -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('portal_mode', __('zatca.portal_mode') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.portal_mode_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-globe"></i>
                                    </span>
                                    {!! Form::select('portal_mode', ['developer' => __('zatca.developer_portal'), 'production' => __('zatca.production_portal')], 'developer', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'portal_mode', 'placeholder' => __('zatca.select_portal_mode')]); !!}
                                </div>
                            </div>
                        </div>

                        <!-- One-Time Password (OTP) -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('otp', __('zatca.one_time_password') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.otp_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-key"></i>
                                    </span>
                                    {!! Form::text('otp', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_otp'), 'id' => 'otp']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('email', __('business.email') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('business.email'), 'id' => 'email']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Common Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('common_name', __('zatca.common_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {!! Form::text('common_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_common_name'), 'id' => 'common_name']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Country Code -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('country_code', __('zatca.country_code') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                    {!! Form::text('country_code', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_country_code'), 'id' => 'country_code']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Organization Unit Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('organization_unit_name', __('zatca.organization_unit_name') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.organization_unit_name_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    {!! Form::text('organization_unit_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_organization_unit_name'), 'id' => 'organization_unit_name']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Organization Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('organization_name', __('zatca.organization_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building-o"></i>
                                    </span>
                                    {!! Form::text('organization_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_organization_name'), 'id' => 'organization_name']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- EGS Serial Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('egs_serial_number', __('zatca.egs_serial_number') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.egs_serial_number_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-barcode"></i>
                                    </span>
                                    {!! Form::text('egs_serial_number', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_egs_serial_number'), 'id' => 'egs_serial_number']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- VAT Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('vat_name', __('zatca.vat_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-money"></i>
                                    </span>
                                    {!! Form::text('vat_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_vat_name'), 'id' => 'vat_name']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Invoice Type -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('invoice_type', __('zatca.invoice_type') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.invoice_type_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-file-text"></i>
                                    </span>
                                    {!! Form::select('invoice_type', ['b2b_b2c' => __('zatca.together_b2b_b2c')], 'b2b_b2c', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'invoice_type']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- VAT Registration Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('vat_number', __('zatca.vat_number') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.vat_number_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-hashtag"></i>
                                    </span>
                                    {!! Form::text('vat_number', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_vat_number'), 'id' => 'vat_number']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Registered Address -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('registered_address', __('zatca.registered_address') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.registered_address_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::text('registered_address', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_registered_address'), 'id' => 'registered_address']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Business Category -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('business_category', __('zatca.business_category') . ':') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.business_category_help')"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-briefcase"></i>
                                    </span>
                                    {!! Form::text('business_category', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_business_category'), 'id' => 'business_category']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- CRN -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('crn', __('zatca.crn') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-id-card"></i>
                                    </span>
                                    {!! Form::text('crn', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_crn'), 'id' => 'crn']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Street Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('street_name', __('zatca.street_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-road"></i>
                                    </span>
                                    {!! Form::text('street_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_street_name'), 'id' => 'street_name']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Building Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('building_number', __('zatca.building_number') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-home"></i>
                                    </span>
                                    {!! Form::text('building_number', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_building_number'), 'id' => 'building_number']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Plot Identification -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('plot_identification', __('zatca.plot_identification') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map"></i>
                                    </span>
                                    {!! Form::text('plot_identification', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_plot_identification'), 'id' => 'plot_identification']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Sub Division Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('sub_division_name', __('zatca.sub_division_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-location-arrow"></i>
                                    </span>
                                    {!! Form::text('sub_division_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_sub_division_name'), 'id' => 'sub_division_name']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- City Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('city_name', __('zatca.city_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    {!! Form::text('city_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_city_name'), 'id' => 'city_name']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Postal Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('postal_number', __('zatca.postal_number') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope-o"></i>
                                    </span>
                                    {!! Form::text('postal_number', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_postal_number'), 'id' => 'postal_number']); !!}
                                </div>
                            </div>
                        </div>

                        <!-- Country Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('country_name', __('zatca.country_name') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-globe"></i>
                                    </span>
                                    {!! Form::text('country_name', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_country_name'), 'id' => 'country_name']); !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg">Submit</button>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            @endcomponent
        </div>
    </div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
        
        // Initialize select2 elements
        $('.select2').select2();
        
        $('#zatca_integration_form').validate({
            rules: {
                otp: {
                    required: true
                },
                email: {
                    required: true,
                    email: true
                },
                common_name: {
                    required: true
                },
                country_code: {
                    required: true
                },
                organization_unit_name: {
                    required: true
                },
                organization_name: {
                    required: true
                },
                egs_serial_number: {
                    required: true
                },
                vat_number: {
                    required: true
                }
            }
        });
    });
</script>
@endsection 