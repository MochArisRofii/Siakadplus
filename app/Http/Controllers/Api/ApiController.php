<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Repositories\Gallery\GalleryInterface;
use App\Repositories\Holiday\HolidayInterface;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\LeaveDetail\LeaveDetailInterface;
use App\Repositories\LeaveMaster\LeaveMasterInterface;
use App\Repositories\PaymentConfiguration\PaymentConfigurationInterface;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\Payment\PaymentService;
use App\Services\ResponseService;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Throwable;

class ApiController extends Controller {
    private CachingService $cache;
    private HolidayInterface $holiday;
    private StudentInterface $student;
    private PaymentConfigurationInterface $paymentConfiguration;
    private PaymentTransactionInterface $paymentTransaction;
    private GalleryInterface $gallery;
    private SessionYearInterface $sessionYear;
    private LeaveDetailInterface $leaveDetail;
    private LeaveMasterInterface $leaveMaster;
    private LeaveInterface $leave;

    public function __construct(CachingService $cache, HolidayInterface $holiday, StudentInterface $student, PaymentConfigurationInterface $paymentConfiguration, PaymentTransactionInterface $paymentTransaction, GalleryInterface $gallery, SessionYearInterface $sessionYear, LeaveDetailInterface $leaveDetail, LeaveMasterInterface $leaveMaster, LeaveInterface $leave) {
        $this->cache = $cache;
        $this->holiday = $holiday;
        $this->student = $student;
        $this->paymentConfiguration = $paymentConfiguration;
        $this->paymentTransaction = $paymentTransaction;
        $this->gallery = $gallery;
        $this->sessionYear = $sessionYear;
        $this->leaveDetail = $leaveDetail;
        $this->leaveMaster = $leaveMaster;
        $this->leave = $leave;
    }

