<?php

namespace App\Http\Controllers;

use App\Models\AddonSubscription;
use App\Models\Package;
use App\Models\PaymentConfiguration;
use App\Models\SchoolSetting;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\SubscriptionBill;
use App\Models\SubscriptionFeature;
use App\Models\User;
use App\Repositories\AddonSubscription\AddonSubscriptionInterface;
use App\Repositories\Feature\FeatureInterface;
use App\Repositories\Package\PackageInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\School\SchoolInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\SubscriptionBill\SubscriptionBillInterface;
use App\Repositories\SubscriptionFeature\SubscriptionFeatureInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FeaturesService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use App\Services\SubscriptionService;
use Stripe\Stripe;
use Stripe\StripeClient;
use Throwable;

class SubscriptionController extends Controller
{
    private PackageInterface $package;
    private FeatureInterface $feature;
    private SubscriptionInterface $subscription;
    private AddonSubscriptionInterface $addonSubscription;
    private UserInterface $user;
    private SchoolSettingInterface $schoolSettings;
    private SubscriptionBillInterface $subscriptionBill;
    private StaffInterface $staff;
    private PaymentTransactionInterface $paymentTransaction;
    private SchoolInterface $school;
    private SchoolSettingInterface $schoolSetting;
    private CachingService $cache;
    private SubscriptionFeatureInterface $subscriptionFeature;
    private PaymentConfigurationInterface $paymentConfiguration;
    private SubscriptionService $subscriptionService;


    public function __construct(PackageInterface $package, FeatureInterface $feature, SubscriptionInterface $subscription, AddonSubscriptionInterface $addonSubscription, UserInterface $user, SchoolSettingInterface $schoolSettings, StaffInterface $staff, SubscriptionBillInterface $subscriptionBill, PaymentTransactionInterface $paymentTransaction, SchoolInterface $school, SchoolSettingInterface $schoolSetting, CachingService $cachingService, SubscriptionFeatureInterface $subscriptionFeature, PaymentConfigurationInterface $paymentConfiguration, SubscriptionService $subscriptionService)
    {
        $this->package = $package;
        $this->feature = $feature;
        $this->subscription = $subscription;
        $this->addonSubscription = $addonSubscription;
        $this->user = $user;
        $this->schoolSettings = $schoolSettings;
        $this->subscriptionBill = $subscriptionBill;
        $this->staff = $staff;
        $this->paymentTransaction = $paymentTransaction;
        $this->school = $school;
        $this->schoolSetting = $schoolSetting;
        $this->cache = $cachingService;
        $this->subscriptionFeature = $subscriptionFeature;
        $this->paymentConfiguration = $paymentConfiguration;
        $this->subscriptionService = $subscriptionService;
    }

    public function index()
    {
        ResponseService::noRoleThenRedirect('School Admin');
        $today_date = Carbon::now()->format('Y-m-d');
        $current_plan = $this->subscription->builder()->where('start_date', '<=', $today_date)->where('end_date', '>=', $today_date)->doesntHave('subscription_bill')->first();

        if (isset($current_plan) && count($current_plan->get())) {
            $packages = $this->package->builder()->with('package_feature.feature')->where('status', 1)->orderBy('rank', 'ASC')->where('is_trial', 0)->get();
        } else {
            $subscription = $this->subscription->builder()->get();
            if (count($subscription)) {
                $packages = $this->package->builder()->with('package_feature.feature')->where('status', 1)->orderBy('rank', 'ASC')->where('is_trial', 0)->get();
            } else {
                $packages = $this->package->builder()->with('package_feature.feature')->where('status', 1)->orderBy('rank', 'ASC')->get();
            }
        }

        $features = $this->feature->builder()->get();
        $settings = app(CachingService::class)->getSystemSettings();

        return view('subscription.index', compact('packages', 'features', 'current_plan', 'settings'));
    }


