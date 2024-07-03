<footer class="commonMT">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-md-6 col-lg-6">
                <div class="companyInfoWrapper">
                    <div>
                        <a href="{{ url('/') }}">
                            <img src="{{ $settings['horizontal_logo'] ?? asset('assets/landing_page_images/Logo1.svg') }}" class="logo" alt="">
                        </a>
                    </div>
                    <div>
                        <span class="commonDesc">
                            {{ $settings['short_description'] ?? '' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-md-6 col-lg-2">
                <div class="linksWrapper usefulLinksDiv">
                    <span class="title">{{ __('links') }}</span>
                    <span><a href="#home">{{ __('home') }}</a></span>
                    <span><a href="#features">{{ __('features') }}</a></span>
                    <span><a href="#pricing">{{ __('pricing') }}</a></span>
                    <span><a href="#faq">{{ __('FAQ') }}</a></span>
                </div>
            </div>

            <div class="col-sm-6 col-md-6 col-lg-2">
                <div class="linksWrapper">
                    <span class="title">{{ __('info') }}</span>
                    <span>
                        <a href="#about-us">
                            {{ __('about_us') }}
                        </a>
                    </span>
                    <span>
                        <a href="#contact-us">
                            {{ __('contact') }}
                        </a>
                    </span>
                </div>
            </div>

            @if (isset($settings['facebook']) || isset($settings['instragram']) || isset($settings['linkedin']))
                    <div class="col-sm-6 col-md-6 col-lg-2">
                        <div class="linksWrapper">
                            <span class="title">{{ __('follow') }}</span>

                            @if (isset($settings['facebook']))
                                <span class="iconsWrapper">
                                    <a href="{{ $settings['facebook'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/facebook.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('facebook') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif

                            @if (isset($settings['instagram']))
                                <span class="iconsWrapper">
                                    <a href="{{ $settings['instagram'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/instagram.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('instagram') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif

                            @if (isset($settings['linkedin']))
                                <span class="iconsWrapper">
                                    <a href="{{ $settings['linkedin'] }}" target="_blank">
                                        <span>
                                            <img src="{{ asset('assets/landing_page_images/linkedin1.svg') }}" alt="">
                                        </span>
                                        <span>
                                            {{ __('linkedin') }}
                                        </span>
                                    </a>
                                </span>    
                            @endif
                        </div>
                    </div>
                @endif

            <hr>

            <div class="col-12 copyright">
                @if (isset($settings['footer_text']))
                    <span class="copyright">{!! $settings['footer_text'] !!}</span>
                @endif
            </div>

        </div>
    </div>
</footer>