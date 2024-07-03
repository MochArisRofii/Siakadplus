<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionBillPayment;
use App\Repositories\AddonSubscription\AddonSubscriptionInterface;
use App\Repositories\SubscriptionBill\SubscriptionBillInterface;
use App\Repositories\SubscriptionBillPayment\SubscriptionBillPaymentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class SubscriptionBillPaymentController extends Controller
{
    private SubscriptionBillPaymentInterface $billPayment;
    private SubscriptionBillInterface $subscriptionBill;
    private AddonSubscriptionInterface $addonSubscription;
    private CachingService $cache;

    public function __construct(SubscriptionBillPaymentInterface $billPayment, SubscriptionBillInterface $subscriptionBill, AddonSubscriptionInterface $addonSubscription, CachingService $cache) {
        $this->billPayment = $billPayment;
        $this->subscriptionBill = $subscriptionBill;
        $this->addonSubscription = $addonSubscription;
        $this->cache = $cache;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');

        $subscriptionBill = $this->subscriptionBill->builder()->with('subscription','school','subscription_bill_payment')->where('id',$id)->first();
        $addons = $this->addonSubscription->builder()->where('subscription_id', $subscriptionBill->subscription_id)->with('feature')->onlyTrashed()->get()->append('days');

        $subscription = $subscriptionBill->subscription;
        
        $today_date = Carbon::now()->format('Y-m-d');
        $start_date = Carbon::parse($subscription->start_date);
        $usage_days = $start_date->diffInDays($subscription->end_date) + 1;
        $bill_cycle_days = $subscription->billing_cycle;

        $student_charges = number_format((($usage_days * $subscription->student_charge) / $bill_cycle_days), 4);
        $staff_charges = number_format((($usage_days * $subscription->staff_charge) / $bill_cycle_days), 4);
        
        $systemSettings = $this->cache->getSystemSettings();

        return view('subscription.subscription_bill',compact('subscriptionBill','addons','student_charges','staff_charges','systemSettings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
        $billPayment = $this->billPayment->builder()->where('subscription_bill_id',$id)->first();
        try {
            DB::beginTransaction();
            $billData = [
                'subscription_bill_id' => $id,
                'date' => Carbon::now()->format('Y-m-d'),
                'amount' => $request->amount,
                'payment_type' => $request->subscription_bill_payment['payment_type'],
                'cheque_number' => $request->subscription_bill_payment['payment_type'] == 'Cheque' ? $request->cheque_number : null,
                'school_id' => $request->school_id
            ];

            if ($billPayment) {
                $this->billPayment->update($billPayment->id,$billData);
            } else {
                $this->billPayment->create($billData);
            }
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully'); 
        } catch (\Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
        

        

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        ResponseService::noPermissionThenRedirect('subscription-bill-payment');
        try {
            $this->billPayment->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }
}
