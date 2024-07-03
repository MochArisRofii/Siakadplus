@extends('layouts.master')
@section('title')
    {{ __('dashboard') }}
@endsection
@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-theme text-white mr-2">
                    <i class="fa fa-home"></i>
                </span> {{ __('dashboard') }}
            </h3>
        </div>
        {{-- School Dashboard --}}
        @if (Auth::user()->hasRole('School Admin') || Auth::user()->hasRole('Teacher') || Auth::user()->school_id)
            <div class="row">
                {{-- License expire message --}}
                @if (Auth::user()->hasRole('School Admin'))
                    @if ($license_expire <= ($settings['current_plan_expiry_warning_days'] ?? 0) && $subscription)
                        <div class="col-sm-12 col-md-12">
                            <div class="alert alert-danger" role="alert">
                                <li>
                                    {{ __('Kindly note that your license will expire on') }} <strong class="package-expire-date">
                                        {{ date('F d, Y', strtotime($subscription->end_date)) }} - 11:59 PM. </strong>
                                    {{ __('If you want to modify your upcoming plan or remove any add-ons, please ensure that these changes are made before your current license expires') }}.
                                </li>
                                <li class="mt-2">
                                    {{ __('If you want to activate or deactivate students, teachers, or staff members in your upcoming plan, Please') }}
                                    <a href="{{ url('users/status') }}">{{ __('click here') }}.</a>
                                </li>

                            </div>
                        </div>
                    @endif

                    <div class="col-sm-12 col-md-12">
                        @foreach ($previous_subscriptions as $subscription)
                            @if ($subscription->status == 3)
                                <div class="alert alert-danger" role="alert">
                                    {{ __('Please make the necessary payment as your license has expired on') }} <strong
                                        class="package-expire-date"> {{ date('F d, Y', strtotime($subscription->end_date)) }}.
                                    </strong>
                                </div>
                                @break
                            @endif
                            @if ($subscription->status == 4)
                                <div class="alert alert-danger" role="alert">
                                    {{ __('We apologize for inconvenience but your payment was not successful Please try to process the payment again') }}.
                                </div>
                                @break
                            @endif
                        @endforeach
                    </div>
                @endif
                @if (Auth::user()->hasRole('School Admin'))
                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-danger card-img-holder text-white">
                            <div class="card-body custom-card-body">
                                <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                     alt="circle-image"/>
                                <h4 class="font-weight-normal mb-3">{{ __('total_teachers') }}
                                    <i class="mdi mdi-account-network mdi-24px float-right"></i>
                                </h4>
                                <h2 class="mb-5">{{ $teacher }}</h2>
                                {{-- <h6 class="card-text">Increased by 60%</h6> --}}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-info card-img-holder text-white">
                            <div class="card-body custom-card-body">
                                <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                     alt="circle-image"/>
                                <h4 class="font-weight-normal mb-3">{{ __('total_students') }}<i
                                        class="mdi mdi-account-plus mdi-24px float-right"></i>
                                </h4>
                                <h2 class="mb-5">{{ $student }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 stretch-card grid-margin">
                        <div class="card bg-gradient-success card-img-holder text-white">
                            <div class="card-body custom-card-body">
                                <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                     alt="circle-image"/>
                                <h4 class="font-weight-normal mb-3">{{ __('Total Guardians') }}<i
                                        class="mdi mdi-account-multiple mdi-24px float-right"></i>
                                </h4>
                                <h2 class="mb-5">{{ $parent }}</h2>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="row">

                {{-- <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body v-scroll">
                            <h4 class="card-title">{{ __('teacher') }}</h4>
                            @if (!empty($teachers))
                                @foreach ($teachers as $row)
                                    <div class="wrapper d-flex align-items-center py-2 border-bottom">
                                        <img class="img-sm rounded-circle" src="{{ $row->image }}" alt="profile">
                                        <div class="wrapper ml-3">
                                            <h6 class="ml-1 mb-1">{{ $row->first_name . ' ' . $row->last_name }}</h6>
                                            <small class="text-muted mb-0">&nbsp;{{ $row->teacher->qualification }}</small>
                                        </div>
                                        <div class="badge badge-pill badge-success ml-auto px-1 py-1">
                                            <i class="mdi mdi-check"></i>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div> --}}

                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <h4 class="card-title">{{ __('holiday') }}</h4>
                            <div class="v-scroll dashboard-description">
                                @if (count($holiday))
                                    @foreach ($holiday as $holiday)
                                        <div class="col-md-12 bg-light p-2 mb-2">
                                            <span>{{ $holiday->title }}</span>
                                            <span class="float-right text-muted">{{ date('d - M',strtotime($holiday->date)) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-md-12 text-center bg-light p-2 mb-2">
                                        <span>{{ __('no_holiday_found') }}.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <h4 class="card-title">{{ __('leaves') }}</h4>
                            <div class="v-scroll dashboard-description">
                                <div class="mb-3">
                                    {!! Form::select('filter_leave', ['Today' => 'Today', 'Tomorrow' => 'Tomorrow', 'Upcoming' => 'Upcoming'], 'Today', ['class' => 'form-control form-control-sm filter-leave']) !!}
                                </div>
                                <div class="leave-details">
                                    @if (count($leaves))
                                        @foreach ($leaves as $leave)
                                        @if (count($leave->leave_detail))
                                        <div class="col-md-12 bg-light p-1 mb-2">
                                            <span>{{ $leave->user->full_name }}</span>
                                            @if ($leave->leave_detail->first()->type == "Full")
                                                <div class="badge custom-badge badge-danger">{{ $leave->leave_detail->first()->type }}</div>
                                            @endif
                                            @if ($leave->leave_detail->first()->type == "First Half")
                                                <div class="badge custom-badge badge-primary">{{ $leave->leave_detail->first()->type }}</div>
                                            @endif
                                            @if ($leave->leave_detail->first()->type == "Second Half")
                                                <div class="badge custom-badge badge-info">{{ $leave->leave_detail->first()->type }}</div>
                                            @endif
                                            <span class="float-right text-muted">{{ date('d - M',strtotime($leave->leave_detail->first()->date)) }}</span>
                                        </div>
                                        @endif
                                        @endforeach
                                    @else
                                        <div class="col-md-12 text-center bg-light p-2 mb-2">
                                            <span>{{ __('no_data_found') }}.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body custom-card-body">
                            <h4 class="card-title">{{ __('gender') }}</h4>
                            <canvas id="gender-ratio-chart"></canvas>
                            <div id="gender-ratio-chart-legend"
                                 class="rounded-legend legend-vertical legend-bottom-left pt-4"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Expense Graph --}}
                @if (Auth::user()->can('expense-create'))
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body custom-card-body">
                                <h4 class="card-title">{{ __('expense') }}</h4>
                                <div class="chartjs-wrapper mt-5" style="height: 330px">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Teacher's Today Schedule #Timetable --}}
                @if (Auth::user()->hasRole('Teacher'))
                    <div class="col-md-6 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body custom-card-body">
                                <div class="clearfix">
                                    <h4 class="card-title float-left">{{ __('today_schedule') }}</h4>
                                </div>
                                <div class="v-scroll">
                                    @foreach ($timetables as $timetable)
                                        <div
                                            class="wrapper mb-2 d-flex align-items-center justify-content-between py-2 border-bottom">
                                            <div class="d-flex">
                                                <div class="wrapper ms-3">
                                                    <h5>{{ $timetable->start_time }} - {{ $timetable->end_time }}</h5>
                                                    <span
                                                        class="text-small text-muted">{{ $timetable->subject->name_with_type }}</span>
                                                </div>
                                            </div>
                                            <span class="text-muted mr-2">{{ $timetable->class_section->full_name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Class section wise attendance --}}
                <div class="col-md-6 d-flex flex-column">
                    <div class="row flex-grow">
                        <div class="col-12 col-lg-4 col-lg-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body custom-card-body">
                                    <div class="clearfix">
                                        <h4 class="card-title float-left">{{ __('class') }} {{ __('attendance') }}</h4>
                                        <div id="performance-line-legend" class="rounded-legend legend-horizontal legend-top-right float-right"></div>
                                    </div>
                                    <div class="chartjs-wrapper mt-5" style="height: 310px">
                                        <canvas id="class-section-attendance"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card search-container">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('noticeboard') }}</h4>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th> {{ __('no.') }}</th>
                                        <th class="col-md-2"> {{ __('title') }}</th>
                                        <th> {{ __('description') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @if (!empty($announcement))
                                        @foreach ($announcement as $key => $row)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $row->title }}</td>
                                                <td>{{ $row->description }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- End School Dashboard --}}

        {{-- Super Admin Dashboard --}}
        @if (Auth::user()->hasRole('Super Admin') || !Auth::user()->school_id)
            <div class="row">
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-info card-img-holder text-white">
                        <div class="card-body custom-card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                 alt="circle-image"/>
                            <h4 class="font-weight-normal mb-3">{{ __('total_schools') }}<i
                                    class="mdi mdi-school mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['total_school'] }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-success card-img-holder text-white">
                        <div class="card-body custom-card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                 alt="circle-image"/>
                            <h4 class="font-weight-normal mb-3">{{ __('active_schools') }}<i
                                    class="mdi mdi-check mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['active_school'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 stretch-card grid-margin">
                    <div class="card bg-gradient-danger card-img-holder text-white">
                        <div class="card-body custom-card-body">
                            <img src="{{ asset(config('global.CIRCLE_SVG')) }}" class="card-img-absolute"
                                 alt="circle-image"/>
                            <h4 class="font-weight-normal mb-3">{{ __('inactive_schools') }}<i
                                    class="mdi mdi-close mdi-24px float-right"></i>
                            </h4>
                            <h2 class="mb-5">{{ $super_admin['deactive_school'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('pending') }} {{ __('bills') }}
                            </h4>

                            <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                   data-url="{{ url('subscriptions/report/show', 1) }}" data-click-to-select="true"
                                   data-side-pagination="server" data-pagination="false"
                                   data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                                   data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                   data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                   data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                   data-maintain-selected="true" data-query-params="subscriptionReportQueryParams"
                                   data-show-export="true" data-escape="true"
                                   data-export-options='{"fileName": "pending-bill-list-<?= date('d-m-y') ?>","ignoreColumn":
                                ["operate"]}'>
                                <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="logo" data-formatter="imageFormatter">{{ __('logo') }}</th>
                                    <th scope="col" data-field="school_name">{{ __('school_name') }}</th>
                                    <th scope="col" data-field="plan" data-formatter="planDetailFormatter">{{ __('plan') }}</th>
                                    <th scope="col" data-field="bill_date">{{ __('bill_date') }} </th>
                                    <th scope="col" data-field="amount">{{ __('bill_amount') }}({{ $settings['currency_symbol'] }})</th>
                                    <th scope="col" data-field="status" class="text-center" data-formatter="subscriptionStatusFormatter">{{ __('status') }} </th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
