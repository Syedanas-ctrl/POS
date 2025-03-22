<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZatcaController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display the ZATCA integration form
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if (!auth()->user()->can('zatca.settings')) {
        //     abort(403, 'Unauthorized action.');
        // }

        // $business_id = request()->session()->get('user.business_id');
        // $zatca_settings = DB::table('zatca_settings')
        //                     ->where('business_id', $business_id)
        //                     ->first();

        return view('zatca.onboarding');
                // ->with(compact('zatca_settings'));
    }

    /**
     * Save ZATCA integration settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveIntegration(Request $request)
    {
        if (!auth()->user()->can('zatca.settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');
            
            // Validate key inputs
            $request->validate([
                'portal_mode' => 'required',
                'otp' => 'required',
                'email' => 'required|email',
                'common_name' => 'required',
                'country_code' => 'required',
                'organization_unit_name' => 'required',
                'organization_name' => 'required',
                'egs_serial_number' => 'required',
                'vat_number' => 'required'
            ]);

            // Save settings to database
            $business_id = request()->session()->get('user.business_id');
            
            // Check if settings already exist for this business
            $zatca_settings = DB::table('zatca_settings')
                                ->where('business_id', $business_id)
                                ->first();
            
            if ($zatca_settings) {
                // Update existing settings
                DB::table('zatca_settings')
                    ->where('business_id', $business_id)
                    ->update([
                        'portal_mode' => $request->portal_mode,
                        'otp' => $request->otp,
                        'email' => $request->email,
                        'common_name' => $request->common_name,
                        'country_code' => $request->country_code,
                        'organization_unit_name' => $request->organization_unit_name,
                        'organization_name' => $request->organization_name,
                        'egs_serial_number' => $request->egs_serial_number,
                        'vat_name' => $request->vat_name,
                        'invoice_type' => $request->invoice_type,
                        'vat_number' => $request->vat_number,
                        'registered_address' => $request->registered_address,
                        'business_category' => $request->business_category,
                        'crn' => $request->crn,
                        'street_name' => $request->street_name,
                        'building_number' => $request->building_number,
                        'plot_identification' => $request->plot_identification,
                        'sub_division_name' => $request->sub_division_name,
                        'city_name' => $request->city_name,
                        'postal_number' => $request->postal_number,
                        'country_name' => $request->country_name,
                        'updated_at' => now()
                    ]);
            } else {
                // Insert new settings
                DB::table('zatca_settings')->insert([
                    'business_id' => $business_id,
                    'portal_mode' => $request->portal_mode,
                    'otp' => $request->otp,
                    'email' => $request->email,
                    'common_name' => $request->common_name,
                    'country_code' => $request->country_code,
                    'organization_unit_name' => $request->organization_unit_name,
                    'organization_name' => $request->organization_name,
                    'egs_serial_number' => $request->egs_serial_number,
                    'vat_name' => $request->vat_name,
                    'invoice_type' => $request->invoice_type,
                    'vat_number' => $request->vat_number,
                    'registered_address' => $request->registered_address,
                    'business_category' => $request->business_category,
                    'crn' => $request->crn,
                    'street_name' => $request->street_name,
                    'building_number' => $request->building_number,
                    'plot_identification' => $request->plot_identification,
                    'sub_division_name' => $request->sub_division_name,
                    'city_name' => $request->city_name,
                    'postal_number' => $request->postal_number,
                    'country_name' => $request->country_name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Log the action for audit purposes
            activity()
                ->performedOn($request->user())
                ->causedBy($request->user())
                ->withProperties(['inputs' => $input])
                ->log('ZATCA integration settings updated');
                
            $output = [
                'success' => true,
                'msg' => __('zatca.settings_updated_success')
            ];
            
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}