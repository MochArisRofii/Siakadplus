<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('login') }} || {{ config('app.name') }}</title>

    @include('layouts.include')

</head>

<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper login-d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-xl-6 mx-auto">
                        @if (env('DEMO_MODE'))
                        <div class="alert alert-info text-center" role="alert">
                            NOTE : <a target="_blank" href="https://eschool-saas.wrteam.me/login">-- Click Here --</a> if you cannot login.
                        </div>
                        @endif
                        <div class="auth-form-light rounded-lg text-left p-5">
                            <div class="brand-logo text-center">
                                <img src="{{ $systemSettings['login_page_logo'] ?? url('assets/horizontal-logo.svg') }}"
                                    alt="logo">
                            </div>
                            <div class="mt-3">
                                @if (\Session::has('success'))
                                    <div class="alert alert-success text-center" role="alert">
                                        {{ \Session::get('success') }}.
                                    </div>
                                    <div class="alert alert-success text-center mt-2" role="alert">
                                        Please use your registered your email for login, and your contact number as the password.
                                    </div>
                                @endif

                                @if (\Session::has('error'))
                                    <div class="alert alert-danger text-center" role="alert">
                                        {{ \Session::get('error') }}.
                                    </div>

                                @endif
                            </div>
                            <form action="{{ route('login') }}" id="frmLogin" method="POST" class="pt-3">
                                @csrf
                                <div class="form-group">
                                    <label for="email">{{ __('email') }}</label>
                                    <input id="email" type="email" class="form-control rounded-lg form-control-lg"
                                        name="email" value="{{ old('email') }}" required autocomplete="email"
                                        autofocus placeholder="{{ __('email') }}">
                                </div>
                                <div class="form-group">
                                    <label for="password">{{ __('password') }}</label>
                                    <div class="input-group">
                                        <input id="password" type="password"
                                            class="form-control rounded-lg form-control-lg" name="password" required
                                            autocomplete="current-password" placeholder="{{ __('password') }}">
                                        <div class="input-group-append" cursor="pointer" id="togglePasswordShowHide">
                                            <span class="input-group-text">
                                                <i class="fa fa-eye-slash" id="togglePassword"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if (Route::has('password.request'))
                                    <div class="my-2 d-flex justify-content-end align-items-center">
                                        <a class="auth-link text-black" href="{{ route('password.request') }}">
                                            {{ __('forgot_password') }}
                                        </a>
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <input type="submit" name="btnlogin" id="login_btn" value="{{ __('login') }}"
                                        class="btn btn-block btn-theme btn-lg font-weight-medium auth-form-btn rounded-lg" />
                                </div>
                            </form>
                            @if (env('DEMO_MODE'))
                                <div class="row mt-3">
                                    <hr class="w-100">
                                    <div class="col-12 text-center mb-4 text-black-50">Demo Credentials</div>
                                </div>
                                <div class="col-12 text-center">
                                    Super Admin Panels
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button class="btn w-100 btn-success mt-2" id="superadmin_btn">Super Admin</button>
                                    </div>

                                    <div class="col-md-6">
                                        <button class="btn w-100 btn-info mt-2" id="superadmin_staff_btn">Staff</button>
                                    </div>
                                </div>

                                <div class="col-12 text-center mt-3">
                                    <hr class="w-100">
                                    School Admin Panels
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-info mt-2" id="schooladmin_btn">School Admin</button>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-danger mt-2" id="teacher_btn">Teacher</button>
                                    </div>

                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-primary mt-2" id="schooladmin_staff_btn">Staff</button>
                                    </div>
                                </div>

                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- content-wrapper ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>

    <script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>

    <script type='text/javascript'>
        $("#frmLogin").validate({
            rules: {
                username: "required",
                password: "required",
            },
            success: function(label, element) {
                $(element).parent().removeClass('has-danger')
                $(element).removeClass('form-control-danger')
            },
            errorPlacement: function(label, element) {
                if (label.text()) {
                    if ($(element).attr("name") == "password") {
                        label.insertAfter(element.parent()).addClass('text-danger mt-2');
                    } else {
                        label.addClass('mt-2 text-danger');
                        label.insertAfter(element);
                    }
                }
            },
            highlight: function(element, errorClass) {
                $(element).parent().addClass('has-danger')
                $(element).addClass('form-control-danger')
            }
        });

        const togglePassword = document.querySelector("#togglePasswordShowHide");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function() {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // this.classList.toggle("fa-eye");
            if (password.getAttribute("type") === 'password') {
                $('#togglePassword').addClass('fa-eye-slash');
                $('#togglePassword').removeClass('fa-eye');
            } else {
                $('#togglePassword').removeClass('fa-eye-slash');
                $('#togglePassword').addClass('fa-eye');
            }
        });

        @if (env('DEMO_MODE'))
        // Super admin panel
            $('#superadmin_btn').on('click', function(e) {
                $('#email').val('superadmin@gmail.com');
                $('#password').val('superadmin');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            $('#superadmin_staff_btn').on('click', function(e) {
                $('#email').val('mahesh@gmail.com');
                $('#password').val('staff@123');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            // School Panel
        $('#schooladmin_btn').on('click', function (e) {
            $('#email').val('school1@gmail.com');
            $('#password').val('school@123');
            $('#login_btn').attr('disabled', true);
            $(this).attr('disabled', true);
            $('#frmLogin').submit();
        })
            $('#teacher_btn').on('click', function(e) {
                $('#email').val('teacher@gmail.com');
                $('#password').val('teacher@123');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            $('#schooladmin_staff_btn').on('click', function(e) {
                $('#email').val('smit@gmail.com');
                $('#password').val('staff@123');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })
        @endif
    </script>
</body>

@if (Session::has('error'))
    <script type='text/javascript'>
        $.toast({
            text: '{{ Session::get('error') }}',
            showHideTransition: 'slide',
            icon: 'error',
            loaderBg: '#f2a654',
            position: 'top-right'
        });
    </script>
@endif

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script type='text/javascript'>
            $.toast({
                text: '{{ $error }}',
                showHideTransition: 'slide',
                icon: 'error',
                loaderBg: '#f2a654',
                position: 'top-right'
            });
        </script>
    @endforeach
@endif

</html>
