@extends('layouts.master')

@section('title')
    {{ __('front_site_settings') }}
@endsection


@section('content')
    {{-- student App Settings --}}
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('front_site_settings') }}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form id="formdata" method="post" class="create-form-without-reset" action="{{ route('system-settings.front-site-settings.update') }}" novalidate="novalidate" enctype="multipart/form-data">
                            @csrf
                            <div class="border border-secondary rounded-lg my-4 mx-1">
                                <div class="col-md-12 mt-3">
                                    <h4>{{ __('Themes Settings') }}</h4>
                                </div>
                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                </div>
                                
                                <div class="row my-4 mx-1">
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="front_site_theme_color">{{ __('theme_color') }} <span class="text-danger">*</span></label>
                                        <input name="front_site_theme_color"
                                            value="{{ $settings['front_site_theme_color'] ?? '' }}" type="text" required
                                            placeholder="{{ __('theme_color') }}" id="theme_color" class="theme_color" />
                                        <small>
                                            <a href="javascript:null" onclick="restore_theme_color()">{{__('restore_default')}}</a>
                                        </small>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="primary_color">{{ __('primary_color') }} <span class="text-danger">*</span></label>
                                        <input name="primary_color" id="primary_color"
                                            value="{{ $settings['primary_color'] ?? '' }}" type="text" required
                                            placeholder="{{ __('primary_color') }}" class="primary_color" />
                                        <small>
                                            <a href="javascript:null" onclick="restore_primary_color()">{{__('restore_default')}}</a>
                                        </small>
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="secondary_color">{{ __('secondary_color') }} <span class="text-danger">*</span></label>
                                        <input name="secondary_color" id="secondary_color"
                                            value="{{ $settings['secondary_color'] ?? '' }}" type="text" required
                                            placeholder="{{ __('secondary_color') }}" class="secondary_color" />
                                        <small>
                                            <a href="javascript:null" onclick="restore_secondary_color()">{{__('restore_default')}}</a>
                                        </small>
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6">
                                        <label for="">{{ __('short_description') }}</label>
                                        {!! Form::textarea('short_description', $settings['short_description'] ?? null, ['class' => 'form-control', 'placeholder' => __('short_description')]) !!}
                                        
                                    </div>

                                    <div class="form-group col-sm-12 col-md-6 mt-3">
                                        <label for="image">{{ __('home_image') }} </label>
                                        <input type="file" name="home_image" class="file-upload-default" />
                                        <div class="input-group col-xs-12">
                                            <input type="text" class="form-control file-upload-info"
                                                id="home_image" disabled=""
                                                placeholder="{{ __('home_image') }}" />
                                            <span class="input-group-append">
                                                <button class="file-upload-browse btn btn-theme"
                                                    type="button">{{ __('upload') }}</button>
                                            </span>
                                            <div class="col-md-12 mt-2">
                                                <img height="50px" src='{{ $settings['home_image'] ?? '' }}'
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="border border-secondary rounded-lg my-4 mx-1">
                                <div class="col-md-12 mt-3">
                                    <h4>{{ __('Footer Settings') }}</h4>
                                </div>
                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                    <h5>{{ __('social_media_links') }}</h5>
                                    <hr class="mt-3">
                                </div>
                                <div class="row my-4 mx-1">
                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="facebook">{{ __('facebook') }}</label>
                                        <input name="facebook" id="facebook" value="{{ $settings['facebook'] ?? '' }}" type="text" placeholder="{{ __('facebook') }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="instagram">{{ __('instagram') }}</label>
                                        <input name="instagram" id="instagram" value="{{ $settings['instagram'] ?? '' }}" type="text" placeholder="{{ __('instagram') }}" class="form-control" />
                                    </div>

                                    <div class="form-group col-md-6 col-lg-6 col-xl-4 col-sm-12">
                                        <label for="linkedin">{{ __('linkedin') }} </label>
                                        <input name="linkedin" id="linkedin" value="{{ $settings['linkedin'] ?? '' }}" type="text" placeholder="{{ __('linkedin') }}" class="form-control" />
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <hr class="mt-0">
                                    <h5>{{ __('footer_text') }}</h5>
                                </div>

                                <div class="row my-4 mx-1">
                                    <div class="col-sm-12 col-md-12">
                                        <textarea id="tinymce_message" name="footer_text" id="footer_text" required placeholder="{{__('footer_text')}}">{{$settings['footer_text'] ?? ''}}</textarea>
                                    </div>
                                </div>                                
                            </div>
                            <hr>
                                <input class="btn btn-theme" type="submit" value="Submit">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function restore_theme_color()
        {
            $('#theme_color').val('#E9F9F3');
            $('.theme_color').asColorPicker('val', '#E9F9F3');
            
        }
        function restore_primary_color()
        {
            $('#primary_color').val('#0CAE74');
            $('.primary_color').asColorPicker('val', '#3CCB9B');
        }
        function restore_secondary_color()
        {
            $('#secondary_color').val('#245A7F');
            $('.secondary_color').asColorPicker('val', '#245A7F');
        }
    </script>
@endsection
