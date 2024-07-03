@extends('layouts.master')

@section('title')
    {{ __('staff') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Manage Staff') }}
                        </h4>
                        <form class="pt-3 create-staff-form" id="create-form" action="{{ route('staff.store') }}" method="POST" novalidate="novalidate">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="role_id">{{ __('role') }} <span class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" class="form-control" required>
                                        @foreach($roles as $role)
                                            <option value="{{$role->id}}">{{$role->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="first_name">{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="first_name" placeholder="{{__('first_name')}}" class="form-control" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="last_name">{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="last_name" placeholder="{{__('last_name')}}" class="form-control" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="mobile">{{ __('mobile') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="mobile" id="mobile" min="0" placeholder="{{__('contact')}}" class="form-control remove-number-increment" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="email">{{__('email') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" placeholder="{{__('email')}}" class="form-control" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('image') }}</label>
                                    <input type="file" name="image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                                @if (Auth::user()->school_id)
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="salary">{{__('Salary') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="salary" id="salary" placeholder="{{__('Salary')}}" class="form-control" min="0" value="0" required>
                                    </div>
                                @endif


                                @if (!Auth::user()->school_id)
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="assign_schools">{{__('assign') }} {{ __('schools') }}</label>
                                        {!! Form::select('school_id[]', $schools, null, ['class' => 'form-control select2-dropdown select2-hidden-accessible','multiple']) !!}
                                    </div>
                                @endif

                                @hasFeature('Staff Management')
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('status') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('status', 1) !!}
                                                {{ __('Active') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('status', 0,true) !!}
                                                {{ __('Inactive') }}
                                            </label>
                                        </div>
                                    </div>
                                    @if(!empty(Auth::user()->school_id))
                                        <span class="text-danger small">{{ __('Note').' :- '.__('Activating this will consider in your current subscription cycle') }}</span>
                                    @endif
                                </div>
                                @endHasFeature()


                            </div>
                            <input class="btn btn-theme" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Staff List') }}</h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <button id="update-status" class="btn btn-secondary" disabled><span class="update-status-btn-name">{{ __('Inactive') }}</span></button>
                            </div>
                        </div>
                        <div class="col-12 mt-4 text-right">
                            <b><a href="#" class="table-list-type active mr-2" data-value="active">{{__('Active')}}</a></b> | <a href="#" class="ml-2 table-list-type" data-value="inactive">{{__("Inactive")}}</a>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('staff.show',[1]) }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2"
                               data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-export-data-type='all' data-query-params="activeDeactiveQueryParams"
                               data-export-options='{ "fileName": "staff-list-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}' data-show-export="true"
                               data-toolbar="#toolbar" data-escape="true">
                            <thead>
                            <tr>
                                <th data-field="state" data-checkbox="true"></th>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="image" data-formatter="imageFormatter">{{ __('image') }}</th>
                                <th scope="col" data-field="full_name" data-sortable="true">{{ __('name') }}</th>
                                <th scope="col" data-field="roles_name" data-sortable="false">{{ __('role') }}</th>
                                <th scope="col" data-field="mobile" data-sortable="true">{{ __('mobile') }}</th>
                                <th scope="col" data-field="email">{{ __('email') }}</th>
                                <th scope="col" data-field="staff.salary" data-visible="false">{{ __('Salary') }}</th>
                                @if (!Auth::user()->school_id)
                                    <th scope="col" data-field="support_school" data-formatter="schoolNameFormatter">{{ __('assign_schools') }}</th>
                                @endif
                                <th scope="col" data-field="created_at" data-formatter="dateTimeFormatter" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-formatter="dateTimeFormatter" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-events="staffEvents" data-escape="false">{{ __('action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('Edit Staff') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 edit-staff-form" id="edit-form" action="{{ url('staff') }}" novalidate="novalidate">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="edit_role_id">{{ __('Role') }} <span class="text-danger">*</span></label>
                                        <select name="role_id" id="edit_role_id" class="form-control" required>
                                            @foreach($roles as $role)
                                                <option value="{{$role->id}}">{{$role->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="edit_first_name">{{ __('first_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="edit_first_name" placeholder="{{__('first_name')}}" class="form-control" required>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="edit_last_name">{{ __('last_name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="edit_last_name" placeholder="{{__('last_name')}}" class="form-control" required>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="edit_mobile">{{ __('mobile') }} <span class="text-danger">*</span></label>
                                        <input type="number" name="mobile" id="edit_mobile" min="0" placeholder="{{__('mobile')}}" class="form-control remove-number-increment" required>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label for="edit_email">{{__('email') }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="edit_email" placeholder="{{__('email')}}" class="form-control" required>
                                    </div>
                                    <div class="form-group col-sm-12 col-md-4">
                                        <label>{{ __('image') }}</label>
                                        <input type="file" name="image" class="file-upload-default"/>
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required/>
                                            <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                        </div>
                                        <div style="width: 60px;">
                                            <img src="" id="edit_staff_image" class="img-fluid w-100" alt=""/>
                                        </div>
                                    </div>
                                    @if (Auth::user()->school_id)
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label for="edit_salary">{{__('Salary') }} <span class="text-danger">*</span></label>
                                            <input type="number" name="salary" id="edit_salary" placeholder="{{__('Salary')}}" class="form-control" min="0" required>
                                        </div>
                                    @endif


                                    @if (!Auth::user()->school_id)
                                        <div class="form-group col-sm-12 col-md-4">
                                            <label for="assign_schools">{{__('assign') }} {{ __('schools') }}</label>
                                            {!! Form::select('school_id[]', $schools, null, ['class' => 'form-control select2-dropdown select2-hidden-accessible','multiple','id' => 'edit_school_id']) !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        let userIds;
        $('.table-list-type').on('click', function (e) {
            let value = $(this).data('value');
            let ActiveLang = window.trans['Active'];
            let DeactiveLang = window.trans['Inactive'];
            if (value === "" || value === "active" || value == null) {
                $("#update-status").data("id")
                $('.update-status-btn-name').html(DeactiveLang);
            } else {
                $('.update-status-btn-name').html(ActiveLang);
            }
        })


        function updateUserStatus(tableId, buttonClass) {
            var selectedRows = $(tableId).bootstrapTable('getSelections');
            var selectedRowsValues = selectedRows.map(function (row) {
                return row.id;
            });
            userIds = JSON.stringify(selectedRowsValues);

            if (buttonClass != null) {
                if (selectedRowsValues.length) {
                    $(buttonClass).prop('disabled', false);
                } else {
                    $(buttonClass).prop('disabled', true);
                }
            }
        }

        $('#table_list').bootstrapTable({
            onCheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onCheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            }
        });
        $("#update-status").on('click', function (e) {
            Swal.fire({
                title: window.trans["Are you sure"],
                text: window.trans["Change Status For Selected Users"],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: window.trans["Yes, Change it"]
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/staff/change-status-bulk';
                    let data = new FormData();
                    data.append("ids", userIds)

                    function successCallback(response) {
                        $('#table_list').bootstrapTable('refresh');
                        $('#update-status').prop('disabled', true);
                        userIds = null;
                        showSuccessToast(response.message);
                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }

                    ajaxRequest('POST', url, data, null, successCallback, errorCallback);
                }
            })
        })
    </script>
@endsection
