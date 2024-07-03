<?php

namespace App\Console\Commands;

use App\Models\AddonSubscription;
use App\Models\Package;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\SubscriptionBill;
use App\Models\SubscriptionFeature;
use App\Models\User;
use App\Models\UserStatusForNextCycle;
use App\Services\CachingService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SubscriptionBillCron extends Command
{
    private CachingService $cache;

    public function __construct(CachingService $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptionBill:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Delete current subscription plan if not cleared previously bills

        $get_subscription_ids_for_unclear_past_bill = array();
        $unclear_addon_soft_delete = array();

        $today_date = Carbon::now()->format('Y-m-d');
        $settings = app(CachingService::class)->getSystemSettings();
        // $extra_day = Carbon::parse($today_date)->addDays(($settings['additional_billing_days'] - 1))->format('Y-m-d');

        $subscriptionBill = SubscriptionBill::with('subscription')->whereHas('transaction', function($q) {
            $q->whereNot('payment_status',"succeed");
        })->orWhereNull('payment_transaction_id')->where('due_date','<',$today_date)->doesntHave('subscription_bill_payment')->get();

        $end_date = Carbon::yesterday()->format('Y-m-d');
        foreach ($subscriptionBill as $key => $bill) {

            $subscriptions = Subscription::where('school_id',$bill->school_id)->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->get();
            
            foreach ($subscriptions as $key => $subscription) {
                $end_paln = Subscription::find($subscription->id);
                $end_paln->end_date = $end_date;
                $end_paln->save();
                $get_subscription_ids_for_unclear_past_bill[] = $subscription->id;
            }

            // Delete upcoming plan if selected
            Subscription::where('school_id',$bill->school_id)->where('start_date','>',$today_date)->delete();
            

            $addon_subscriptions = AddonSubscription::where('school_id',$bill->school_id)->where('start_date','<=',$today_date)->where('end_date','>=',$today_date)->get();
            
            foreach ($addon_subscriptions as $key => $addon) {
                $addon_subscription = AddonSubscription::find($addon->id);
                $addon_subscription->end_date = $end_date;
                $addon_subscription->save();
                $unclear_addon_soft_delete[] = $addon->id;
            }

            
            // Delete upcoming plan if selected
            AddonSubscription::where('school_id',$bill->school_id)->where('start_date','>',$today_date)->delete();

            $school_settings = SchoolSetting::where('school_id',$bill->school_id)->where('name','auto_renewal_plan')->first();
            $school_settings->data = 0;
            $school_settings->save();

            // Remove cache
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'),$bill->school_id);
        }

        // End if not clear past bills


        // Bill Generation


        $today_date = Carbon::now()->format('Y-m-d');
        $today_date_without_format = Carbon::yesterday();
        $subscriptions = Subscription::whereDate('end_date', $today_date_without_format->format('Y-m-d'))
        ->doesnthave('subscription_bill')
        ->get();

        foreach ($subscriptions as $subscription) {

            $subscription_date = Carbon::createFromDate($subscription->end_date);
            //
            if ($today_date_without_format->isSameDay($subscription_date)) {

                // Create subscription bill
                $subscriptionBillData = app(SubscriptionService::class)->createSubscriptionBill($subscription, 1);

                // Delete addon
                $addons = AddonSubscription::where('school_id', $subscription->school_id)->where('end_date', $subscription->end_date)->get();
                $soft_delete_addon_ids = array();
                foreach ($addons as $addon) {
                    $soft_delete_addon_ids[] = $addon->id;
                }

                // Delete subscription features
                SubscriptionFeature::where('subscription_id',$subscription->id)->delete();
                

                // Check auto-renew plan is enabled
                $auto_renewal_plan = SchoolSetting::where('name', 'auto_renewal_plan')->where('data', 1)->where('school_id', $subscription->school_id)->first();
                if ($auto_renewal_plan) {
                    $check_subscription = Subscription::whereDate('start_date', '<=', $today_date)->whereDate('end_date', '>=', $today_date)->where('school_id', $subscription->school_id)->first();

                    // If already change plan for next billing cycle or not
                    if (!$check_subscription) {
                        // Not set, add previous subscription and addons
                        $previous_subscription = Subscription::where('school_id', $subscription->school_id)->orderBy('end_date', 'DESC')->whereHas('package',function($q) {
                            $q->where('is_trial',0);
                        })->first();

                        // Check free trial package or not
                        if ($previous_subscription) {
                            // Create subscription plan
                            $new_subscription_plan = app(SubscriptionService::class)->createSubscription($previous_subscription->package_id, $previous_subscription->school_id, null, 1);
                            
                            // Check addons
                            $addons = AddonSubscription::where('school_id',$subscription->school_id)->where('subscription_id',$subscription->id)->where('status',1)->get();
                            $addons_data = array();
                            foreach ($addons as $addon) {
                                $addons_data[] = [
                                    'school_id' => $subscription->school_id,
                                    'feature_id' => $addon->feature_id,
                                    'price' => $addon->addon->price,
                                    'start_date' => $today_date,
                                    'end_date' => $new_subscription_plan->end_date,
                                    'status' => 1,
                                    'subscription_id' => $new_subscription_plan->id,
                                ];
                            }
                            AddonSubscription::upsert($addons_data,['school_id','feature_id','end_date'],['price','start_date','status','subscription_id']);
                        }

                    } else {
                        Log::info('Else parts');
                        // Already set plan, update charges in subscription table

                        // Create subscription plan
                        $update_subscription = app(SubscriptionService::class)->createSubscription($check_subscription->package_id, $check_subscription->school_id, $check_subscription->id, 1);

                        $addons = AddonSubscription::where('school_id',$subscription->school_id)->where('subscription_id',$subscription->id)->where('status',1)->get();

                        $update_addons = array();
                        foreach ($addons as $addon) {
                            $update_addons[] = [
                                'school_id' => $subscription->school_id,
                                'feature_id' => $addon->feature_id,
                                'price' => $addon->addon->price,
                                'start_date' => $update_subscription->start_date,
                                'end_date' => $update_subscription->end_date,
                                'status' => 1,
                                'subscription_id'     => $update_subscription->id,
                            ];
                        }

                        AddonSubscription::upsert($update_addons, ['school_id', 'feature_id', 'end_date'], ['price', 'start_date', 'status','subscription_id']);
                    }

                    AddonSubscription::whereIn('id',$soft_delete_addon_ids)->delete();

                    // Enable / Disable user for next bill cylce
                    $user_status = UserStatusForNextCycle::where('school_id',$subscription->school_id)->get();

                    $yesterday_date = Carbon::yesterday()->toDateTimeString();
                    $enable_user = array();
                    $disable_user = array();
                    foreach ($user_status as $key => $status) {
                        if ($status->status == 1) {
                            $enable_user[] = $status->user_id;
                        } else {
                            $disable_user[] = $status->user_id;
                        }
                    }

                    // Enable / disable user for upcoming billing cycle
                    if (count($enable_user)) {
                        User::whereIn('id',$enable_user)->withTrashed()->update(['deleted_at' => null, 'status' => 1]);
                    }
                    if (count($disable_user)) {
                        User::whereIn('id',$disable_user)->withTrashed()->update(['deleted_at' => $yesterday_date, 'status' => 0]);
                    }
                    UserStatusForNextCycle::where('school_id',$subscription->school_id)->delete();
                }
            }

            // Unclear bill soft-delete addon
            AddonSubscription::whereIn('id',$unclear_addon_soft_delete)->delete();

            // Remove cache
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'),$subscription->school_id);
        }        

        Log::info("Cron is working fine!");
        return CommandAlias::SUCCESS;
    }
}
