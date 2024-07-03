@extends('layouts.master')

@section('title')
    {{ __('teacher') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_teacher') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create_teacher') }}
                        </h4>
                        <form class="create-form pt-3" id="formdata" action="{{ route('teachers.store') }}" enctype="multipart/form-data" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('email') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('email', null, ['required', 'placeholder' => __('email'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('first_name', null, ['required', 'placeholder' => __('first_name'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('last_name', null, ['required', 'placeholder' => __('last_name'), 'class' => 'form-control']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'male') !!}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'female') !!}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('mobile') }} <span class="text-danger">*</span></label>
                                    {!! Form::number('mobile', null, ['required', 'placeholder' => __('mobile'),'min' => 1 , 'class' => 'form-control remove-number-increment']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('dob', null, ['required', 'placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control','autocomplete'=>'off']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="image">{{ __('image') }}</label>
                                    <input type="file" name="image" class="file-upload-default" accept="image/png,image/jpeg,image/jpg"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" id="image" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('qualification') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('qualification', null, ['required', 'placeholder' => __('qualification'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('current_address', null, ['required', 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('permanent_address', null, ['required', 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="salary">{{__('Salary') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="salary" id="salary" placeholder="{{__('Salary')}}" class="form-control" min="0" required>
                                </div>
                                @hasFeature('Teacher Management')
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Status') }} <span class="text-danger">*</span></label><br>
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
                                                {{ __('inactive') }}
                                            </label>
                                        </div>
                                    </div>
                                    <span class="text-danger small">{{ __('Note :- Activating this will consider in your current subscription cycle') }}</span>
                                </div>
                                @endHasFeature
                            </div>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list_teacher') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-12">
                                <button id="update-status" class="btn btn-secondary" disabled><span class="update-status-btn-name">{{ __('Inactive') }}</span></button>
                            </div>
                        </div>

                        <div class="col-12 mt-4 text-right">
                            <b><a href="#" class="table-list-type active mr-2" data-value="active">{{__('active')}}</a></b> | <a href="#" class="ml-2 table-list-type" data-value="inative">{{__("Inactive")}}</a>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                       data-url="{{ route('teachers.show',[1]) }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                       data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                                       data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                       data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                       data-export-options='{ "fileName": "teacher-list-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                                       data-query-params="activeDeactiveQueryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no">{{ __('no.') }}</th>
                                        <th scope="col" data-field="first_name">{{ __('first_name') }}</th>
                                        <th scope="col" data-field="last_name">{{ __('last_name') }}</th>
                                        <th scope="col" data-field="gender">{{ __('gender') }}</th>
                                        <th scope="col" data-field="email">{{ __('email') }}</th>
                                        <th scope="col" data-field="dob" data-formatter="dateFormatter">{{ __('dob') }}</th>
                                        <th scope="col" data-field="image" data-formatter="imageFormatter">{{ __('image') }}</th>
                                        <th scope="col" data-field="staff.qualification">{{ __('qualification') }}</th>
                                        <th scope="col" data-field="current_address">{{ __('current_address') }}</th>
                                        <th scope="col" data-field="permanent_address">{{ __('permanent_address') }}</th>
                                        <th scope="col" data-field="staff.salary" data-visible="false"> {{ __('Salary') }}</th>
                                        <th data-events="teacherEvents" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('edit_teacher') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="editdata" class="edit-form" action="{{ url('teachers') }}" novalidate="novalidate" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('email') }} <span class="text-danger">*</span></label>
                                {!! Form::text('email', null, ['required', 'placeholder' => __('email'), 'class' => 'form-control', 'id' => 'email']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                {!! Form::text('first_name', null, ['required', 'placeholder' => __('first_name'), 'class' => 'form-control', 'id' => 'first_name']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                {!! Form::text('last_name', null, ['required', 'placeholder' => __('last_name'), 'class' => 'form-control', 'id' => 'last_name']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-12">
                                <label>{{ __('gender') }} <span class="text-danger">*</span></label>
                                <div class="d-flex">
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            {!! Form::radio('gender', 'male', null, ['class' => 'form-check-input edit', 'id' => 'gender']) !!}
                                            {{ __('male') }}
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <label class="form-check-label">
                                            {!! Form::radio('gender', 'female', null, ['class' => 'form-check-input edit', 'id' => 'gender']) !!}
                                            {{ __('female') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('mobile') }} <span class="text-danger">*</span></label>
                                {!! Form::number('mobile', null, ['required', 'min' => 1 , 'placeholder' => __('mobile'), 'class' => 'form-control remove-number-increment', 'id' => 'mobile']) !!}
                            </div>

                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                {!! Form::text('dob', null, ['required', 'placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control', 'id' => 'edit-dob']) !!}
                                <span class="input-group-addon input-group-append"></span>
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label for="edit-image">{{ __('image') }}</label><br>
                                {{-- <input type="file" name="image" id="edit_image" class="form-control" placeholder="{{__('image')}}"> --}}
                                <input type="file" name="image" class="file-upload-default" accept="image/png,image/jpeg,image/jpg"/>
                                <div class="input-group col-xs-12">
                                    <input type="text" class="form-control file-upload-info" id="edit-image" disabled="" placeholder="{{ __('image') }}"/>
                                    <span class="input-group-append">
                                        <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                    </span>
                                </div>
                                <div style="width: 60px;" class="mt-2">
                                    <img src="" id="edit-teacher-image-tag" class="img-fluid w-100" alt=""/>
                                </div>
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('qualification') }} <span class="text-danger">*</span></label>
                                {!! Form::textarea('qualification', null, ['required', 'placeholder' => __('qualification'), 'class' => 'form-control', 'rows' => 3, 'id' => 'qualification']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                {!! Form::textarea('current_address', null, ['required', 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3, 'id' => 'current_address']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-12 col-lg-4">
                                <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                {!! Form::textarea('permanent_address', null, ['required', 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3, 'id' => 'permanent_address']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-4">
                                <label for="edit_salary">{{__('Salary') }} <span class="text-danger">*</span></label>
                                <input type="number" name="salary" min="0" id="edit_salary" placeholder="{{__('Salary')}}" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </div>
                </form>
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
                    let url = baseUrl + '/teachers/change-status-bulk';
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
