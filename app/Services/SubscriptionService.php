<?php

namespace App\Services;

use App\Repositories\AddonSubscription\AddonSubscriptionInterface;
use App\Repositories\Package\PackageInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\SubscriptionBill\SubscriptionBillInterface;
use App\Repositories\SubscriptionFeature\SubscriptionFeatureInterface;
use App\Repositories\User\UserInterface;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

class SubscriptionService {
    private UserInterface $user;
    private SubscriptionInterface $subscription;
    private PackageInterface $package;
    private SubscriptionFeatureInterface $subscriptionFeature;
    private CachingService $cache;
    private AddonSubscriptionInterface $addonSubscription;
    private StaffInterface $staff;
    private SubscriptionBillInterface $subscriptionBill;

    public function __construct(UserInterface $user, SubscriptionInterface $subscription, PackageInterface $package, SubscriptionFeatureInterface $subscriptionFeature, CachingService $cache, AddonSubscriptionInterface $addonSubscription, StaffInterface $staff, SubscriptionBillInterface $subscriptionBill) {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->package = $package;
        $this->subscriptionFeature = $subscriptionFeature;
        $this->cache = $cache;
        $this->addonSubscription = $addonSubscription;
        $this->staff = $staff;
        $this->subscriptionBill = $subscriptionBill;
    }


    /**
     * @param $package_id
     * @param $school_id
     * @param $isCurrentPlan
     
     * @return Model|null
     */
    public function createSubscription($package_id, $school_id = null, $subscription_id = null, $isCurrentPlan = null)
    {    
        // package_id => Create that package
        // school_id => if super admin can assign package, then school id is compulsory
        // subscription_id => if school admin already set upcoming plan update only that plan
        // isCurrentPlan => school admin can set current plan & upcoming plan also
        
        $settings = $this->cache->getSystemSettings();
        $package = $this->package->builder()->with('package_feature')->where('id', $package_id)->first();
        $end_date = '';
        if (!$school_id) {
            $school_id = Auth::user()->school_id;
        }
        if ($package->is_trial) {
            $end_date = Carbon::now()->addDays(($settings['trial_days']))->format('Y-m-d');
        } else {
            $end_date = Carbon::now()->addDays(($package->days - 1))->format('Y-m-d');
        }
        $start_date = Carbon::now()->format('Y-m-d');

        // If not current subscription plan
        if (!$isCurrentPlan) {
            $current_subscription = $this->subscription->default()->first();
            $start_date = Carbon::parse($current_subscription->end_date)->addDays()->format('Y-m-d');
            $end_date = Carbon::parse($start_date)->addDays(($package->days - 1))->format('Y-m-d');
        }
        $subscription_data = [
            'package_id'     => $package->id,
            'name'           => $package->name,
            'student_charge' => $package->student_charge,
            'staff_charge'   => $package->staff_charge,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'billing_cycle'  => $package->days,
            'school_id'      => $school_id
        ];

        // Check subscription update or create
        // If school has already set upcoming plan
        if ($subscription_id) {
            $subscription = $this->subscription->update($subscription_id, $subscription_data);
        } else {
            $subscription = $this->subscription->create($subscription_data);
        }
        

        // If current subscription plan then set package features
        if ($isCurrentPlan) {
            $subscription_features = array();
            foreach ($package->package_feature as $key => $feature) {
                $subscription_features[] = [
                    'subscription_id' => $subscription->id,
                    'feature_id'      => $feature->feature_id
                ];
            }
            $this->subscriptionFeature->upsert($subscription_features, ['subscription_id', 'feature_id'], ['subscription_id', 'feature_id']);
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'), $subscription->school_id);
        }
        return $subscription;
    }

    /**
     * @param $generateBill
     * @return Model|null
     */
    public function createSubscriptionBill($subscription, $generateBill = null)
    {
        // GenerateBill [ null => Generate immediate bill, 1 => Generate regular bill ]
        $students = $this->user->builder()->withTrashed()->where(function ($q) use ($subscription) {
            $q->whereBetween('deleted_at', [$subscription->start_date, $subscription->end_date]);
        })->orWhereNull('deleted_at')->role('Student')->where('school_id', $subscription->school_id)->count();

        $staffs = $this->staff->builder()->whereHas('user', function ($q) use ($subscription) {
            $q->where(function ($q) use ($subscription) {
                $q->withTrashed()->whereBetween('deleted_at', [$subscription->start_date, $subscription->end_date])
                    ->orWhereNull('deleted_at');
            })->where('school_id', $subscription->school_id);
        })->count();
        
        $today_date = Carbon::now()->format('Y-m-d');
        $start_date = Carbon::parse($subscription->start_date);
        if ($generateBill) {
            $usage_days = $start_date->diffInDays($subscription->end_date) + 1;
        } else {
            $usage_days = $start_date->diffInDays($today_date) + 1;
        }
        $bill_cycle_days = $subscription->billing_cycle;


        // Get addon total
        $addons = $this->addonSubscription->builder()->where('subscription_id',$subscription->id)->sum('price');

        $student_charges = number_format((($usage_days * $subscription->student_charge) / $bill_cycle_days), 4) * $students;
        $staff_charges = number_format((($usage_days * $subscription->staff_charge) / $bill_cycle_days), 4) * $staffs;
        
        $systemSettings = $this->cache->getSystemSettings();

        $subscription_bill = [
            'subscription_id' => $subscription->id,
            'amount'          => ($student_charges + $staff_charges + $addons),
            'total_student'   => $students,
            'total_staff'     => $staffs,
            'due_date'        => Carbon::now()->addDays($systemSettings['additional_billing_days'])->format('Y-m-d'),
            'school_id'       => $subscription->school_id
        ];
        // Create bill for active plan
        return $subscription_bill = $this->subscriptionBill->create($subscription_bill);
    }

    // Check subscription pending bills
    public function subscriptionPendingBill()
    {
        $subscriptionBill = $this->subscriptionBill->builder()->whereHas('transaction', function ($q) {
            $q->whereNot('payment_status', "succeed");
        })->orWhereNull('payment_transaction_id')->where('school_id', Auth::user()->school_id)->whereNot('amount', 0)
        ->doesnthave('subscription_bill_payment')
        ->first();
        return $subscriptionBill;
    }


    /**
     * @param $currency
     * @param $amount
     */
    public function checkMinimumAmount($currency, $amount)
    {
        $currencies = array(
            'USD' => 0.50,
            'AED' => 2.00,
            'AUD' => 0.50,
            'BGN' => 1.00,
            'BRL' => 0.50,
            'CAD' => 0.50,
            'CHF' => 0.50,
            'CZK' => 15.00,
            'DKK' => 2.50,
            'EUR' => 0.50,
            'GBP' => 0.30,
            'HKD' => 4.00,
            'HUF' => 175.00,
            'INR' => 0.50,
            'JPY' => 50,
            'MXN' => 10,
            'MYR' => 2.00,
            'NOK' => 3.00,
            'NZD' => 0.50,
            'PLN' => 2.00,
            'RON' => 2.00,
            'SEK' => 3.00,
            'SGD' => 0.50,
            'THB' => 10
        );
        if ($amount != 0) {
            if (array_key_exists($currency, $currencies)) {
                if ($currencies[$currency] >= $amount) {
                    return $currencies[$currency];
                } else {
                    return $amount;
                }
            } else {
                return $amount;
            }
        }
        return 0;
    }

}
