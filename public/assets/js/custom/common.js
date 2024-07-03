/*
* Common JS is used to write code which is generally used for all the UI components
* Specific component related code won't be written here
*/

"use strict";
$(document).ready(function () {
    // $('#toolbar').parent().addClass('col-12  col-md-7 col-lg-7 p-0');
    $('#table_list').on('all.bs.table', function () {
        $('#toolbar').parent().addClass('col-12  col-md-7 col-lg-7 p-0');
    })
    // $('#table_list').on('load-success.bs.table', function () {
    //
    //     $('#toolbar').parent().addClass('col-12  col-md-7 col-lg-7 p-0');
    // })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
})

//Setup CSRF Token default in AJAX Request
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$('#create-form,.create-form,.create-form-without-reset').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let url = $(this).attr('action');

    setTimeout(() => {
        let data = new FormData(this);
        let preSubmitFunction = $(this).data('pre-submit-function');
        if (preSubmitFunction) {
            //If custom function name is set in the Form tag then call that function using eval
            eval(preSubmitFunction + "()");
        }
        let customSuccessFunction = $(this).data('success-function');
        // noinspection JSUnusedLocalSymbols
        function successCallback(response) {
            if (!$(formElement).hasClass('create-form-without-reset')) {
                formElement[0].reset();
                $(".select2-dropdown").val("").trigger('change').trigger('unselect');
                $('.stream-divs').slideUp(500);
                $('[data-repeater-item]').slice(1).remove();
            }
            setTimeout(() => {
                $('#tags').removeTag();
            }, 500);
            $('#table_list').bootstrapTable('refresh');
            if (customSuccessFunction) {
                //If custom function name is set in the Form tag then call that function using eval
                eval(customSuccessFunction + "(response)");
            }

        }

        formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
    }, 300);
})
// create-form-without-reset-text-editor
$('.create-form-without-reset-text-editor').on('submit', function (e) {
    e.preventDefault();
    setTimeout(() => {
        let request_data = new FormData(this);
        // let data = request_data.get('data');
        let formElement = $(this);
        let submitButtonElement = $(this).find(':submit');
        let url = $(this).attr('action');
        formAjaxRequest('POST', url, request_data, formElement, submitButtonElement);
    }, 1000);

})

$('#edit-form,.edit-form').on('submit', function (e) {
    e.preventDefault();
    let formElement = $(this);
    let submitButtonElement = $(this).find(':submit');
    let data = new FormData(this);
    data.append("_method", "PUT");
    // let url = $(this).attr('action') + "/" + data.get('edit_id');
    let url = $(this).attr('action');
    let preSubmitFunction = $(this).data('pre-submit-function');
    if (preSubmitFunction) {
        //If custom function name is set in the Form tag then call that function using eval
        eval(preSubmitFunction + "()");
    }
    let customSuccessFunction = $(this).data('success-function');

    // noinspection JSUnusedLocalSymbols
    function successCallback(response) {
        $('#table_list').bootstrapTable('refresh');
        setTimeout(function () {
            $('#editModal').modal('hide');
            $('#change-bill').modal('hide');
            $('#viewModal').modal('hide');
            
            $('#update-current-plan').modal('hide');
            $('#tags').removeTag();
            formElement[0].reset();

        }, 1000)
        if (customSuccessFunction) {
            //If custom function name is set in the Form tag then call that function using eval
            eval(customSuccessFunction + "(response)");
        }
    }

    formAjaxRequest('POST', url, data, formElement, submitButtonElement, successCallback);
})

$(document).on('click', '.delete-form', function (e) {
    e.preventDefault();
    showDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }, errorCallBack: function (response) {
            // showErrorToast(response.message);
        }
    })
})

$(document).on('click', '.restore-data', function (e) {
    e.preventDefault();
    showRestorePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }
    })
})

$(document).on('click', '.trash-data', function (e) {
    e.preventDefault();
    showPermanentlyDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            $('#table_list').bootstrapTable('refresh');
        }
    })
})

