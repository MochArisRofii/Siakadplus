@extends('layouts.master')

@section('title')
    {{ __('plans') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('subscription') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row pricing-table">
                            @foreach ($packages as $package)
                                <div class="col-md-6 col-xl-4 grid-margin stretch-card pricing-card">
                                    <div
                                        class="card @if ($package->highlight) border-success ribbon @else border-primary @endif  border pricing-card-body">

                                        <div class="text-center pricing-card-head mb-2">
                                            <h3>{{ $package->name }}</h3>
                                            <p>{{ $package->description }}</p>
                                            <h1 class="font-weight-normal mb-2"></h1>
                                            <hr>
                                            <div class="row">
                                                @if ($package->is_trial == 1)
                                                    <div class="col-sm-12 col-md-12">
                                                        <b>Package Information</b>
                                                    </div>
                                                    <div class="col-sm-12 col-md-12 mt-3 text-small">
                                                        Student Limit : {{ $settings['student_limit'] }}
                                                    </div>

                                                    <div class="col-sm-12 col-md-12 mt-1 text-small">
                                                        Staff Limit : {{ $settings['staff_limit'] }}
                                                    </div>
                                                @else
                                                    <div class="col-sm-12 col-md-12">
                                                        <b>Package Price Information</b>
                                                    </div>
                                                    <div class="col-sm-12 col-md-12 mt-3 text-small">
                                                        Per student charges : {{ $settings['currency_symbol'] }} {{ $package->student_charge }}
                                                    </div>

                                                    <div class="col-sm-12 col-md-12 mt-1 text-small">
                                                        Per Staff charges : {{ $settings['currency_symbol'] }} {{ $package->staff_charge }}
                                                    </div>
                                                @endif

                                                <div class="col-sm-12 col-md-12 mt-2">
                                                    @if ($package->is_trial == 1)
                                                        {{ $settings['trial_days'] }} / Days
                                                    @else
                                                        {{ $package->days }} / Days
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                        <hr>

                                        <ul class="list-unstyled">
                                            @foreach ($features as $feature)
                                                @if (in_array($feature->id, $package->package_feature->pluck('feature_id')->toArray()))
                                                    <li><i class="fa fa-check check mr-2"></i>{{ $feature->name }}</li>
                                                @else
                                                    <li><i class="fa fa-times no-feature mr-2"></i><span
                                                            class="text-decoration-line-through">{{ $feature->name }}</span>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                        @if ($current_plan)
                                            @if ($package->id == $current_plan->package_id)
                                                <div class="wrapper mb-3">
                                                    <a href="#" class="btn disabled @if ($package->highlight) btn-success @else btn-outline-primary @endif btn-block select-plan" data-id="{{ $package->id }}">{{ __('current_active_plan') }}</a>
                                                </div>

                                                {{-- Set upcoming --}}
                                                <div class="col-sm-12 col-md-12">
                                                    <a href="#" class="btn disabled @if ($package->highlight) btn-outline-success @else btn-outline-primary @endif btn-block select-plan" data-id="{{ $package->id }}">{{ __('update_upcoming_plan') }}</a>
                                                </div>
                                            @else
                                                <div class="row">
                                                    <div class="col-sm-12 col-md-12 mb-3">
                                                        {{-- Start Immediate plan --}}
                                                        <a href="#" class="btn start-immediate-plan @if ($package->highlight) btn-success @else btn-primary @endif btn-block" data-id="{{ $package->id }}">{{ __('update_current_plan') }}</a>
                                                    </div>

                                                    {{-- Set upcoming --}}
                                                    <div class="col-sm-12 col-md-12">
                                                        <a href="#" class="btn @if ($package->highlight) btn-outline-success @else btn-outline-primary @endif btn-block select-plan" data-id="{{ $package->id }}">{{ __('update_upcoming_plan') }}</a>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <div class="wrapper">
                                                <a href="#" class="btn @if ($package->highlight) btn-success @else btn-outline-primary @endif btn-block select-plan" data-id="{{ $package->id }}">{{ __('get_start') }}</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
