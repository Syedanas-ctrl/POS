<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Zatca\Zatca;

class ZatcaController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;
    protected $zatca;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->zatca = new Zatca();
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

        $business_id = request()->session()->get('user.business_id');

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
        // dd($request->all());
        // if (!auth()->user()->can('zatca.settings')) {
        //     abort(403, 'Unauthorized action.');
        // }

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
            $csr = $this->zatca->getCsr($input);
            $csid = $this->zatca->getCSID([
                'portal_mode' => $input['portal_mode'],
                'otp' => $input['otp'],
                'csr' => $csr['certificate']
            ]);
            $prodCSID = $this->zatca->getProdCSID([
                'portal_mode' => $input['portal_mode'],
                'otp' => $input['otp'],
                'certificate' => base64_encode($csid['certificate']),
                'secret' => $csid['secret'],
                'requestId' => $csid['requestId']
            ]);
            DB::table('zatca_certificates')->insert([
                'business_location_id' => $input['business_location_id'],
                'csr' => $csr['certificate'],
                'private' => $csr['privateKey'],
                'csid_certificate' => $csid['certificate'],
                'csid_secret' => $csid['secret'],
                'csid_request_id' => $csid['requestId'],
                'csid_production_certificate' => $prodCSID['certificate'],
                'csid_production_secret' => $prodCSID['secret'],
                'csid_production_request_id' => $prodCSID['requestId']
            ]);
            
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