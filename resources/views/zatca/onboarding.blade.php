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

                        <!-- Email -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('email', 'Email:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => 'Email', 'id' => 'email']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Common Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('common_name', 'Common Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {!! Form::text('common_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Common Name', 'id' => 'common_name']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Country Code -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('country_code', 'Country Code:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                    {!! Form::text('country_code', null, ['class' => 'form-control', 'placeholder' => 'Enter Country Code', 'id' => 'country_code']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Organization Unit Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('organization_unit_name', 'Organization Unit Name:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter your department or division name"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    {!! Form::text('organization_unit_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Organization Unit Name', 'id' => 'organization_unit_name']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Organization Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('organization_name', 'Organization Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building-o"></i>
                                    </span>
                                    {!! Form::text('organization_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Organization Name', 'id' => 'organization_name']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- EGS Serial Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('egs_serial_number', 'EGS Serial Number:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter the electronic generating system serial number"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-barcode"></i>
                                    </span>
                                    {!! Form::text('egs_serial_number', null, ['class' => 'form-control', 'placeholder' => 'Enter EGS Serial Number', 'id' => 'egs_serial_number']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- VAT Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('vat_name', 'VAT Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-money"></i>
                                    </span>
                                    {!! Form::text('vat_name', null, ['class' => 'form-control', 'placeholder' => 'Enter VAT Name', 'id' => 'vat_name']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Invoice Type -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('invoice_type', 'Invoice Type:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Select the type of invoices to be generated"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-file-text"></i>
                                    </span>
                                    {!! Form::select('invoice_type', ['b2b_b2c' => 'Together B2B & B2C'], 'b2b_b2c', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'invoice_type']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- VAT Registration Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('vat_number', 'VAT Number:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter your VAT registration number from ZATCA"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-hashtag"></i>
                                    </span>
                                    {!! Form::text('vat_number', null, ['class' => 'form-control', 'placeholder' => 'Enter VAT Number', 'id' => 'vat_number']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Registered Address -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('registered_address', 'Registered Address:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter your business official registered address"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map-marker"></i>
                                    </span>
                                    {!! Form::text('registered_address', null, ['class' => 'form-control', 'placeholder' => 'Enter Registered Address', 'id' => 'registered_address']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Business Category -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('business_category', 'Business Category:') !!}
                                <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="top" title="Enter your business category based on official classification"></i>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-briefcase"></i>
                                    </span>
                                    {!! Form::text('business_category', null, ['class' => 'form-control', 'placeholder' => 'Enter Business Category', 'id' => 'business_category']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- CRN -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('crn', 'CRN:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-id-card"></i>
                                    </span>
                                    {!! Form::text('crn', null, ['class' => 'form-control', 'placeholder' => 'Enter CRN', 'id' => 'crn']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Street Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('street_name', 'Street Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-road"></i>
                                    </span>
                                    {!! Form::text('street_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Street Name', 'id' => 'street_name']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Building Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('building_number', 'Building Number:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-home"></i>
                                    </span>
                                    {!! Form::text('building_number', null, ['class' => 'form-control', 'placeholder' => 'Enter Building Number', 'id' => 'building_number']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Plot Identification -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('plot_identification', 'Plot Identification:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-map"></i>
                                    </span>
                                    {!! Form::text('plot_identification', null, ['class' => 'form-control', 'placeholder' => 'Enter Plot Identification', 'id' => 'plot_identification']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Sub Division Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('sub_division_name', 'Sub Division Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-location-arrow"></i>
                                    </span>
                                    {!! Form::text('sub_division_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Sub Division Name', 'id' => 'sub_division_name']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- City Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('city_name', 'City Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    {!! Form::text('city_name', null, ['class' => 'form-control', 'placeholder' => 'Enter City Name', 'id' => 'city_name']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Postal Number -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('postal_number', 'Postal Number:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope-o"></i>
                                    </span>
                                    {!! Form::text('postal_number', null, ['class' => 'form-control', 'placeholder' => 'Enter Postal Number', 'id' => 'postal_number']) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Country Name -->
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('country_name', 'Country Name:') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-globe"></i>
                                    </span>
                                    {!! Form::text('country_name', null, ['class' => 'form-control', 'placeholder' => 'Enter Country Name', 'id' => 'country_name']) !!}
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