    public function store(Request $request)
    {

        try {
            $settings = app(CachingService::class)->getSystemSettings();
            $subscriptionBill = $this->subscriptionBill->findById($request->id);

            // Access the model directly via data for super admin data, use the interface builder for school-specific data.
            $paymentConfiguration = PaymentConfiguration::where('school_id', null)->first();
            if ($paymentConfiguration && !$paymentConfiguration->status) {
                return redirect()->back()->with('error', trans('Current stripe payment not available'));
            }
            $stripe_secret_key = $paymentConfiguration->secret_key ?? null;
            if (empty($stripe_secret_key)) {
                return redirect()->back()->with('error', trans('No API key provided'));
            }
            $amount = number_format(ceil($subscriptionBill->amount * 100) / 100, 2);
            $currency = $paymentConfiguration->currency_code;

            $checkAmount = $this->subscriptionService->checkMinimumAmount(strtoupper($currency), $amount);
            // if (!$checkAmount) {
            //     return redirect()->back()->with('error', trans('server_not_responding'));
            // }

            Stripe::setApiKey($stripe_secret_key);
            $session = StripeSession::create([
                'line_items'  => [
                    [
                        'price_data' => [
                            'currency'     => $currency,
                            'product_data' => [
                                'name'   => $subscriptionBill->subscription->name,
                                'images' => [$settings['horizontal_logo'] ?? 'logo.svg'],
                            ],
                            'unit_amount'  => $checkAmount * 100,
                        ],
                        'quantity'   => 1,
                    ],
                ],
                'mode'        => 'payment',
                'success_url' => url('subscriptions/payment/success') . '/{CHECKOUT_SESSION_ID}' . '/' . $request->id,
                'cancel_url'  => url('subscriptions/payment/cancel'),
            ]);
            return redirect()->away($session->url);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('server_not_responding'));
        }
    }

    public function plan($id)
    {
        if (env('DEMO_MODE')) {
            return response()->json(array(
                'error'   => true,
                'message' => "This is not allowed in the Demo Version.",
                'code'    => 112
            ));
        }
        // Store subscription plan
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            DB::beginTransaction();

            // Check pending bills
            $subscriptionBill = $this->subscriptionService->subscriptionPendingBill();

            if ($subscriptionBill) {
                ResponseService::errorResponse('Kindly settle any outstanding payments from before');
            }

            $package_id = $id;
            $subscription = $this->subscription->default()->with('package')->first();

            // Check current active subscription
            if ($subscription) {
                // Check trial package
                if ($subscription->package->is_trial == 1) {
                    $data = [
                        'package_id' => $package_id,
                        'plan'       => 'Trial'
                    ];
                } else {
                    $data = [
                        'package_id' => $package_id,
                        'plan'       => 'Regular'
                    ];
                }

                $response = [
                    'error'   => false,
                    'message' => trans('data_fetch_successfully'),
                    'data'    => $data,
                ];
                return response()->json($response);
            }

            $subscription = $this->subscriptionService->createSubscription($package_id, null, null, 1);
            
            DB::commit();
            ResponseService::successResponse(trans('Package Subscription Successfully'));
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Plan method');
            ResponseService::errorResponse();
        }
    }

    public function show()
    {
        ResponseService::noRoleThenRedirect('School Admin');

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = $_GET['search'];
        $paymentConfiguration = PaymentConfiguration::where('school_id', null)->first();
        $currency = $paymentConfiguration->currency_code;

        $sql = $this->subscriptionBill->builder()->with('subscription_bill_payment','transaction','school')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")
                        ->orwhere('description', 'LIKE', "%$search%")
                        ->orwhere('amount', 'LIKE', "%$search%")
                        ->orwhere('total_student', 'LIKE', "%$search%")
                        ->orwhere('total_staff', 'LIKE', "%$search%")
                        ->orwhere('due_date', 'LIKE', "%$search%")
                        ->orWhereHas('subscription', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        })
                        ->Owner();
                });
            });

        $total = $sql->count();

        $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        $settings = app(CachingService::class)->getSystemSettings();
        foreach ($res as $row) {

            $payment_status = $transaction_id = null;

            $operate = BootstrapTableService::button('fa fa-dollar', '#', ['btn', 'btn-xs', 'btn-gradient-success', 'btn-rounded', 'btn-icon', 'edit-data'], ['data-id' => $row->id, 'title' => 'Pay Bill', "data-toggle" => "modal", "data-target" => "#editModal"]);
            $operate .= BootstrapTableService::button('fa fa-file-pdf-o', url('subscriptions/bill/receipt', $row->id), ['btn-gradient-info'], ['title' => 'Receipt', 'target' => '_blank']);

            if (isset($row->transaction)) {
                $payment_status = $row->transaction->payment_status;
                $transaction_id = $row->transaction->order_id;
            }
            if ($row->subscription_bill_payment) {
                $payment_status = 'succeed';
            }
            $addons = $this->addonSubscription->builder()->where('subscription_id', $row->subscription_id)->with('feature')->withTrashed()->get()->append('days');

            $amount = number_format(ceil($row->amount * 100) / 100, 2);
            $tempRow['no'] = $no++;
            $tempRow['id'] = $row->id;
            $tempRow['date'] = Carbon::parse($row->subscription->end_date)->addDay()->format('Y-m-d');
            $tempRow['due_date'] = $row->due_date;
            $tempRow['name'] = $row->subscription->name;
            $tempRow['description'] = $row->description;
            $tempRow['total_student'] = $row->total_student;
            $tempRow['total_staff'] = $row->total_staff;
            $tempRow['amount'] = $amount;
            $tempRow['subscription'] = $row->subscription;
            $tempRow['addons'] = $addons;
            $tempRow['payment_status'] = $payment_status;
            $tempRow['transaction_id'] = $transaction_id;
            $tempRow['currency_symbol'] = $settings['currency_symbol'] ?? '';

            $tempRow['total_days'] = $settings['billing_cycle_in_days'];
            $start_date = Carbon::parse($row->subscription->start_date);
            $end_date = Carbon::parse($row->subscription->end_date);
            $tempRow['usage_days'] = $start_date->diffInDays($end_date) + 1;
            $tempRow['default_amount'] = $this->subscriptionService->checkMinimumAmount(strtoupper($currency), $amount);


            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function history()
    {
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            $data = [
                'students' => 0,
                'staffs'   => 0
            ];
            $active_package = $this->subscription->default()->with('subscription_feature.feature', 'package.package_feature.feature')->first();
            $addons = $this->addonSubscription->default()->with('feature')->get()->append('days');
            $upcoming_package = '';
            if ($active_package) {
                $upcoming_package = $this->subscription->builder()->with('package.package_feature.feature')->whereDate('start_date', '>=', $active_package->end_date)->first();
                if (!$upcoming_package) {
                    /*TODO : this logic is problematic here*/
                    $upcoming_package = $active_package;
                }

                $students = $this->user->builder()->withTrashed()->where(function ($q) use ($active_package) {
                    $q->whereBetween('deleted_at', [$active_package->start_date, $active_package->end_date]);
                })->orWhereNull('deleted_at')->Owner()->role('Student')->count();

                $staffs = $this->staff->builder()->whereHas('user', function ($q) use ($active_package) {
                    $q->where(function ($q) use ($active_package) {
                        $q->withTrashed()->whereBetween('deleted_at', [$active_package->start_date, $active_package->end_date])
                            ->orWhereNull('deleted_at');
                    })->Owner();
                })->count();

                $data = [
                    'students' => $students,
                    'staffs'   => $staffs
                ];
            }
            $system_settings = app(CachingService::class)->getSystemSettings()->toArray();
            $school_settings = app(CachingService::class)->getSchoolSettings()->toArray();
            $system_settings['currency_symbol'] = $system_settings['currency_symbol'] ?? '';
            $features = FeaturesService::getFeatures();

            return view('subscription.subscription', compact('active_package', 'addons', 'upcoming_package', 'data', 'school_settings', 'system_settings', 'features'));
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'Subscription Controller -> History method');
            ResponseService::errorResponse();
        }
    }

    public function cancel_upcoming($id = null)
    {
        if (env('DEMO_MODE')) {
            return response()->json(array(
                'error'   => true,
                'message' => "This is not allowed in the Demo Version.",
                'code'    => 112
            ));
        }
        ResponseService::noRoleThenRedirect('School Admin');
        try {

            if ($id) {
                $subscription = $this->subscription->findById($id);
                // Remove addons first
                $this->addonSubscription->builder()->where('start_date', $subscription->start_date)->where('end_date', $subscription->end_date)->delete();
                // Remove subscription
                $this->subscription->deleteById($id);
            } else {

                $data[] = [
                    'name' => 'auto_renewal_plan',
                    'data' => 0,
                    'type' => 'integer'
                ];
                $this->schoolSettings->upsert($data, ["name"], ["data"]);
            }
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));

            ResponseService::successResponse('Your upcoming plan has been canceled successfully');
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Cancel Upcoming method');
            ResponseService::errorResponse();
        }
    }

    public function confirm_upcoming_plan($id)
    {
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            DB::beginTransaction();
            $message = 'Your Upcoming Billing Cycle Plan Has Been Added Successfully';
            $current_subscription = $this->subscription->default()->with('package')->first();
            $subscription = $this->subscription->builder()->where('start_date', '>', $current_subscription->end_date)->first();
            if ($subscription) {
                $response = [
                    'error'   => true,
                    'message' => trans('already_added'),
                    'data'    => $subscription
                ];
                return response()->json($response);
            }
            $package = $this->package->findById($id);

            // Create upcoming subscription
            $subscription = $this->subscriptionService->createSubscription($id, null, null, 0);

            // Add addons for upcoming plan
            $current_addons = $this->addonSubscription->default()->with('addon')->where('status', 1)->has('addon')->get();
            $addon_data = array();
            foreach ($current_addons as $current_addon) {
                if (!in_array($current_addon->addon->feature_id, $package->package_feature->pluck('feature_id')->toArray())) {
                    $addon_data[] = [
                        'feature_id' => $current_addon->feature_id,
                        'price'      => $current_addon->addon->price,
                        'start_date' => $subscription->start_date,
                        'end_date'   => $subscription->end_date,
                        'subscription_id' => $subscription->id
                    ];
                } else {
                    $this->addonSubscription->update($current_addon->id, ['status' => 0]);
                }
            }
            $this->addonSubscription->createBulk($addon_data);

            $data[] = [
                'name' => 'auto_renewal_plan',
                'data' => 1,
                'type' => 'integer'
            ];
            $this->schoolSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'));

            DB::commit();
            ResponseService::successResponse($message);
        } catch (\Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Confirm Upcoming Plan method');
            ResponseService::errorResponse();
        }
    }

    /**
     * @throws ApiErrorException
     */
    public function payment_success($check_out_session_id, $id)
    {
        $settings = app(CachingService::class)->getSystemSettings();
        $currency = $settings['currency_code'];

        $paymentConfiguration = PaymentConfiguration::where('school_id', null)->first();
        $stripe_secret_key = $paymentConfiguration->secret_key ?? null;
        $currency = $paymentConfiguration->currency_code;

        $this->subscriptionBill->findById($id);

        Stripe::setApiKey($stripe_secret_key);

        $session = StripeSession::retrieve($check_out_session_id);
        $status = "pending";
        if ($session->payment_status == 'paid') {
            $status = "succeed";
        }

        $payment_data = [
            'user_id'         => Auth::user()->id,
            'amount'          => ($session->amount_total / 100),
            'payment_gateway' => 'Stripe',
            'order_id'        => $session->payment_intent,
            'payment_id'      => $session->id,
            'payment_status'  => $status,
        ];

        $paymentTransaction = $this->paymentTransaction->create($payment_data);
        $this->subscriptionBill->update($id, ['payment_transaction_id' => $paymentTransaction->id]);
        $stripe = new StripeClient($stripe_secret_key);
        $stripeData = $stripe->customers->create(
            [
                'metadata' => [
                    'amount'         => $paymentTransaction->amount,
                    'transaction_id' => $paymentTransaction->id,
                    'order_id'       => $paymentTransaction->order_id,
                    'payment_id'     => $paymentTransaction->payment_id,
                    'payment_status' => $paymentTransaction->payment_status,
                ]
            ]
        );

        return redirect()->route('subscriptions.history')->with('success', trans('the_payment_has_been_completed_successfully'));
    }

    public function payment_cancel()
    {
        return redirect()->route('subscriptions.history')->with('error', trans('the_payment_has_been_cancelled'));
    }

    public function bill_receipt($id)
    {
        $subscriptionBill = $this->subscriptionBill->findById($id);

        $settings = app(CachingService::class)->getSystemSettings()->toArray();
        $school_settings = app(CachingService::class)->getSchoolSettings('*', $subscriptionBill->school_id)->toArray();

        $settings['horizontal_logo'] = basename($settings['horizontal_logo'] ?? '');
        $addons = $this->addonSubscription->builder()->where('subscription_id', $subscriptionBill->subscription_id)->onlyTrashed()->get();

        $status = 3;
        $transaction_id = null;
        if ($subscriptionBill->transaction) {
            $status = $subscriptionBill->transaction->payment_status;
            $transaction_id = $subscriptionBill->transaction->order_id;
        }

        $paymentConfiguration = PaymentConfiguration::where('school_id', null)->first();
        $currency = $paymentConfiguration->currency_code;

        $deafult_amount = $this->subscriptionService->checkMinimumAmount(strtoupper($currency), $subscriptionBill->amount);

        $start_date = Carbon::parse($subscriptionBill->subscription->start_date);
        $usage_days = $start_date->diffInDays(Carbon::parse($subscriptionBill->subscription->end_date)) + 1;


        $pdf = Pdf::loadView('subscription.subscription_receipt', compact('settings', 'subscriptionBill', 'school_settings', 'addons', 'status', 'transaction_id', 'deafult_amount','usage_days'));
        return $pdf->stream('subscription.pdf');
    }

    public function subscription_report()
    {
        ResponseService::noPermissionThenRedirect('subscription-view');
        $school = $this->school->builder();

        $settings = app(CachingService::class)->getSystemSettings();

        $packages = $this->package->builder()->where('is_trial', 0)->pluck('name', 'id');

        $over_due = $this->subscription->builder()->with('subscription_bill.transaction','subscription_bill.subscription_bill_payment')
            ->whereHas('package', function ($q) {
                $q->where('is_trial', 0);
            })->get()->where('status', 'Over Due')->count();
        $unpaid = $this->subscription->builder()->with('subscription_bill.transaction','subscription_bill.subscription_bill_payment')
            ->whereHas('package', function ($q) {
                $q->where('is_trial', 0);
            })->get()->whereIn('status', ['Failed', 'Pending', 'Unpaid'])->count();
        $paid = $this->subscription->builder()->with('subscription_bill.transaction','subscription_bill.subscription_bill_payment')
            ->whereHas('package', function ($q) {
                $q->where('is_trial', 0);
            })->get()->where('status', 'Paid')->count();
        $data = [
            'registration' => $school->count(),
            'active'       => $school->where('status', 1)->count(),
            'deactivate'   => $school->where('status', 0)->count(),
            'over_due'     => $over_due,
            'unpaid'       => $unpaid,
            'paid'         => $paid,
        ];

        return view('schools.subscription', compact('data', 'settings', 'packages'));
    }

    public function subscription_report_show(Request $request, $status = null)
    {
        ResponseService::noPermissionThenRedirect('subscription-view');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'start_date');
        $order = request('order', 'ASC');
        $search = request('search');

        $sql = $this->subscription->builder()->with('subscription_bill.transaction','subscription_bill.subscription_bill_payment','school')->has('school')
            //search query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->orwhere('name', 'LIKE', "%$search%")
                        ->orwhereHas('school', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        });
                });
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        if ($status) {
            $res = $res->whereIn('status', ['Over Due', 'Failed', 'Pending', 'Unpaid']);
            $total = count($res);
            $res = $res;
        } else {
            if ($request->status == 'Not Generated') {
                $res = $res->where('status', 'Not Generated');
                $total = count($res);
            } else if ($request->status != 0) {
                $res = $res->where('status', $request->status);
                $total = count($res);
            }
        }

        $res = $res->skip($offset)->take($limit);
        $res = (object)$res;

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $operate = '';
            // Update Current plan or Delete upconing plan
            if ($row->status == 'Current Cycle' || $row->status == 'Next Billing Cycle') {
                // $operate = BootstrapTableService::button('fa fa-calendar', '#', ['btn-gradient-primary edit-data'], ['title' => trans("change_billing_cycle"), 'data-toggle' => "modal", 'data-target' => "#editModal"]);

                // Start immediate plan
                if ($row->status == 'Current Cycle') {
                    $operate .= BootstrapTableService::button('fa fa-edit', '#', ['btn-gradient-info update-current-plan'], ['title' => trans("update_current_plan"), 'data-toggle' => "modal", 'data-target' => "#update-current-plan"]);
                }

                // Remove next billing cycle and stop auto renewal
                if ($row->status == 'Next Billing Cycle') {
                    $operate .= BootstrapTableService::button('fa fa-trash', '#', ['btn-gradient-danger stop-auto-renewal-plan'], ['title' => trans("stop_auto_renewal_plan"), 'data-id' => $row->id]);
                }
            }
            // Change bill date
            if (($row->status == 'Over Due' || $row->status == 'Failed' || $row->status == 'Pending' || $row->status == 'Unpaid') && number_format($row->subscription_bill->amount,2) != 0) {
                $operate .= BootstrapTableService::button('fa fa-calendar', '#', ['btn-gradient-danger change-bill'], ['title' => trans("change_bill_date"), 'data-toggle' => "modal", 'data-target' => "#change-bill"]);
            }

            // Generate bill
            if ($row->status == 'Bill Not Generated') {
                $operate .= BootstrapTableService::button('fa fa-file-pdf-o', '#', ['btn-gradient-dark generate-bill'], ['title' => trans("generate_bill")]);
            }

            // Bill Receipt & Pay bill cash
            if (($row->status == 'Paid' || $row->status == 'Over Due' || $row->status == 'Failed' || $row->status == 'Pending' || $row->status == 'Unpaid' || $row->status == 'Bill Not Generated') && $row->subscription_bill) {
                if ($row->status != 'Paid') {
                    $operate .= BootstrapTableService::button('fa fa-dollar', route('subscriptions-bill-payment.edit', $row->subscription_bill->id), ['btn-gradient-success receive_payment'], ['title' => trans("receive_payment")]);
                }

                $operate .= BootstrapTableService::button('fa fa-file-pdf-o', url('subscriptions/bill/receipt', $row->subscription_bill->id), ['btn-gradient-light'], ['title' => 'Receipt', 'target' => '_blank']);
            }

            // Delete payment [ cash / cheque ] receive
            if ($row->subscription_bill) {
                if ($row->subscription_bill->subscription_bill_payment) {
                    $operate .= BootstrapTableService::deleteButton(route('subscriptions-bill-payment.destroy', $row->subscription_bill->subscription_bill_payment->id));
                }
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['logo'] = $row->school->logo;
            $tempRow['school_name'] = $row->school->name;
            $tempRow['plan'] = $row->name;
            $tempRow['billing_cycle'] = format_date($row->start_date) . ' - ' . format_date($row->end_date);
            if ($row->subscription_bill) {
                $tempRow['amount'] = number_format(ceil($row->subscription_bill->amount * 100) / 100, 2);
                // $tempRow['amount'] = $row->subscription_bill->amount;
                $tempRow['due_date'] = $row->subscription_bill->due_date;
                $tempRow['subscription_bill_id'] = $row->subscription_bill->id;
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update_expiry(Request $request)
    {
        ResponseService::noPermissionThenRedirect('subscription-change-bills');
        $request->validate([
            'end_date' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $subscription = $this->subscription->findById($request->id);
            $upcoming_package_start_date = Carbon::parse($subscription->end_date)->addDay()->format('Y-m-d');

            $upcoming_package = $this->subscription->builder()->where('school_id', $request->school_id)->whereDate('start_date', $upcoming_package_start_date)->first();

            $end_date = date('Y-m-d', strtotime($request->end_date));
            // Update upcoming billing if found
            if ($upcoming_package) {
                $systemSettings = $this->cache->getSystemSettings();
                $upcoming_package_end_date = Carbon::parse($end_date)->addDays($systemSettings['billing_cycle_in_days'])->format('Y-m-d');
                $this->subscription->update($upcoming_package->id, ['start_date' => Carbon::parse($end_date)->addDay()->format('Y-m-d'), 'school_id' => $request->school_id, 'end_date' => $upcoming_package_end_date]);
            }

            $this->subscription->update((int)$request->id, ['end_date' => $end_date, 'school_id' => $request->school_id]);

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'), $request->school_id);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function change_bill_date(Request $request)
    {
        ResponseService::noPermissionThenRedirect('subscription-change-bills');
        $request->validate([
            'due_date' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $due_date = date('Y-m-d', strtotime($request->due_date));
            $this->subscriptionBill->update($request->id, ['due_date' => $due_date, 'school_id' => $request->school_id]);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function start_immediate_plan($id = null)
    {
        if (env('DEMO_MODE')) {
            return response()->json(array(
                'error'   => true,
                'message' => "This is not allowed in the Demo Version.",
                'code'    => 112
            ));
        }
        ResponseService::noRoleThenRedirect('School Admin');
        try {
            DB::beginTransaction();

            // Check previous pending bills
            $subscriptionBill = $this->subscriptionService->subscriptionPendingBill();

            if ($subscriptionBill) {
                ResponseService::errorResponse('Kindly settle any outstanding payments from before');
            }

            // Get current plan
            $subscription = $this->subscription->default()->first();

            // Create current subscription plan bill
            $subscriptionBillData = $this->subscriptionService->createSubscriptionBill($subscription, null);

            // Update current plan end date & delete features
            $current_subscription_expiry = $this->subscription->update($subscription->id, ['end_date' => Carbon::now()->format('Y-m-d')]);
            $this->subscriptionFeature->builder()->where('subscription_id', $subscription->id)->delete();

            // Create new subscription plan
            $new_subscription = $this->subscriptionService->createSubscription($id, null, null, 1);

            // Update upcoming plan start & end date if found
            $upcoming_package = $this->subscription->builder()->with('package')->whereDate('start_date', '>', $subscription->end_date)->first();

            if ($upcoming_package) {
                $upcoming_plan_start_date = Carbon::parse($new_subscription->end_date)->addDays(1);
                $upcoming_plan_end_date = Carbon::parse($upcoming_plan_start_date)->addDays($upcoming_package->package->days);
                $upcoming_plan_update = [
                    'start_date' => $upcoming_plan_start_date,
                    'end_date'   => $upcoming_plan_end_date
                ];
                $this->subscription->update($upcoming_package->id, $upcoming_plan_update);
            }

            // Delete addons
            $addons = $this->addonSubscription->builder()->where('subscription_id', $subscription->id)->get();

            $soft_delete_addon = array();
            foreach ($addons as $key => $addon) {
                $this->addonSubscription->update($addon->id, ['end_date' => $current_subscription_expiry->end_date]);
                $soft_delete_addon[] = $addon->id;
            }

            $this->addonSubscription->builder()->whereIn('id', $soft_delete_addon)->delete();

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'));
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function update_current_plan(Request $request)
    {
        if (env('DEMO_MODE')) {
            return response()->json(array(
                'error'   => true,
                'message' => "This is not allowed in the Demo Version.",
                'code'    => 112
            ));
        }
        ResponseService::noPermissionThenRedirect('subscription-change-bills');
        try {
            DB::beginTransaction();

            // Get active plan
            $subscription = $this->subscription->builder()->where('id', $request->id)->first();

            // Create bill for current subscription plan
            $subscriptionBillData = $this->subscriptionService->createSubscriptionBill($subscription, null);

            // Expiry current plan end date & delete features
            $this->subscription->update($subscription->id, ['end_date' => Carbon::now()->format('Y-m-d'), 'school_id' => $subscription->school_id]);
            $this->subscriptionFeature->builder()->where('subscription_id', $subscription->id)->delete();

            // Create new subscription plan
            $new_subscription = $this->subscriptionService->createSubscription($request->package_id, $subscription->school_id, null, 1);
            
            // Change start and end date if upcoming plan found
            $upcoming_plan = $this->subscription->builder()->with('package')->where('school_id', $subscription->school_id)->whereDate('start_date', Carbon::parse($subscription->end_date)->addDay()->format('Y-m-d'))->first();

            if ($upcoming_plan) {
                $upcoming_plan_data = [
                    'start_date' => Carbon::parse($new_subscription->end_date)->addDay()->format('Y-m-d'),
                    'end_date'   => Carbon::parse($new_subscription->end_date)->addDays($upcoming_plan->package->days)->format('Y-m-d'),
                    'school_id'  => $upcoming_plan->school_id
                ];
                $this->subscription->update($upcoming_plan->id, $upcoming_plan_data);
            }

            // Expiry addons
            $addons = $this->addonSubscription->builder()->where('subscription_id', $subscription->id)->where('school_id', $subscription->school_id)->get();
            $soft_delete_addon = array();

            foreach ($addons as $key => $addon) {
                $this->addonSubscription->update($addon->id, ['end_date' => $new_subscription->end_date, 'school_id' => $new_subscription->school_id]);
                $soft_delete_addon[] = $addon->id;
            }
            $this->addonSubscription->builder()->whereIn('id', $soft_delete_addon)->delete();

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.FEATURES'), $new_subscription->school_id);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function destroy($id)
    {
        //
        ResponseService::noPermissionThenSendJson('subscription-change-bills');
        try {
            DB::beginTransaction();
            $subscription = $this->subscription->findById($id);
            // $school_settings = [
            //     'name' => 'auto_renewal_plan',
            //     'data' => '0',
            //     'school_id' => $subscription->school_id,
            //     'type' => 'integer'
            // ];

            $schoolSetting = SchoolSetting::where('name', 'auto_renewal_plan')->where('school_id', $subscription->school_id)->first();
            if ($schoolSetting) {
                $schoolSetting->data = 0;
                $schoolSetting->save();
            }

            $this->subscription->deleteById($id);

            $this->cache->removeSchoolCache(config('constants.CACHE.SCHOOL.SETTINGS'), $subscription->school_id);
            DB::commit();
            ResponseService::successResponse('Auto-renewal successfully canceled');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Destroy method');
            ResponseService::errorResponse();
        }
    }

    public function generate_bill($id)
    {
        ResponseService::noPermissionThenSendJson('subscription-change-bills');
        if (env('DEMO_MODE')) {
            return response()->json(array(
                'error'   => true,
                'message' => "This is not allowed in the Demo Version.",
                'code'    => 112
            ));
        }
        try {
            DB::beginTransaction();
            $subscription = $this->subscription->findById($id);
            $today_date = Carbon::now()->format('Y-m-d');

            $subscriptionBillData = $this->subscriptionService->createSubscriptionBill($subscription, 1);

            $addons = AddonSubscription::where('school_id', $subscription->school_id)->where('end_date', $subscription->end_date)->get();
            $soft_delete_addon_ids = array();
            foreach ($addons as $addon) {
                $soft_delete_addon_ids[] = $addon->id;
            }
            
            // Delete subscription features 
            SubscriptionFeature::where('subscription_id', $subscription->id)->delete();

            // Check auto-renew plan is enabled
            $auto_renewal_plan = SchoolSetting::where('name', 'auto_renewal_plan')->where('data', 1)->where('school_id', $subscription->school_id)->first();
            if ($auto_renewal_plan) {
                $check_subscription = Subscription::whereDate('start_date', '<=', $today_date)->whereDate('end_date', '>=', $today_date)->where('school_id', $subscription->school_id)->first();

                // Check if already set upcoming billing cycle or not
                if (!$check_subscription) {
                    // Not set, add previous subscription and addons
                    $previous_subscription = Subscription::where('school_id', $subscription->school_id)->orderBy('end_date', 'DESC')->first();

                    // Create subscription plan
                    $new_subscription_plan = $this->subscriptionService->createSubscription($previous_subscription->package_id, $previous_subscription->school_id, null, 1);

                    // Check addons
                    $addons = AddonSubscription::where('school_id', $subscription->school_id)->where('subscription_id', $subscription->id)->where('status', 1)->get();

                    $addons_data = array();
                    foreach ($addons as $addon) {
                        $addons_data[] = [
                            'school_id'  => $subscription->school_id,
                            'feature_id' => $addon->feature_id,
                            'price'      => $addon->addon->price,
                            'start_date' => $today_date,
                            'end_date'   => $new_subscription_plan->end_date,
                            'status'     => 1,
                            'subscription_id'     => $new_subscription_plan->id,
                        ];
                    }

                    AddonSubscription::upsert($addons_data, ['school_id', 'feature_id', 'end_date'], ['price', 'start_date', 'status','subscription_id']);
                } else {
                    // Already set plan, update charges in subscription table

                    $update_subscription = $this->subscriptionService->createSubscription($check_subscription->package_id, $check_subscription->school_id, $check_subscription->id, 1);

                    // Create addon
                    $addons = AddonSubscription::where('school_id', $subscription->school_id)->where('subscription_id', $subscription->id)->where('status', 1)->get();


                    $update_addons = array();
                    foreach ($addons as $addon) {
                        $update_addons[] = [
                            'school_id'  => $subscription->school_id,
                            'feature_id' => $addon->feature_id,
                            'price'      => $addon->addon->price,
                            'start_date' => $update_subscription->start_date,
                            'end_date'   => $update_subscription->end_date,
                            'status'     => 1,
                            'subscription_id'     => $update_subscription->id,
                        ];
                    }

                    AddonSubscription::upsert($update_addons, ['school_id', 'feature_id', 'end_date'], ['price', 'start_date', 'status','subscription_id']);
                }
                AddonSubscription::whereIn('id', $soft_delete_addon_ids)->delete();
            }


            DB::commit();
            ResponseService::successResponse('bill generated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'Subscription Controller -> Generate bill method');
            ResponseService::errorResponse();
        }
    }

    public function transactions_log()
    {
        ResponseService::noPermissionThenRedirect('subscription-view');
        return view('subscription.transaction_log');
    }

    public function subscription_transaction_list(Request $request)
    {
        ResponseService::noPermissionThenRedirect('subscription-view');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $display_type = request('display_type');

        $sql = $this->subscriptionBill->builder()->with('school:id,name,logo','transaction')->has($display_type)->where('amount','>',0);

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->where(function ($q) use ($search) {
                $q->where('amount', 'LIKE', "%$search%")
                ->orWhereHas('transaction',function($q) use($search) {
                    $q->where('order_id', 'LIKE', "%$search%")
                    ->orwhere('payment_id', 'LIKE', "%$search%")
                    ->orWhere('payment_gateway', 'LIKE', "%$search%");
                })->orWhereHas('school', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('subscription_bill_payment',function($q) use($search) {
                    $q->where('cheque_number', 'LIKE', "%$search%");
                });
            });
        }

        $currency = '';
        $paymentConfiguration = '';
        if ($display_type == 'transaction') {
            $paymentConfiguration = PaymentConfiguration::where('school_id', null)->first();
            $currency = $paymentConfiguration->currency_code;
        }

        if (!empty($request->payment_status)) {
            if ($display_type == 'subscription_bill_payment' && $request->payment_status == 'succeed') {
                $sql->whereHas('subscription_bill_payment',function($q){
                    $q->whereNotNull('id');
                });
            } else {
                $sql->whereHas('transaction',function($q) use($request){
                    $q->where('payment_status', $request->payment_status);
                });

                

                // $deafult_amount = $this->subscriptionService->checkMinimumAmount(strtoupper($currency), $subscriptionBill->amount);
            }
        }
        
        $total = $sql->count();
        $sql->orderBy($sort,$order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $tempRow = $row->toArray();
            if ($row->transaction) {
                // Online payment
                $amount = $this->subscriptionService->checkMinimumAmount(strtoupper($currency), number_format($row->amount, 2));
                $tempRow['amount'] = number_format($amount, 2);
                $tempRow['payment_gateway'] = $row->transaction->payment_gateway;
                $tempRow['order_id'] = $row->transaction->order_id;
                $tempRow['payment_id'] = $row->transaction->payment_id;
                $tempRow['payment_status'] = $row->transaction->payment_status;
                $tempRow['date'] = $row->transaction->created_at;
            } else if($row->subscription_bill_payment){
                // Offline payment [ Cash / Cheque ]
                $tempRow['payment_gateway'] = $row->subscription_bill_payment->payment_type == 'Cash' ? 'Cash' : 'Cheque';
                $tempRow['payment_status'] = 'succeed';
                $tempRow['order_id'] = $row->subscription_bill_payment->cheque_number;
                $tempRow['date'] = $row->subscription_bill_payment->date;
                $tempRow['amount'] = number_format($row->amount, 2);
            }
            $tempRow['no'] = $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