$(document).on('click', '.set-form-url', function (e) {
    //This event will be called when user clicks on the edit button of the bootstrap table
    e.preventDefault();
    $('#edit-form,.edit-form').attr('action', $(this).attr('href'));

})

$(document).on('click', '.delete-form-reload', function (e) {
    e.preventDefault();
    showDeletePopupModal($(this).attr('href'), {
        successCallBack: function () {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    })
})

$(document).on('click', '.change-school-status', function (e) {
    e.preventDefault();
    let school_id = $(this).data('id');
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["change_school_status"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes, Change it"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/schools/change/status/' + school_id;
            let data = null;

            function successCallback(response) {
                $('#table_list').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('PUT', url, data, null, successCallback, errorCallback);
        }
    })
})

$(document).on('click', '.change-package-status', function (e) {
    e.preventDefault();
    let package_id = $(this).data('id');
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["change_package_status"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes, Change it"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/package/status/' + package_id;
            let data = null;

            function successCallback(response) {
                $('#table_list').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('GET', url, data, null, successCallback, errorCallback);
        }
    })
})

$(document).on('click', '.change-addon-status', function (e) {
    e.preventDefault();
    let addon_id = $(this).data('id');
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["change_addon_status"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes, Change it"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/addons/status/' + addon_id;
            let data = null;

            function successCallback(response) {
                $('#table_list').bootstrapTable('refresh');
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('PUT', url, data, null, successCallback, errorCallback);
        }
    })
})


$(document).on('click', '.cancel-upcoming-plan', function (e) {
    e.preventDefault();
    let id = $(this).data('id');
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["Cancel This Plan"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/subscriptions/cancel-upcoming/' + id;
            let data = null;

            function successCallback(response) {
                showSuccessToast(response.message);
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('GET', url, data, null, successCallback, errorCallback);
        }
    })
})

$(document).on('click', '.select-plan', function (e) {
    e.preventDefault();
    let id = $(this).data('id');
    let link = baseUrl + '/school-terms-condition';

    Swal.fire({
        title: window.trans["Are you sure"],
        html: window.trans["Agree to This Subscription"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"],
        input: 'checkbox',
        inputPlaceholder: '<div class="m-2">'+ window.trans['I accept the provided'] + '<a href="'+ link +'" target="_blank"> '+window.trans['terms_condition']+' </a></div>',


    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value) {
                let url = baseUrl + '/subscriptions/plan/' + id;
                let data = null;

                function successCallback(response) {
                    if (response.data) {
                        let package_id = response.data.package_id;
                        let package_type = response.data.plan
                        confirm_upcoming_plan(package_id, package_type);
                    } else {
                        showSuccessToast(response.message);
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }

                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('GET', url, data, null, successCallback, errorCallback);
            } else {
                Swal.fire({icon: 'error', text: window.trans['please_confirm_terms_condition']});
            }

        }
    })

})

function confirm_upcoming_plan(package_id, package_type = null) {
    let id = package_id;
    let type = package_type;
    let message = window.trans["Accept Upcoming Billing Cycle Subscription"];
    if (type == 'Trial') {
        message = window.trans["You want to go ahead with this plan?"];
    }
    Swal.fire({
        title: window.trans["Are you sure"],
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/subscriptions/confirm-upcoming-plan/' + id;
            let data = null;

            function successCallback(response) {
                showSuccessToast(response.message);
                setTimeout(() => {
                    window.location.href = baseUrl + '/subscriptions/history';
                }, 3000);
            }

            function errorCallback(response) {
                let message = window.trans["You have already selected an upcoming billing cycle plan If you want to change your upcoming billing cycle plan please ensure to remove the previously selected plan before making any alterations"];

                if (response.data == 0) {
                    message = response.message;
                }
                already_added_upcoming_plan(message);
            }

            ajaxRequest('GET', url, data, null, successCallback, errorCallback);
        }
    })
}

