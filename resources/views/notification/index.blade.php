@extends('layouts.master')

@section('title')
    {{ __('notification') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage_notification') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create_notification') }}
                        </h4>
                        <form id="create-form" class="pt-3" action="{{ url('notifications') }}" method="POST"
                              novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="">{{ __('title') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('title', null, ['required','class' => 'form-control','placeholder' => __('title')]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="">{{ __('message') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('message', null, ['required','class' => 'form-control','placeholder' => __('message'), 'rows' => 3]) !!}
                                </div>
                                <div class="form-group col-sm-6 col-md-4">
                                    <label>{{ __('send_to') }} <span class="text-danger">*</span></label>
                                    {!! Form::select('send_to', ['All users' => __('all_users'),'Students' => __('students'), 'Guardian' => __('guardian'),'Over Due Fees' => __('over_due_fees'), 'Specific users' => __('specific_users')], null, ['class' => 'form-control type']) !!}
                                </div>

                                <div class="form-group col-sm-6 col-md-4">
                                    <label>{{ __('user') }} </label>
                                    <select name="user_id[]" id="" disabled multiple class="form-control user_id select2-dropdown select2-hidden-accessible" data-placeholder="{{ __('select_users') }}">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" data-role="{{ $user->roles->first()->name }}">{{ $user->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-6 col-md-4">
                                    <label>{{ __('image') }} </label>
                                    <input type="file" name="image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" id="image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>

                            </div>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list_notification') }}
                        </h4>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('notifications.show', [1]) }}" data-click-to-select="true"
                               data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1"
                               data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                               data-sort-order="desc" data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                               data-export-options='{ "fileName": "notification-list-<?= date('d-m-y') ?>","ignoreColumn":["operate"]}'
                               data-escape="true" data-query-params="queryParams">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="image" data-formatter="imageFormatter">{{ __('image') }}</th>
                                <th scope="col" data-field="title">{{ __('title') }}</th>
                                <th scope="col" data-field="message" data-events="tableDescriptionEvents" data-formatter="descriptionFormatter">{{ __('message') }}</th>
                                <th scope="col" data-field="operate" data-escape="false">{{ __('action') }}
                                </th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $('.type').change(function (e) {
            e.preventDefault();
            $('.user_id').val('').trigger('change');
            if ($(this).val() == 'Specific users') {
                $('.user_id').attr('disabled', false);
            } else {
                $('.user_id').attr('disabled', true);
            }
        });
    </script>
@endsection
