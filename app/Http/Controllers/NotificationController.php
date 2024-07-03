<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Repositories\Fees\FeesInterface;
use App\Repositories\Notification\NotificationInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Throwable;

class NotificationController extends Controller
{

    private NotificationInterface $notification;
    private CachingService $cache;
    private UserInterface $user;
    private FeesInterface $fees;

    public function __construct(NotificationInterface $notification, CachingService $cache, UserInterface $user, FeesInterface $fees) {
        $this->notification = $notification;
        $this->cache = $cache;
        $this->user = $user;
        $this->fees = $fees;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
        ResponseService::noAnyPermissionThenRedirect(['notification-create','notification-list']);

        $users = $this->user->builder()->role(['Student','Guardian'])->with('roles:id,name')->select('id','first_name','last_name')->get();
        return view('notification.index',compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
        ResponseService::noPermissionThenRedirect('notification-create');
        $request->validate([
            'title' => 'required',
            'message' => 'required',
            'send_to' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $data = [
                'title' => $request->title,
                'message' => $request->message,
                'send_to' => $request->send_to,
                'image' => $request->hasFile('image') ? $request->file('image')->store('notification','public') : null,
                'session_year_id' => $sessionYear->id
            ];
            $notification = $this->notification->create($data);

            $notifyUser = [];

            if ($request->send_to == 'All users') {
                $notifyUser = $this->user->builder()->role(['Student','Guardian'])->pluck('id');
            } else if($request->send_to == 'Students') {
                $notifyUser = $this->user->builder()->role('Student')->pluck('id');
            } else if($request->send_to == 'Guardian') {
                $notifyUser = $this->user->builder()->role('Guardian')->pluck('id');
            } else if($request->send_to == 'Over Due Fees') {
                // Over due fees
                $today = Carbon::now()->format('Y-m-d');
                $student_ids = array();
                $guardian_ids = array();
                $fees = $this->fees->builder()->whereDate('due_date','<',$today)->get();

                foreach ($fees as $key => $fee) {
                    $sql = $this->user->builder()->role('Student')->select('id', 'first_name', 'last_name')->with([
                        'fees_paid'     => function ($q) use ($fee) {
                            $q->where('fees_id', $fee->id);
                        },
                        'student:id,guardian_id,user_id','student.guardian:id'])->whereHas('student.class_section', function ($q) use ($fee) {
                        $q->where('class_id', $fee->class_id);
                    })->whereDoesntHave('fees_paid', function ($q) use ($fee) {
                        $q->where('fees_id', $fee->id);
                    })->orWhereHas('fees_paid', function ($q) use ($fee) {
                        $q->where(['fees_id' => $fee->id, 'is_fully_paid' => 0]);
                    });
                    $student_ids[] = $sql->pluck('id')->toArray();
                    $guardian_ids[] = $sql->get()->pluck('student.guardian_id')->toArray();
                }
                
                $student_ids = array_merge(...$student_ids);
                $guardian_ids = array_merge(...$guardian_ids);
                $notifyUser = array_merge($student_ids, $guardian_ids);
            } else {
                $notifyUser = $request->user_id;
            }

            $customData = [];
            if ($notification->image) {
                $customData = [
                    'image' => $notification->image
                ];
            }
            $title = $request->title; // Title for Notification
            $body = $request->message;
            $type = 'Notification';

            send_notification($notifyUser, $title, $body, $type, $customData); // Send Notification
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Notification Controller -> Store Method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
        ResponseService::noPermissionThenRedirect('notification-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = $this->notification->builder()
            ->where(function ($query) use ($search) {
                $query->when($search, function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")->orwhere('message', 'LIKE', "%$search%")->Owner();
                });
            });
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = BootstrapTableService::deleteButton(route('notifications.destroy', $row->id));
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        ResponseService::noFeatureThenRedirect('Announcement Management');
        ResponseService::noPermissionThenRedirect('notification-delete');
        try {
            $this->notification->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Notification Controller -> Delete Method");
            ResponseService::errorResponse();
        }

    }
}