function already_added_upcoming_plan(message) {
    Swal.fire({
        text: message,
        icon: 'error',
    })
}

// Start immediate plan if currently working on another plan
$(document).on('click', '.start-immediate-plan', function (e) {
    e.preventDefault();
    let id = $(this).data('id');
    let link = baseUrl + '/school-terms-condition';
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["start_immediate_this_plan"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"],
        input: 'checkbox',
        inputPlaceholder: '<div class="m-2">'+ window.trans['I accept the provided'] + '<a href="'+ link +'" target="_blank"> '+window.trans['terms_condition']+' </a></div>',
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value) { 
                let url = baseUrl + '/subscriptions/start-immediate-plan/' + id;
                let data = null;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }
                ajaxRequest('GET', url, data, null, successCallback, errorCallback);
            } else {
                Swal.fire({icon: 'error', text: window.trans['please_confirm_terms_condition']});
            }            
        }
    })
})

// add-addon
$(document).on('click', '.add-addon', function (e) {
    e.preventDefault();
    let id = $(this).data('id');
    let link = baseUrl + '/school-terms-condition';
    Swal.fire({
        title: window.trans["Are you sure you want to add this add-on"],
        text: window.trans["This add-on will be added into your current subscribed package and will expire when your current subscription expires"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"],
        input: 'checkbox',
        inputPlaceholder: '<div class="m-2">'+ window.trans['I accept the provided'] + '<a href="'+ link +'" target="_blank"> '+window.trans['terms_condition']+' </a></div>',
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value) {
                let url = baseUrl + '/addons/subscribe/' + id;
                let data = null;

                function successCallback(response) {
                    showSuccessToast(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }

                function errorCallback(response) {
                    showErrorToast(response.message);
                }

                ajaxRequest('GET', url, data, null, successCallback, errorCallback);
            } else {
                Swal.fire({icon: 'error', text: window.trans['please_confirm_terms_condition']});
            }

        }
    })
})

// discontinue_addon
$(document).on('click', '.discontinue_addon', function (e) {
    e.preventDefault();
    let id = $(this).data('id');
    Swal.fire({
        title: window.trans["Are you sure"],
        text: window.trans["discontinue_upcoming_billing_cycle"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes"]
    }).then((result) => {
        if (result.isConfirmed) {
            let url = baseUrl + '/addons/discontinue/' + id;
            let data = null;

            function successCallback(response) {
                showSuccessToast(response.message);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
            }

            ajaxRequest('GET', url, data, null, successCallback, errorCallback);
        }
    })
})


$(document).on('click', '.no-feature-lock-menu,.no-feature-lock-menu-sub-item', function (e) {
    e.preventDefault();
    let link = baseUrl + '/addons/plan';
    let role = $(this).data('name');

    if (role == 'School Admin') {
        Swal.fire({
            title: window.trans["no_permission"],
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: window.trans["ok"],
            html: '<div class="mb-2">' + window.trans["no_permission_upgrade_plan"] + '</div><a href="' + link + '">' + window.trans["click_here_to_buy_addon"] + '</a>',
        })
    } else if (role == 'basic-features') {
        Swal.fire({
            title: window.trans["Your License Has Expired"],
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: window.trans["ok"],
        })
    } else {
        Swal.fire({
            title: window.trans["no_permission"],
            icon: 'warning',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: window.trans["ok"],
        })
    }

})

$(document).ready(function () {
    $('.sidebar .nav-link').each(function (index, value) {
        if (typeof $(value).data('access') != "undefined" && !$(value).data('access')) {
            if ($(value).data('toggle') == "collapse") {
                $(value).addClass('no-feature-lock-menu-without-alert');
            } else {
                if ($(value).parents('.collapse').length) {
                    $(value).addClass('no-feature-lock-menu-sub-item');
                } else {
                    $(value).addClass('no-feature-lock-menu');
                }
                $(value).attr('href', '#');
            }

        }
    })
})
