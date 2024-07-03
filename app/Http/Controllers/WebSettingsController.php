<?php

namespace App\Http\Controllers;

use App\Repositories\FeatureSection\FeatureSectionInterface;
use App\Repositories\FeatureSectionList\FeatureSectionListInterface;
use App\Repositories\SystemSetting\SystemSettingInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;
use Throwable;

class WebSettingsController extends Controller
{
    private CachingService $cache;
    private SystemSettingInterface $systemSettings;
    private FeatureSectionInterface $featureSection;
    private FeatureSectionListInterface $featureSectionList;

    public function __construct(CachingService $cache, SystemSettingInterface $systemSettings, FeatureSectionInterface $featureSection, FeatureSectionListInterface $featureSectionList) {
        $this->cache = $cache;
        $this->systemSettings = $systemSettings;
        $this->featureSection = $featureSection;
        $this->featureSectionList = $featureSectionList;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        ResponseService::noPermissionThenRedirect('web-settings');

        $settings = $this->cache->getSystemSettings();
        return view('web_settings.index',compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        ResponseService::noPermissionThenRedirect('web-settings');

        $request->validate([
            'hero_title_1'         => 'required',
            'hero_title_2'         => 'required',
            'about_us_title'         => 'required',
            'about_us_heading'         => 'required',
            'about_us_description'         => 'required',
            'about_us_points'         => 'required',
            'custom_package_description'         => 'required_if:custom_package_status,1',
            'download_our_app_description'         => 'required',
            'short_description'         => 'required'

        ],[
            'custom_package_description.required_if' => 'The custom package description field is required when custom package status is enable.'
        ]);
        

        $settings = array(
            'home_image', 'hero_title_1', 'hero_title_2', 'hero_title_2_image', 'about_us_title', 'about_us_heading', 'about_us_description', 'about_us_points', 'about_us_image', 'custom_package_status', 'custom_package_description', 'download_our_app_image', 'download_our_app_description', 'facebook', 'instagram', 'linkedin', 'footer_text','short_description', 'theme_primary_color',  'theme_secondary_color', 'theme_secondary_color_1',  'theme_primary_background_color', 'theme_text_secondary_color',
        );
        try {
            $data = array();
            foreach ($settings as $row) {
                if ($row == 'home_image' || $row == 'hero_title_2_image' || $row == 'about_us_image' || $row == 'download_our_app_image') {
                    if ($request->hasFile($row)) {
                        // TODO : Remove the old files from server
                        $data[] = [
                            "name" => $row,
                            "data" => $request->file($row),
                            "type" => "file"
                        ];
                    }
                } else {
                    $data[] = [
                        "name" => $row,
                        "data" => $request->$row,
                        "type" => "text"
                    ];
                }
            }
            $this->systemSettings->upsert($data, ["name"], ["data"]);
            $this->cache->removeSystemCache(config('constants.CACHE.SYSTEM.SETTINGS'));
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Web Settings Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function feature_section_index()
    {
        ResponseService::noPermissionThenRedirect('web-settings');

        return view('web_settings.feature_section');
    }

    public function feature_section_store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('web-settings');
        try {
            DB::beginTransaction();
            $feature_section_data = [
                'title' => $request->title,
                'heading' => $request->heading,
                'rank' => 0
            ];
            $featureSection = $this->featureSection->create($feature_section_data);
            $data = array();
            foreach ($request->section_data as $key => $section) {
                $data[] = [
                    'feature_section_id' => $featureSection->id,
                    'feature' => $section['feature'],
                    'description' => $section['description'],
                    'image' => $section['image']->store('feature_section','public'),
                ];
            }
            $this->featureSectionList->createBulk($data);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function web_settings_show()
    {
        ResponseService::noPermissionThenRedirect('web-settings');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'rank');
        $order = request('order', 'ASC');
        $search = request('search');

        $sql = $this->featureSection->builder()
            ->where(function ($query) use ($search) {
                $query->when($search, function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%$search%")
                        ->orwhere('title', 'LIKE', "%$search%")
                        ->orwhere('heading', 'LIKE', "%$search%");
                });
            });
        $total = $sql->count();
        $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {            
            // $operate = BootstrapTableService::editButton(route('web-settings-section.update', $row->id));
            $operate = BootstrapTableService::button('fa fa-edit', route('web-settings-section.edit',$row->id), ['btn-gradient-info'], ['title' => trans("edit")]);
            $operate .= BootstrapTableService::deleteButton(route('web-settings-section.destroy', $row->id));
            
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function web_settings_edit($id)
    {
        ResponseService::noPermissionThenRedirect('web-settings');
        $featureSection = $this->featureSection->builder()->with('feature_section_list')->where('id',$id)->first();

        return view('web_settings.feature_section_edit',compact('featureSection'));
        
    }

    public function web_settings_update(Request $request, $id)
    {
        ResponseService::noPermissionThenRedirect('web-settings');

        try {
            DB::beginTransaction();
            $feature_section_data = [
                'title' => $request->title,
                'heading' => $request->heading,
                'rank' => 0
            ];
            $featureSection = $this->featureSection->update($id, $feature_section_data);
            $data_with_image = array(); // with adding image [ update / create ]
            $data_without_image = array(); // without image
            foreach ($request->section_data as $key => $section) {
                // To check image select or not
                if (isset($section['image'])) {
                    // Check already exits record or not
                    if (isset($section['id'])) {
                        // If image & id found then delete old image file from storage
                        $section_feature = $this->featureSectionList->findById($section['id']);
                        if ($section_feature) {
                            if ($section_feature->image && Storage::disk('public')->exists($section_feature->getRawOriginal('image'))) {
                                Storage::disk('public')->delete($section_feature->getRawOriginal('image'));
                            }
                        }
                    }
                    // Set data with image
                    $data_with_image[] = [
                        'id' => $section['id'],
                        'feature_section_id' => $featureSection->id,
                        'feature' => $section['feature'],
                        'description' => $section['description'],
                        'image' => $section['image']->store('feature_section','public'),
                    ];
                } else {
                    // Set data without image
                    $data_without_image[] = [
                        'id' => $section['id'],
                        'feature_section_id' => $featureSection->id,
                        'feature' => $section['feature'],
                        'description' => $section['description'],
                        // 'image' => $section['image']->store('feature_section','public'),
                    ];
                }
                
            }
            // With image
            $this->featureSectionList->upsert($data_with_image, ['id'],['feature_section_id', 'feature', 'description', 'image']);
            // Without image
            $this->featureSectionList->upsert($data_without_image, ['id'],['feature_section_id', 'feature', 'description']);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }

    }

    public function feature_section_delete($id)
    {
        ResponseService::noPermissionThenRedirect('web-settings');
        try {
            $this->featureSection->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function feature_section_rank(Request $request)
    {
        ResponseService::noPermissionThenSendJson('web-settings');

        $validator = Validator::make($request->all(), [
            'ids' => 'required',
        ], [
            'ids' => trans('No Data Found'),
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $ids = json_decode($request->ids, false, 512, JSON_THROW_ON_ERROR);
            $update = [];
            foreach ($ids as $key => $id) {
                $update[] = [
                    'id' => $id,
                    'rank' => ($key + 1)
                ];
            }
            $this->featureSection->upsert($update, ['id'], ['rank']);
            DB::commit();
            ResponseService::successResponse('Rank Updated Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'WebSettings Controller -> Change Rank method');
            ResponseService::errorResponse();
        }
    }
}