    public function logout(Request $request) {
        try {
            $user = $request->user();
            $user->fcm_id = '';
            $user->save();
            $user->currentAccessToken()->delete();
            ResponseService::successResponse('Logout Successfully done');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getHolidays(Request $request) {
        try {
            if ($request->child_id) {
                $child = $this->student->findById($request->child_id);
                $data = $this->holiday->builder()->where('school_id', $child->user->school_id)->get();
            } else {
                $data = $this->holiday->all();
            }
            ResponseService::successResponse("Holidays Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getSettings(Request $request) {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:privacy_policy,contact_us,about_us,terms_condition,app_settings,fees_settings'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $systemSettings = $this->cache->getSystemSettings();
            if ($request->type == "app_settings") {
                $data = array(
                    'app_link'                 => $systemSettings['app_link'] ?? "",
                    'ios_app_link'             => $systemSettings['ios_app_link'] ?? "",
                    'app_version'              => $systemSettings['app_version'] ?? "",
                    'ios_app_version'          => $systemSettings['ios_app_version'] ?? "",
                    'force_app_update'         => $systemSettings['force_app_update'] ?? "",
                    'app_maintenance'          => $systemSettings['app_maintenance'] ?? "",
                    'teacher_app_link'         => $systemSettings['teacher_app_link'] ?? "",
                    'teacher_ios_app_link'     => $systemSettings['teacher_ios_app_link'] ?? "",
                    'teacher_app_version'      => $systemSettings['teacher_app_version'] ?? "",
                    'teacher_ios_app_version'  => $systemSettings['teacher_ios_app_version'] ?? "",
                    'teacher_force_app_update' => $systemSettings['teacher_force_app_update'] ?? "",
                    'teacher_app_maintenance'  => $systemSettings['teacher_app_maintenance'] ?? "",
                    'tagline'                  => $systemSettings['tag_line'] ?? "",
                );

            } else {
                $data = isset($systemSettings[$request->type]) ? htmlspecialchars_decode($systemSettings[$request->type]) : "";
            }
            ResponseService::successResponse("Data Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    protected function forgotPassword(Request $request) {
        $request->validate([
            'email' => "required|email"
        ]);
        try {
            $response = Password::sendResetLink(['email' => $request->email]);
            if ($response == Password::RESET_LINK_SENT) {
                ResponseService::successResponse("Forgot Password email send successfully");
            } else {
                ResponseService::errorResponse("Cannot send Reset Password Link.Try again later", null, config('constants.RESPONSE_CODE.RESET_PASSWORD_FAILED'));
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    protected function changePassword(Request $request) {
        $request->validate([
            'current_password'     => 'required',
            'new_password'         => 'required|min:6',
            'new_confirm_password' => 'same:new_password',
        ]);

        try {
            $user = $request->user();
            if (Hash::check($request->current_password, $user->password)) {
                $user->update(['password' => Hash::make($request->new_password)]);
                ResponseService::successResponse("Password Changed successfully.");
            } else {
                ResponseService::errorResponse("Invalid Password", null, config('constants.RESPONSE_CODE.INVALID_PASSWORD'));
            }
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPaymentMethod(Request $request) {
        if (Auth::user()->hasRole('Guardian')) {
            $validator = Validator::make($request->all(), [
                'child_id' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
        }
        try {

            $response = $this->paymentConfiguration->builder()->select('payment_method', 'status')->pluck('status', 'payment_method');
            ResponseService::successResponse("Payment Details Fetched", $response);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPaymentConfirmation(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $paymentTransaction = app(PaymentTransactionInterface::class)->builder()->where('id', $request->id)->first();
            if (empty($paymentTransaction)) {
                ResponseService::errorResponse("No Data Found");
            }
            $data = PaymentService::create($paymentTransaction->payment_gateway, $paymentTransaction->school_id)->retrievePaymentIntent($paymentTransaction->order_id);

            $data = PaymentService::formatPaymentIntent($paymentTransaction->payment_gateway, $data);

            // Success
            ResponseService::successResponse("Payment Details Fetched", $data, ['payment_transaction' => $paymentTransaction]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getPaymentTransactions(Request $request) {
        $validator = Validator::make($request->all(), [
            'latest_only' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $paymentTransactions = app(PaymentTransactionInterface::class)->builder();
            if ($request->latest_only) {
                $paymentTransactions->where('created_at', '>', Carbon::now()->subMinutes(30)->toDateTimeString());
            }
            $paymentTransactions = $paymentTransactions->with('school')->orderBy('id', 'DESC')->get();
            $schoolSettings = app(SchoolSettingInterface::class)->builder()
                ->where(function ($q) {
                    $q->where('name', 'currency_code')->orWhere('name', 'currency_symbol');
                })->whereIn('school_id', $paymentTransactions->pluck('school_id'))->get();

            $paymentTransactions = collect($paymentTransactions)->map(function ($data) use ($schoolSettings) {
                $getSchoolSettings = $schoolSettings->filter(function ($settings) use ($data) {
                    return $settings->school_id == $data->school_id;
                })->pluck('data', 'name');
                $data->currency_code = $getSchoolSettings['currency_code'] ?? '';
                $data->currency_symbol = $getSchoolSettings['currency_symbol'] ?? '';
                if ($data->payment_status == "pending") {
                    try {
                        $paymentIntent = PaymentService::create($data->payment_gateway, $data->school_id)->retrievePaymentIntent($data->order_id);
                        $paymentIntent = PaymentService::formatPaymentIntent($data->payment_gateway, $paymentIntent);
                    } catch (ApiErrorException) {
                        $this->paymentTransaction->update($data->id, ['payment_status' => "failed", 'school_id' => $data->school_id]);
                    }

                    if (!empty($paymentIntent) && $paymentIntent['status'] != "pending") {
                        $this->paymentTransaction->update($data->id, ['payment_status' => $paymentIntent['status'] ?? "failed", 'school_id' => $data->school_id]);
                    }
                }
                return $data;
            });

            ResponseService::successResponse("Payment Transactions Fetched", $paymentTransactions);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getGallery(Request $request)
    {
        try {
            if ($request->gallery_id) {
                $data = $this->gallery->builder()->with('file')->where('id', $request->gallery_id)->first();
            } else {
                if ($request->child_id) {
                    $child = $this->student->findById($request->child_id);
                    $data = $this->gallery->builder()->with('file')->where('school_id', $child->user->school_id);
                    if ($request->session_year_id) {
                        $data = $data->where('session_year_id',$request->session_year_id);
                    }
                    $data = $data->get();
                } else {
                    if ($request->session_year_id) {
                        $data = $this->gallery->builder()->with('file')->where('session_year_id',$request->session_year_id);
                    } else {
                        $data = $this->gallery->builder()->with('file');
                    }
                    $data = $data->get();
                }
            }
            ResponseService::successResponse("Gallery Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getSessionYear(Request $request)
    {
        try {
            if ($request->child_id) {
                $child = $this->student->findById($request->child_id);
                $data = $this->sessionYear->builder()->where('school_id', $child->user->school_id)->get();
            } else {
                $data = $this->sessionYear->builder()->get();
            }
            ResponseService::successResponse("Session Year Fetched Successfully", $data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getLeaves(Request $request)
    {

        try {
            $leave = $this->leaveDetail->builder()->with('leave:id,user_id','leave.user:id,first_name,last_name')
            ->whereHas('leave',function($q) {
                $q->where('status',1);
            });
            if ($request->type == 0 || $request->type == null) {
                $leave->whereDate('date','<=',Carbon::now()->format('Y-m-d'))->whereDate('date','>=',Carbon::now()->format('Y-m-d'));
            }
            if ($request->type == 1) {
                $tomorrow_date = Carbon::now()->addDay()->format('Y-m-d');
                $leave->whereDate('date','<=',$tomorrow_date)->whereDate('date','>=',$tomorrow_date);
            }
            if ($request->type == 2) {
                $upcoming_date = Carbon::now()->addDays(1)->format('Y-m-d');
                $leave->whereDate('date','>',$upcoming_date);
            }
            $leave = $leave->orderBy('date','ASC')->get()->append(['leave_date']);
            ResponseService::successResponse("Data Fetched Successfully", $leave);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
        
    }

    public function applyLeaves(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason'  => 'required',
            
        ]);
        
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $leaveMaster = $this->leaveMaster->builder()->where('session_year_id',$sessionYear->id)->first();

            if (!$leaveMaster) {
                ResponseService::errorResponse("Kindly contact the school admin to update settings for continued access.");
            }

            $public_holiday = $this->holiday->builder()->whereDate('date','>=',$sessionYear->start_date)->whereDate('date','<=',$sessionYear->end_date)->get()->pluck('date')->toArray();
            
            $dates = array_column($request->leave_details, 'date');
            $from_date = min($dates);
            $to_date = max($dates);

            $leave_data = [
                'user_id' => Auth::user()->id,
                'reason' => $request->reason,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'status' => 0,
                'leave_master_id' => $leaveMaster->id
            ];

            $holidays = explode(',',$leaveMaster->holiday);

            $leave = $this->leave->create($leave_data);
            foreach ($request->leave_details as $key => $leaves) {
                $day = date('l',strtotime($leaves['date']));
                if (!in_array($day, $holidays) && !in_array($leaves['date'], $public_holiday)) {
                    $data[] = [
                        'leave_id' => $leave->id,
                        'date' => $leaves['date'],
                        'type' => $leaves['type']
                    ];
                }
            }
            $this->leaveDetail->createBulk($data);
            DB::commit();
            ResponseService::successResponse("Data Stored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function getMyLeaves(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month'  => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'status' => 'in:0,1,2'
        ]);
        
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sessionYear = $this->cache->getDefaultSessionYear();
            $sql = $this->leave->builder()->with('leave_detail')->where('user_id',Auth::user()->id)->withCount(['leave_detail as full_leave' => function($q) {
                $q->where('type','Full');
            }])->withCount(['leave_detail as half_leave' => function($q) {
                $q->whereNot('type','Full');
            }]);

            if ($request->session_year_id) {
                $sql->whereHas('leave_master',function($q) use($request) {
                    $q->where('session_year_id', $request->session_year_id);
                });
            } else {
                $sql->whereHas('leave_master',function($q) use($sessionYear) {
                    $q->where('session_year_id', $sessionYear->id);
                });
            }
            if (isset($request->status)) {
                $sql->where('status',$request->status);
            }
            if ($request->month) {
                $sql->whereHas('leave_detail',function($q) use($request){
                    $q->whereMonth('date',$request->month);
                });
            }
            $data = $sql->get();

            $data = $data->map(function($sql) {
                $total_leaves = ($sql->half_leave / 2) + $sql->full_leave;
                $sql->days = $total_leaves;
                return $sql;
            });
            ResponseService::successResponse("Data Fetched Successfully",$data);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function deleteLeaves(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_id'  => 'required',
        ]);
        
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $leave = $this->leave->findById($request->leave_id);
            if ($leave->status != 0) {
                ResponseService::errorResponse("You cannot delete this leave");
            }
            $this->leave->deleteById($request->leave_id);
            DB::commit();
            ResponseService::successResponse("Data Deleted Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
