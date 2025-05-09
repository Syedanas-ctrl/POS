<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Zatca\Zatca;
use App\BusinessLocation;
use App\User;
use App\Contact;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Yajra\DataTables\Facades\DataTables;
use App\Business;
use function Sabre\Uri\split;
class ZatcaController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;
    protected $zatca;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->zatca = new Zatca();
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
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
        $business_locations = BusinessLocation::where('business_id', $business_id)->get();
        $business = Business::find($business_id);
        $zatca_certificates = DB::table('zatca_certificates')
            ->whereIn('business_location_id', $business_locations->pluck('id'))
            ->get();

        return view('zatca.onboarding')
            ->with(compact('business_locations', 'business', 'zatca_certificates'));
    }

    /**
     * Save ZATCA integration settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveIntegration(Request $request, $id)
    {
        // dd($request->all());
        // if (!auth()->user()->can('zatca.settings')) {
        //     abort(403, 'Unauthorized action.');
        // }

        try {
            // Get business location id from query url
            $business_id = request()->session()->get('user.business_id');
            $input = $request->except('_token');
            // Save settings to database
            $business_location_data = [
                "business_category" => $input['business_category'],
                "name" => $input['organization_unit_name'],
                "egs_serial_number" => $input['egs_serial_number'],
                'registered_address' => $input['registered_address'],
                'street' => $input['street_name'],
                'building_number' => $input['building_number'],
                'plot_number' => $input['plot_identification'],
                'sub_division_name' => $input['sub_division_name'],
                'zip_code' => $input['postal_number'],
                'city' => $input['city_name'],
                'country' => $input['country_name'],
            ];
            $business_data = [
                "tax_number_1" => $input["vat_number"],
                "tax_label_1" => $input["vat_name"],
                "tax_number_2" => $input["crn"],
                "tax_label_2" => 'CRN',
            ];
            DB::beginTransaction();
            BusinessLocation::where('business_id', $business_id)
                ->where('id', $id)
                ->update($business_location_data);

            Business::where('id', $business_id)
                ->update($business_data);
            $business = Business::find($business_id);
            
            $address = $business_location_data['registered_address'] . ', ' . $business_location_data['street'] . ', ' . $business_location_data['building_number'] . ', ' . $business_location_data['plot_number'] . ', ' . $business_location_data['sub_division_name'] . ', ' . $business_location_data['zip_code'] . ', ' . $business_location_data['city'] . ', ' . $business_location_data['country'];
            $data = [
                "portal_mode" => $input['portal_mode'],
                "otp" => $input['otp'],
                "common_name" => $business->name,
                "country_code" => "SA",
                "organization_unit_name" => $input['organization_unit_name'],
                "organization_name" => $business->name,
                "egs_serial_number" => $input['egs_serial_number'],
                "vat_number" => $input["vat_number"],
                "business_category" => $input['business_category'],
                "crn" => $input["crn"],
                "address" => $address,
            ];
            // dd($data);
            $csr = $this->zatca->getCsr($data);
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
                'business_location_id' => $id,
                'csr' => $csr['certificate'],
                'private' => $csr['privateKey'],
                'csid_certificate' => $csid['certificate'],
                'csid_secret' => $csid['secret'],
                'csid_request_id' => $csid['requestId'],
                'csid_production_certificate' => $prodCSID['certificate'],
                'csid_production_secret' => $prodCSID['secret'],
                'csid_production_request_id' => $prodCSID['requestId']
            ]);
            DB::commit();
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
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            DB::rollBack();
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function sync($id)
    {

        $sell = Transaction::find($id);
        $certificate = \App\ZatcaCertificate::getActiveCertificate($sell->location_id);
        // Report the invoice
        $report = $this->zatca->report([
            'portal_mode' => 'sandbox',
            'certificate' => base64_encode($certificate->csid_production_certificate),
            'secret' => $certificate->csid_production_secret,
            'signedXmlInvoice' => $sell->zatca_xml,
            'invoiceHash' => $sell->zatca_hash,
            'uuid' => $sell->zatca_uuid
        ]);

        if ($report['status'] == 'success') {
            $sell->zatca_status = 'REPORTED';
            $sell->save();
            return redirect()->back()->with(['status' => ['success' => true, 'msg' => __('messages.synced_successfully')]]);
        } else {
            return redirect()->back()->with(['status' => ['success' => false, 'msg' => __('messages.something_went_wrong')]]);
        }
    }

    /**
     * Display the ZATCA dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $business_id = request()->session()->get('user.business_id');

        // Get statistics from database
        // $stats = DB::table('zatca_certificates')
        //     ->where('business_location_id', $business_id)
        //     ->select(
        //         DB::raw('COUNT(*) as total_invoices'),
        //         DB::raw('SUM(CASE WHEN sync_status = "pending" THEN 1 ELSE 0 END) as total_unsynced'),
        //         DB::raw('SUM(CASE WHEN sync_status = "synced" THEN 1 ELSE 0 END) as total_synced'),
        //         DB::raw('SUM(CASE WHEN sync_status = "success" THEN 1 ELSE 0 END) as total_successfully_synced'),
        //         DB::raw('SUM(CASE WHEN sync_status = "failed" THEN 1 ELSE 0 END) as total_failed_synced')
        //     )
        //     ->first();

        return view('zatca.dashboard');
        // ->with([
        //     'totalInvoices' => $stats->total_invoices ?? 0,
        //     'totalUnsynced' => $stats->total_unsynced ?? 0,
        //     'totalSynced' => $stats->total_synced ?? 0,
        //     'totalSuccessfullySynced' => $stats->total_successfully_synced ?? 0,
        //     'totalFailedSynced' => $stats->total_failed_synced ?? 0
        // ]);
    }

    /**
     * Display the ZATCA sells
     *
     * @return \Illuminate\Http\Response
     */
    public function sells()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'so.view_all', 'so.view_own'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();

            $sale_type = !empty(request()->input('sale_type')) ? request()->input('sale_type') : 'sell';

            $sells = $this->transactionUtil->getListSells($business_id, $sale_type);
            // only display sell invoice we add it because project invoive show in sell list
            if ($sale_type == 'sell') {
                $sells->where(function ($query) {
                    $query->where('transactions.sub_type', '!=', 'project_invoice')
                        ->orWhereNull('transactions.sub_type');
                });
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            $partial_permissions = ['view_own_sell_only', 'view_commission_agent_sell', 'access_own_shipping', 'access_commission_agent_shipping'];
            if (!auth()->user()->can('direct_sell.view')) {
                $sells->where(function ($q) {
                    if (auth()->user()->hasAnyPermission(['view_own_sell_only', 'access_own_shipping'])) {
                        $q->where('transactions.created_by', request()->session()->get('user.id'));
                    }

                    //if user is commission agent display only assigned sells
                    if (auth()->user()->hasAnyPermission(['view_commission_agent_sell', 'access_commission_agent_shipping'])) {
                        $q->orWhere('transactions.commission_agent', request()->session()->get('user.id'));
                    }
                });
            }

            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments) {
                $sells->whereNotNull('transactions.shipping_status');

                if (auth()->user()->hasAnyPermission(['access_pending_shipments_only'])) {
                    $sells->where('transactions.shipping_status', '!=', 'delivered');
                }
            }

            if (!$is_admin && !$only_shipments && $sale_type != 'sales_order') {
                $payment_status_arr = [];
                if (auth()->user()->can('view_paid_sells_only')) {
                    $payment_status_arr[] = 'paid';
                }

                if (auth()->user()->can('view_due_sells_only')) {
                    $payment_status_arr[] = 'due';
                }

                if (auth()->user()->can('view_partial_sells_only')) {
                    $payment_status_arr[] = 'partial';
                }

                if (empty($payment_status_arr)) {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->OverDue();
                    }
                } else {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->where(function ($q) use ($payment_status_arr) {
                            $q->whereIn('transactions.payment_status', $payment_status_arr)
                                ->orWhere(function ($qr) {
                                    $qr->OverDue();
                                });
                        });
                    } else {
                        $sells->whereIn('transactions.payment_status', $payment_status_arr);
                    }
                }
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if (!empty(request()->input('source'))) {
                //only exception for woocommerce
                if (request()->input('source') == 'woocommerce') {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                } else {
                    $sells->where('transactions.source', request()->input('source'));
                }
            }

            if ($is_crm) {
                $sells->addSelect('transactions.crm_is_order_request');

                if (request()->has('crm_is_order_request')) {
                    $sells->where('transactions.crm_is_order_request', 1);
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('status'))) {
                $sells->where('transactions.status', request()->input('status'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }

            $only_pending_shipments = request()->only_pending_shipments == 'true' ? true : false;
            if ($only_pending_shipments) {
                $sells->where('transactions.shipping_status', '!=', 'delivered')
                    ->whereNotNull('transactions.shipping_status');
                $only_shipments = true;
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            if (!empty(request()->input('for_dashboard_sales_order'))) {
                $sells->whereIn('transactions.status', ['partial', 'ordered'])
                    ->orHavingRaw('so_qty_remaining > 0');
            }

            if ($sale_type == 'sales_order') {
                if (!auth()->user()->can('so.view_all') && auth()->user()->can('so.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            }

            if (!empty(request()->input('delivery_person'))) {
                $sells->where('transactions.delivery_person', request()->input('delivery_person'));
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';

            if (!empty($with)) {
                foreach ($with as $relation) {
                    if ($relation == 'payment_lines' && !empty(request()->input('payment_method'))) {
                        $sells->whereHas($relation, function ($query) {
                            $query->where('method', request()->input('payment_method'));
                        });
                    } else {
                        $sells->with($relation);
                    }
                }
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }
            $sales_order_statuses = Transaction::sales_order_statuses();
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments, $is_admin, $sale_type) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" 
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __('messages.actions') .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view') || auth()->user()->can('view_own_sell_only')) {
                            $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '</a></li>';
                        }
                        if (!$only_shipments) {
                        }

                        if (config('constants.enable_download_pdf') && auth()->user()->can('print_invoice') && $sale_type != 'sales_order') {
                            $html .= '<li><a href="' . route('sell.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __('lang_v1.download_pdf') . '</a></li>';

                            if (!empty($row->shipping_status)) {
                                $html .= '<li><a href="' . route('packing.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __('lang_v1.download_paking_pdf') . '</a></li>';
                            }
                        }

                        if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.access')) {
                            if (!empty($row->document)) {
                                $document_name = !empty(explode('_', $row->document, 2)[1]) ? explode('_', $row->document, 2)[1] : $row->document;
                                $html .= '<li><a href="' . url('uploads/documents/' . $row->document) . '" download="' . $document_name . '"><i class="fas fa-download" aria-hidden="true"></i>' . __('purchase.download_document') . '</a></li>';
                                if (isFileImage($document_name)) {
                                    $html .= '<li><a href="#" data-href="' . url('uploads/documents/' . $row->document) . '" class="view_uploaded_document"><i class="fas fa-image" aria-hidden="true"></i>' . __('lang_v1.view_document') . '</a></li>';
                                }
                            }
                        }

                        if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
                            $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\SellController::class, 'editShipping'], [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . __('lang_v1.edit_shipping') . '</a></li>';
                        }

                        if ($row->type == 'sell') {
                            if (auth()->user()->can('print_invoice')) {
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __('lang_v1.print_invoice') . '</a></li>
                                    <li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '?package_slip=true"><i class="fas fa-file-alt" aria-hidden="true"></i> ' . __('lang_v1.packing_slip') . '</a></li>';

                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '?delivery_note=true"><i class="fas fa-file-alt" aria-hidden="true"></i> ' . __('lang_v1.delivery_note') . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>';
                            if (!$only_shipments) {
                                if (
                                    $row->is_direct_sale == 0 && !auth()->user()->can('sell.update') &&
                                    auth()->user()->can('edit_pos_payment')
                                ) {
                                    $html .= '<li><a href="' . route('edit-pos-payment', [$row->id]) . '" 
                                    ><i class="fas fa-money-bill-alt"></i> ' . __('lang_v1.add_edit_payment') .
                                        '</a></li>';
                                }

                                if (
                                    auth()->user()->can('sell.payments') ||
                                    auth()->user()->can('edit_sell_payment') ||
                                    auth()->user()->can('delete_sell_payment')
                                ) {
                                    if ($row->payment_status != 'paid') {
                                        $html .= '<li><a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'addPayment'], [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __('purchase.add_payment') . '</a></li>';
                                    }

                                    $html .= '<li><a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __('purchase.view_payments') . '</a></li>';
                                }

                                if (auth()->user()->can('sell.create') || auth()->user()->can('direct_sell.access')) {
                                    // $html .= '<li><a href="' . action([\App\Http\Controllers\SellController::class, 'duplicateSell'], [$row->id]) . '"><i class="fas fa-copy"></i> ' . __("lang_v1.duplicate_sell") . '</a></li>';
        
                                    $html .= '<li><a href="' . action([\App\Http\Controllers\SellReturnController::class, 'add'], [$row->id]) . '"><i class="fas fa-undo"></i> ' . __('lang_v1.sell_return') . '</a></li>

                                    <li><a href="' . action([\App\Http\Controllers\SellPosController::class, 'showInvoiceUrl'], [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i> ' . __('lang_v1.view_invoice_url') . '</a></li>';
                                }
                            }

                            $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\NotificationController::class, 'getTemplate'], ['transaction_id' => $row->id, 'template_for' => 'new_sale']) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __('lang_v1.new_sale_notification') . '</a></li>';
                        } else {
                            $html .= '<li><a href="#" data-href="' . action([\App\Http\Controllers\SellController::class, 'viewMedia'], ['model_id' => $row->id, 'model_type' => \App\Transaction::class, 'model_media_type' => 'shipping_document']) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-paperclip" aria-hidden="true"></i>' . __('lang_v1.shipping_documents') . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->addColumn(
                    'zatca_action',
                    function ($row) use ($only_shipments, $is_admin, $sale_type) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-info tw-w-max dropdown-toggle" 
                                        data-toggle="dropdown" aria-expanded="false">'.
                                        __('messages.actions').
                                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if ($row->zatca_status != 'REPORTED' && $row->type == 'sell') {
                            $html .= '<li><a href="#" data-href="'.route('zatca.sync', [$row->id]).'" target="_blank" class="btn-modal" data-container=".view_modal"><i class="fas fa-sync" aria-hidden="true"></i> Sync invoice</a></li>';
                        }
                        if ($row->zatca_status == 'REPORTED') {
                            $html .= '<li><a href="#" class="btn-modal" data-container=".view_modal"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Download Xml</a></li>';
                        }
                        $html .= '</ul></div>'; 

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="final-total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="total-tax" data-orig-value="{{$tax_amount}}">@format_currency($tax_amount)</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="total_before_tax" data-orig-value="{{$total_before_tax}}">@format_currency($total_before_tax)</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);

                        return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
                    }
                )
                ->editColumn(
                    'types_of_service_name',
                    '<span class="service-type-label" data-orig-value="{{$types_of_service_name}}" data-status-name="{{$types_of_service_name}}">{{$types_of_service_name}}</span>'
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid;
                    $total_remaining_html = '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . $this->transactionUtil->num_f($total_remaining, true) . '</span>';

                    return $total_remaining_html;
                })
                ->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="sell_return_due" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                    }

                    return $return_due_html;
                })
                ->editColumn('invoice_no', function ($row) use ($is_crm) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->is_export)) {
                        $invoice_no .= '</br><small class="label label-default no-print" title="' . __('lang_v1.export') . '">' . __('lang_v1.export') . '</small>';
                    }

                    if ($is_crm && !empty($row->crm_is_order_request)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('crm::lang.order_request') . '"><i class="fas fa-tasks"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action([\App\Http\Controllers\SellController::class, 'editShipping'], [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color . '">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                            ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('payment_methods', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]] ?? '';
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';

                    return $html;
                })
                ->editColumn('status', function ($row) use ($sales_order_statuses, $is_admin) {
                    $status = '';

                    if ($row->type == 'sales_order') {
                        if ($is_admin && $row->status != 'completed') {
                            $status = '<span class="edit-so-status label ' . $sales_order_statuses[$row->status]['class'] . '" data-href="' . action([\App\Http\Controllers\SalesOrderController::class, 'getEditSalesOrderStatus'], ['id' => $row->id]) . '">' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        } else {
                            $status = '<span class="label ' . $sales_order_statuses[$row->status]['class'] . '" >' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        }
                    }

                    return $status;
                })
                ->editColumn('so_qty_remaining', '{{@format_quantity($so_qty_remaining)}}')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can('sell.view') || auth()->user()->can('view_own_sell_only')) {
                            return action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]);
                        } else {
                            return '';
                        }
                    },
                ]);

            $rawColumns = ['final_total', 'action', 'zatca_action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'conatct_name', 'status'];
            
            // Process datatable with raw columns
            $datatable = $datatable->rawColumns($rawColumns);
            
            // Create a base query for counts that matches what datatable displays
            // We need to ensure the count logic matches exactly what datatable is displaying
            $baseCountQuery = clone $sells;
            
            // The groupBy ensures we're counting distinct transactions only
            // This is important because the joins might create duplicate rows
            if (!$baseCountQuery->getQuery()->groups) {
                $baseCountQuery->groupBy('transactions.id');
            }
            
            // Get total invoices count with groupBy to match datatable display
            $total_invoices = $baseCountQuery->get()->count();
            
            // Clone the base query again for different counts to avoid stacking conditions
            $syncedCountQuery = clone $baseCountQuery;
            $unsyncedCountQuery = clone $baseCountQuery;
            
            // Get synced invoices count
            $synced_invoices = $syncedCountQuery->where('transactions.zatca_status', 'REPORTED')->get()->count();
            
            // Get unsynced invoices count
            $unsynced_invoices = $unsyncedCountQuery->where(function($query) {
                $query->whereNull('transactions.zatca_status')
                    ->orWhere('transactions.zatca_status', '!=', 'REPORTED');
            })->get()->count();
            
            return $datatable
                ->with([
                    'recordsTotal' => $total_invoices, 
                    'total_invoices' => $total_invoices, 
                    'synced_invoices' => $synced_invoices, 
                    'unsynced_invoices' => $unsynced_invoices
                ])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $sources = $this->transactionUtil->getSources($business_id);
        if ($is_woocommerce) {
            $sources['woocommerce'] = 'Woocommerce';
        }

        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

        return view('zatca.sells')
            ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses', 'sources', 'payment_types'));
    }
}