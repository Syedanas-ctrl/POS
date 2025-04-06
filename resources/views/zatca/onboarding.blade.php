@extends('layouts.app')
@section('title', 'ZATCA Onboarding')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">ZATCA Onboarding</h1>
    <br>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            @component('components.widget', ['class' => 'box-solid'])
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        @foreach($business_locations as $key => $location)
                            <li class="{{ $loop->first ? 'active' : '' }}">
                                <a href="#location_{{ $key }}" data-toggle="tab" aria-expanded="true">{{ $location->name }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach($business_locations as $key => $location)
                            <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="location_{{ $key }}">
                                <div class="box-body">
                                    @if($zatca_certificates->where('business_location_id', $location->id)->first())
                                        <div class="alert alert-success">
                                            <p>ZATCA integration is already completed for this location.</p>
                                        </div>
                                    @endif

                                    {!! Form::open(['url' => action([\App\Http\Controllers\ZatcaController::class, 'saveIntegration'], [$location->id]), 'method' => 'put', 'id' => 'zatca_integration_form']) !!}
                                    <div class="row">
                                        <!-- Portal Mode -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('portal_mode', __('zatca.select_portal_mode') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-globe"></i>
                                                    </span>
                                                    {!! Form::select('portal_mode', ['developer' => __('zatca.developer_portal'), 'production' => __('zatca.production_portal')], 'developer', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'portal_mode', 'placeholder' => __('zatca.select_portal_mode')]) !!}
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
                                                    {!! Form::text('otp', null, ['class' => 'form-control', 'placeholder' => __('zatca.enter_otp'), 'id' => 'otp']) !!}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Common Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('common_name', __('zatca.common_name') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.change_from_settings')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-user"></i>
                                                    </span>
                                                    {!! Form::text('common_name', $business->name, ['class' => 'form-control', 'placeholder' => __('zatca.enter_common_name'), 'id' => 'common_name', 'disabled' => 'disabled']); !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Organization Unit Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('organization_unit_name', __('zatca.organization_unit_name') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.organization_unit_name_help')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-building"></i>
                                                    </span>
                                                    {!! Form::text('organization_unit_name', $location->name, ['class' => 'form-control', 'placeholder' => __('zatca.enter_organization_unit_name'), 'id' => 'organization_unit_name']) !!}
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
                                                    {!! Form::text('egs_serial_number', $location->egs_serial_number, ['class' => 'form-control', 'placeholder' => __('zatca.enter_egs_serial_number'), 'id' => 'egs_serial_number']) !!}
                                                </div>
                                            </div>
                                        </div>
                                        <!-- VAT Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('vat_name', __('zatca.vat_name') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.change_from_settings')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-money"></i>
                                                    </span>
                                                    {!! Form::text('vat_name', $business->tax_label_1, ['class' => 'form-control', 'placeholder' => __('zatca.enter_vat_name'), 'id' => 'vat_name']); !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">

                                        <!-- VAT Registration Number -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('vat_number', __('zatca.vat_number') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.vat_number_help')"></i>
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.change_from_settings')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-hashtag"></i>
                                                    </span>
                                                    {!! Form::text('vat_number', $business->tax_number_1, ['class' => 'form-control', 'placeholder' => __('zatca.enter_vat_number'), 'id' => 'vat_number']); !!}
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
                                                    {!! Form::text('registered_address', $location->registered_address, ['class' => 'form-control', 'placeholder' => __('zatca.enter_registered_address'), 'id' => 'registered_address']) !!}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Business Category -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('business_category', __('zatca.business_category') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.business_category_help')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-briefcase"></i>
                                                    </span>
                                                    {!! Form::text('business_category', $location->business_category, ['class' => 'form-control', 'placeholder' => __('zatca.enter_business_category'), 'id' => 'business_category']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        

                                        <!-- CRN -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('crn', __('zatca.crn') . ':') !!}
                                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="@lang('zatca.change_from_settings')"></i>
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-id-card"></i>
                                                    </span>
                                                    {!! Form::text('crn', $business->tax_number_2, ['class' => 'form-control', 'placeholder' => __('zatca.enter_crn'), 'id' => 'crn']); !!}
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
                                                    {!! Form::text('street_name', $location->street, ['class' => 'form-control', 'placeholder' => __('zatca.enter_street_name'), 'id' => 'street_name']) !!}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Building Number -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('building_number', __('zatca.building_number') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-home"></i>
                                                    </span>
                                                    {!! Form::text('building_number', $location->building_number, ['class' => 'form-control', 'placeholder' => __('zatca.enter_building_number'), 'id' => 'building_number']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Plot Identification -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('plot_identification', __('zatca.plot_identification') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-map"></i>
                                                    </span>
                                                    {!! Form::text('plot_identification', $location->plot_number, ['class' => 'form-control', 'placeholder' => __('zatca.enter_plot_identification'), 'id' => 'plot_identification']) !!}
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
                                                    {!! Form::text('sub_division_name', $location->sub_division_name, ['class' => 'form-control', 'placeholder' => __('zatca.enter_sub_division_name'), 'id' => 'sub_division_name']) !!}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- City Name -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('city_name', __('zatca.city_name') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-building"></i>
                                                    </span>
                                                    {!! Form::text('city_name', $location->city, ['class' => 'form-control', 'placeholder' => __('zatca.enter_city_name'), 'id' => 'city_name']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Postal Number -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('postal_number', __('zatca.postal_number') . ':') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-envelope-o"></i>
                                                    </span>
                                                    {!! Form::text('postal_number', $location->zip_code, ['class' => 'form-control', 'placeholder' => __('zatca.enter_postal_number'), 'id' => 'postal_number']) !!}
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
                                                    {!! Form::text('country_name', $location->country, ['class' => 'form-control', 'placeholder' => __('zatca.enter_country_name'), 'id' => 'country_name']) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 text-center">
                                            <button type="submit" class="btn btn-primary btn-lg" data-location-id="{{ $key }}">Submit</button>
                                        </div>
                                    </div>
                                    {!! Form::close() !!}
                                </div>
                            </div>
                        @endforeach
                    </div>
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
        
        // Add required class to important fields
        $('.form-group input, .form-group select').not('[disabled]').addClass('required');
        
        // Initialize form validation for each form
        $('form').each(function() {
            $(this).validate({
                // Add error class for styling
                errorClass: 'help-block',
                // Add error element
                errorElement: 'span',
                // Highlight error fields
                highlight: function(element) {
                    $(element).closest('.form-group').addClass('has-error');
                },
                // Remove highlight from valid fields
                unhighlight: function(element) {
                    $(element).closest('.form-group').removeClass('has-error');
                },
                // Rules for all important fields
                rules: {
                    'portal_mode': 'required',
                    'otp': 'required',
                    'organization_unit_name': 'required',
                    'egs_serial_number': 'required',
                    'registered_address': 'required',
                    'business_category': 'required',
                    'street_name': 'required',
                    'building_number': 'required',
                    'city_name': 'required',
                    'postal_number': 'required',
                    'country_name': 'required'
                },
                // Custom messages for validation
                messages: {
                    'portal_mode': 'Please select portal mode',
                    'otp': 'OTP is required',
                    'organization_unit_name': 'Organization unit name is required',
                    'egs_serial_number': 'EGS serial number is required',
                    'registered_address': 'Registered address is required',
                    'business_category': 'Business category is required',
                    'street_name': 'Street name is required',
                    'building_number': 'Building number is required',
                    'city_name': 'City name is required',
                    'postal_number': 'Postal number is required',
                    'country_name': 'Country name is required'
                },
                // Prevent form submission if validation fails
                submitHandler: function(form) {
                    if ($(form).valid()) {
                        form.submit();
                    }
                    return false;
                }
            });
        });
    });
</script>
@endsection 