@section('script')
    @if ($boys || $girls)
        <script>
            (function ($) {
                'use strict';
                $(function () {
                    Chart.defaults.global.legend.labels.usePointStyle = true;
                    if ($("#gender-ratio-chart").length) {
                        let ctx = document.getElementById('gender-ratio-chart').getContext("2d")
                        let gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 181);
                        gradientStrokeBlue.addColorStop(0, 'rgba(54, 215, 232, 1)');
                        gradientStrokeBlue.addColorStop(1, 'rgba(177, 148, 250, 1)');
                        let gradientLegendBlue =
                            'linear-gradient(to right, rgba(54, 215, 232, 1), rgba(177, 148, 250, 1))';

                        let gradientStrokeRed = ctx.createLinearGradient(0, 0, 0, 50);
                        gradientStrokeRed.addColorStop(0, 'rgba(255, 191, 150, 1)');
                        gradientStrokeRed.addColorStop(1, 'rgba(254, 112, 150, 1)');
                        let gradientLegendRed =
                            'linear-gradient(to right, rgba(255, 191, 150, 1), rgba(254, 112, 150, 1))';
                        let trafficChartData = {
                            datasets: [{
                                data: [{{ $boys }}, {{ $girls }}],
                                backgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                hoverBackgroundColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                borderColor: [
                                    gradientStrokeBlue,
                                    gradientStrokeRed
                                ],
                                legendColor: [
                                    gradientLegendBlue,
                                    gradientLegendRed
                                ]
                            }],

                            // These labels appear in the legend and in the tooltips when hovering different arcs
                            labels: [
                                "{{ __('boys') }}",
                                "{{ __('girls') }}"
                            ]
                        };
                        let trafficChartOptions = {
                            responsive: true,
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            },
                            legend: false,
                            legendCallback: function (chart) {
                                let text = [];
                                text.push('<ul>');
                                for (let i = 0; i < trafficChartData.datasets[0].data.length; i++) {
                                    text.push('<li><span class="legend-dots" style="background:' +
                                        trafficChartData.datasets[0].legendColor[i] + '"></span>');
                                    if (trafficChartData.labels[i]) {
                                        text.push(trafficChartData.labels[i]);
                                    }
                                    text.push('<span class="float-right">' + trafficChartData.datasets[0]
                                        .data[i] + "%" + '</span>')
                                    text.push('</li>');
                                }
                                text.push('</ul>');
                                return text.join('');
                            }
                        };
                        let trafficChartCanvas = $("#gender-ratio-chart").get(0).getContext("2d");
                        let trafficChart = new Chart(trafficChartCanvas, {
                            type: 'doughnut',
                            data: trafficChartData,
                            options: trafficChartOptions
                        });
                        $("#gender-ratio-chart-legend").html(trafficChart.generateLegend());
                    }
                    if ($("#inline-datepicker").length) {
                        $('#inline-datepicker').datepicker({
                            enableOnReadonly: true,
                            todayHighlight: true,
                        });
                    }
                });
            })(jQuery);
        </script>
    @endif

    <script>
        setTimeout(() => {
            expense_graph(<?php echo json_encode($expense_months); ?>, <?php echo json_encode($expense_amount); ?>);
        }, 1500);

        setTimeout(() => {
            class_attendance(<?php echo json_encode($classData ?? []); ?>);
        }, 1500);

    </script>
@endsection
