@extends('layouts.master')

@section('title')
    {{ __('addons') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('addons') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row pricing-table">
                            @foreach ($addons as $addon)
                                <div class="col-md-6 col-xl-3 grid-margin stretch-card pricing-card">
                                    <div class="card border-primary ribbon  border pricing-card-body">
                                        <div class="text-center pricing-card-head mb-2 text-center">
                                            <h4>{{ $addon->name }}</h4>
                                            <p>{{ __('price') }} : {{ $settings['currency_symbol'] ?? null }} {{ number_format($addon->price, 2) }} </p>
                                            <h1 class="font-weight-normal mb-2"></h1>
                                            <hr>
                                            <div class="text-center">
                                                {{ $addon->feature->name }}
                                            </div>
                                            <hr>
                                        </div>
                                        <div class="wrapper">
                                            @if (in_array($addon->feature_id, $subscibed_addons) || in_array($addon->feature_id, $subscription))
                                                <button disabled data-id="{{ $addon->id }}" class="btn btn-outline-success add-addon btn-block">{{ __('added') }}</button>
                                            @else
                                                <button data-id="{{ $addon->id }}" class="btn btn-outline-success add-addon btn-block">{{ __('add') }}</button>
                                            @endif
                                            
                                        </div>

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
