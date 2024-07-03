@extends('layouts.master')

@section('title')
    {{ __('package') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('package') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title float-left">
                            {{ __('create') . ' ' . __('package') }}
                        </h4>
                        <div class="row">
                            <div class="col-sm-12 col-md-12 text-right">
                                <a href="{{ route('package.index') }}" class="btn btn-theme btn-sm">{{ __('back') }}</a>
                            </div>
                        </div>
                        <hr>
                        <form action="{{ route('package.store') }}" method="post" class="create-form" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="">{{ __('name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('name', null, [
                                        'required',
                                        'class' => 'form-control',
                                        'placeholder' => __('package') . ' ' . __('name'),
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="">{{ __('description') }}</label>
                                    {!! Form::textarea('description', null, [
                                        'class' => 'form-control',
                                        'placeholder' => __('description'),
                                        'rows' => '3',
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="">{{ __('tagline') }}</label>
                                    {!! Form::text('tagline', null, ['class' => 'form-control', 'placeholder' => __('tagline')]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-2">
                                    <label for="" class="day-label">{{ __('days') }}</label> <span class="text-danger">*</span>
                                    {!! Form::number('days', null, [
                                        'required',
                                        'class' => 'form-control days',
                                        'min' => 1,
                                        'placeholder' => __('days'),
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-2">
                                    <label for=""
                                        class="student-label">{{ __('per_active_student_charges') }}</label> <span
                                        class="text-danger">*</span>
                                    {!! Form::number('student_charge', null, [
                                        'required',
                                        'class' => 'form-control student-input',
                                        'min' => 0,
                                        'placeholder' => __('per_active_student_charges'),
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-2">
                                    <label for="" class="staff-label">{{ __('per_active_staff_charges') }}</label>
                                    <span class="text-danger">*</span>
                                    {!! Form::number('staff_charge', null, [
                                        'required',
                                        'class' => 'form-control staff-input',
                                        'min' => 0,
                                        'placeholder' => __('per_active_staff_charges'),
                                    ]) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12 col-md-3">
                                    <div class="form-check">
                                        <label class="form-check-label d-inline">
                                            <input type="checkbox" class="form-check-input" name="highlight"
                                                value="1">{{ __('highlight') }} {{ __('package') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            {{-- Feature --}}
                            <div class="row">
                                <div class="col-sm-12 col-md-12 mb-3">
                                    <h4 class="card-title">{{ __('features') }}</h4>
                                </div>
                                @foreach ($features as $feature)
                                    <div class="form-group col-sm-12 col-md-3">
                                        {{-- Default Features --}}
                                        @if ($feature->is_default == 1)
                                            <input id="{{ __($feature->name) }}" class="feature-checkbox" @if($feature->is_default == 1) checked disabled @endif type="checkbox" name="feature_id[]" value="{{ $feature->id }}"/>
                                            <label class="feature-list-default text-center" for="{{ __($feature->name) }}" title="{{ __('default_feature') }}">{{ __($feature->name) }}</label>
                                            <input type="hidden" name="feature_id[]" value="{{ $feature->id }}">

                                        @else
                                            <input id="{{ __($feature->name) }}" class="feature-checkbox" type="checkbox" name="feature_id[]" value="{{ $feature->id }}"/>
                                            <label class="feature-list text-center" for="{{ __($feature->name) }}">{{ __($feature->name) }}</label>
                                        @endif
                                    </div>
                                @endforeach

                            </div>
                            <hr>
                            <input type="submit" class="btn btn-theme" value="{{ __('submit') }}">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

