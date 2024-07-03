@extends('layouts.master')

@section('title')
    {{ __('student_id_card') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-8 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('student_id_card') }} {{ __('settings') }}
                        </h4>
                        <form class="pt-3 create-form" id="create-form" action="{{ url('/') }}" method="POST" novalidate="novalidate">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="header_color">{{ __('header_color') }} <span class="text-danger">*</span></label>
                                    <input name="header_color" id="header_color" value="{{ $settings['header_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="footer_color">{{ __('footer_color') }} <span class="text-danger">*</span></label>
                                    <input name="footer_color" id="footer_color" value="{{ $settings['footer_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label for="header_footer_color">{{ __('header_footer_color') }} <span class="text-danger">*</span></label>
                                    <input name="header_footer_color" id="header_footer_color" value="{{ $settings['header_footer_color'] ?? '' }}" type="text" required placeholder="{{ __('color') }}" class="color-picker"/>
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
                            </div>
                            <input class="btn btn-theme" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4 grid-margin">
                <div class="card">
                    <div class="card-body">
                        
                    </div>
                </div>
            </div>
            
        </div>
    </div>
@endsection
@section('script')
    
@endsection
