@extends('layouts.master')

@section('title')
    {{ __('manage') . ' ' . __('fees') }} {{ __('paid') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('fees') }} {{ __('paid') }}
            </h3>
        </div>
        <div class="row">
            {{-- Total Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold">{{ __('total_fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_statistics">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span class="total_compulsory_fees">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span class="total_optional_fees">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Total Collected Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold"> {{ __('collected') }} {{ __('fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_collected">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span class="total_compulsory_fees_collected">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span class="total_optional_fees_collected">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Total Pending Fees --}}
            <div class="col-md-4 col-sm-12 grid-margin stretch-card">
                <div class="card card-statistics">
                    <div class="custom-card-body">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <p class="font-weight-bold"> {{ __('pending') }} {{ __('fees') }}</p>
                                <div class="d-flex align-items-center">
                                    <h4 class="font-weight-semibold total_fees_pending">0</h4>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6 border-left text-right">
                                <p class="text-muted mt-2">{{ __('compulsory_fees') }} : <span class="total_compulsory_fees_pending">0</span></p>
                                <p class="text-muted mb-0">{{ __('optional_fees') }} : <span class="total_optional_fees_pending">0</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <div id="toolbar" class="row">
                            <div class="col">
                                <label for="session_year_id"> {{ __('Session Years') }} </label>
                                <select name="session_year_id" id="session_year_id" class="form-control">
                                    @foreach ($session_year_all as $session_year)
                                        <option value="{{ $session_year->id }}" {{$session_year->default ? "selected" :""}}> {{ $session_year->name }} </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label for="filter_fees_id">{{ __('Fees') }}</label>
                                <select name="filter_fees_id" id="filter_fees_id" class="form-control">
                                    @foreach ($fees as $key => $fee)
                                        <option value="{{ $fee->id }}" {{ $key == 0 ? 'selected' : "" }}>{{ $fee->name}}</option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col">
                                <label for="filter_paid_status"> {{ __('Status') }} </label>
                                <select name="filter_paid_status" id="filter_paid_status" class="form-control">
                                    <option value="0">Unpaid</option>
                                    <option value="1">Paid</option>
                                </select>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('fees.paid.list', 1) }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                               data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-export-data-type='all' data-export-options='{ "fileName": "{{ __('fees') }}-{{ __('paid') }}-{{ __('list') }}-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                               data-show-export="true" data-query-params="feesPaidListQueryParams" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false" data-align="center">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-formatter="totalFeesFormatter" data-sortable="false" data-align="center">{{ __('no.') }}</th>
                                <th scope="col" data-field="student.id" data-sortable="false" data-visible="false" data-align="center">{{ __('Student Id') }}</th>
                                <th scope="col" data-field="full_name" data-sortable="false" data-align="center">{{ __('Student Name') }}</th>
                                <th scope="col" data-field="student.class_section.full_name" data-sortable="false" data-align="center">{{ __('Class') }}</th>
                                <th scope="col" data-field="fees.total_compulsory_fees" data-sortable="false" data-align="center">{{ __('Compulsory Fees') }}</th>
                                <th scope="col" data-field="fees.total_optional_fees" data-sortable="false" data-align="center">{{ __('Optional Fees') }}</th>
                                <th scope="col" data-field="fees_status" data-sortable="false" data-formatter="feesPaidStatusFormatter" data-align="center">{{ __('Fees Status')}}</th>
                                <th scope="col" data-field="fees_paid.date" data-formatter="dateFormatter" data-sortable="false" data-align="center">{{ __('Date') }}</th>
                                {{--                                    <th scope="col" data-field="session_year_name" data-sortable="false" data-align="center">{{ __('Session Years') }}</th>--}}

                                <th scope="col" data-field="operate" data-sortable="false" data-events="feesPaidEvents" data-align="center" data-escape="false">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#session_year_id').on('change', function () {
            let data = new FormData();
            data.append('session_year_id', $(this).val());
            ajaxRequest('GET', baseUrl + '/fees/search', {'session_year_id': $(this).val()}, null, function (response) {
                let feesDropdown = "";
                response.data.forEach(function (value, index) {
                    feesDropdown += "<option value='" + value.id + "'>" + value.name + "</option>";
                })

                $('#filter_fees_id').html(feesDropdown);
                $('#table_list').bootstrapTable('refresh');
            }, null, null, true)
        })
    </script>
@endsection
