"use strict";

function showErrorToast(message) {
    $.toast({
        text: message,
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right',
        hideAfter: 5000
    });
}

function showSuccessToast(message) {
    $.toast({
        text: message,
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#f96868',
        position: 'top-right',
        // hideAfter: 1000

    });
}

function showWarningToast(message) {
    $.toast({
        text: message,
        showHideTransition: 'slide',
        icon: 'warning',
        loaderBg: '#f96868',
        position: 'top-right',
        // hideAfter: 1000

    });
}

/**
 *
 * @param type
 * @param url
 * @param data
 * @param {function} beforeSendCallback
 * @param {function} successCallback - This function will be executed if no Error will occur
 * @param {function} errorCallback - This function will be executed if some error will occur
 * @param {function} finalCallback - This function will be executed after all the functions are executed
 * @param processData
 */
function ajaxRequest(type, url, data, beforeSendCallback = null, successCallback = null, errorCallback = null, finalCallback = null, processData = false) {
    $.ajax({
        type: type,
        url: url,
        data: data,
        cache: false,
        processData: processData,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            if (beforeSendCallback != null) {
                beforeSendCallback();
            }
        },
        success: function (data) {
            if (!data.error) {
                if (successCallback != null) {
                    successCallback(data);
                }
            } else {
                if (errorCallback != null) {
                    errorCallback(data);
                }
            }

            if (finalCallback != null) {
                finalCallback(data);
            }
        }, error: function (jqXHR) {
            console.log(jqXHR);
            if (jqXHR.responseJSON) {
                showErrorToast(jqXHR.responseJSON.message);
            }
            if (finalCallback != null) {
                finalCallback();
            }
        }
    })
}

function formAjaxRequest(type, url, data, formElement, submitButtonElement, successCallback = null, errorCallback = null) {
    // To Remove Red Border from the Validation tag.
    formElement.find('.has-danger').removeClass("has-danger");
    formElement.validate();
    if (formElement.valid()) {
        let submitButtonText = submitButtonElement.val();

        function beforeSendCallback() {
            submitButtonElement.val('Please Wait...').attr('disabled', true);
        }

        function mainSuccessCallback(response) {
            if (response.warning) {
                showWarningToast(response.message);
            } else {
                showSuccessToast(response.message);
            }

            if (successCallback != null) {
                successCallback(response);
            }
        }

        function mainErrorCallback(response) {
            showErrorToast(response.message);
            if (errorCallback != null) {
                errorCallback(response);
            }
        }

        function finalCallback() {
            submitButtonElement.val(submitButtonText).attr('disabled', false);
        }

        ajaxRequest(type, url, data, beforeSendCallback, mainSuccessCallback, mainErrorCallback, finalCallback)
    }
}

function createCkeditor() {
    for (let equation_editor in CKEDITOR.instances) {
        CKEDITOR.instances[equation_editor].destroy();
    }
    CKEDITOR.replaceAll(function (textarea, config) {
        if (textarea.className == "editor_question") {
            config.mathJaxLib = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML';
            config.extraPlugins = 'mathjax';
            config.height = 200;
            return true;
        }
        if (textarea.className == "editor_options") {
            config.mathJaxLib = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML';
            config.extraPlugins = 'mathjax';
            config.height = 100
            return true;
        }
        if (textarea.className == "edit_editor_options") {
            config.mathJaxLib = '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML';
            config.extraPlugins = 'mathjax';
            config.height = 100
            return true;
        }
        return false;
    });

    // inline editors
    let elements = CKEDITOR.document.find('.equation-editor-inline'), i = 0, element;
    while ((element = elements.getItem(i++))) {
        CKEDITOR.inline(element, {
            mathJaxLib: '//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
            extraPlugins: 'mathjax',
            readOnly: true,
        });
    }
}

function Select2SearchDesignTemplate(repo) {
    /**
     * This function is used in Select2 Searching Functionality
     */
    if (repo.loading) {
        return repo.text;
    }
    let $container;
    if (repo.id && repo.text) {
        $container = $(
            "<div class='select2-result-repository clearfix'>" +
            "<div class='select2-result-repository__title'></div>" +
            "</div>"
        );
        $container.find(".select2-result-repository__title").text(repo.text);
    } else {
        $container = $(
            "<div class='select2-result-repository clearfix'>" +
            "<div class='row'>" +
            "<div class='col-1 select2-result-repository__avatar' style='width:20px'>" +
            "<img src='" + repo.image + "' class='w-100' alt=''/>" +
            "</div>" +
            "<div class='col-10'>" +
            "<div class='select2-result-repository__title'></div>" +
            "<div class='select2-result-repository__description'></div>" +
            "</div>" +
            "</div>"
        );

        $container.find(".select2-result-repository__title").text(repo.first_name + " " + repo.last_name);
        $container.find(".select2-result-repository__description").text(repo.email);
    }

    return $container;
}

/**
 *
 * @param searchElement
 * @param searchUrl
 * @param {Object|null} data
 * @param {number} data.total_count
 * @param {string} data.email
 * @param {number} data.page
 * @param placeHolder
 * @param templateDesignEvent
 * @param onTemplateSelectEvent
 */
function select2Search(searchElement, searchUrl, data, placeHolder, templateDesignEvent, onTemplateSelectEvent) {
    //Select2 Ajax Searching Functionality function
    if (!data) {
        data = {};
    }
    $(searchElement).select2({
        tags: true,
        ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            cache: true,
            data: function (params) {
                data.email = params.term;
                data.page = params.page;
                return data;
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.data,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            }
        },
        placeholder: placeHolder,
        minimumInputLength: 1,
        templateResult: templateDesignEvent,
        templateSelection: onTemplateSelectEvent,
    });
}

/**
 * @param {string} [url] - Ajax URL that will be called when the Confirm button will be clicked
 * @param {string} [method] - GET / POST / PUT / PATCH / DELETE
 * @param {Object} [options] - Options to Configure SweetAlert
 * @param {string} [options.title] - Are you sure
 * @param {string} [options.text] - You won't be able to revert this
 * @param {string} [options.icon] - 'warning'
 * @param {boolean} [options.showCancelButton] - true
 * @param {string} [options.confirmButtonColor] - '#3085d6'
 * @param {string} [options.cancelButtonColor] - '#d33'
 * @param {string} [options.confirmButtonText] - Confirm
 * @param {string} [options.cancelButtonText] - Cancel
 * @param {function} [options.successCallBack] - function()
 * @param {function} [options.errorCallBack] - function()
 */
function showSweetAlertConfirmPopup(url, method, options = {}) {
    let opt = {
        title: window.trans["Are you sure"],
        text: window.trans["You wont be able to revert this"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Confirm"],
        cancelButtonText: window.trans["Cancel"],
        successCallBack: function () {
        },
        errorCallBack: function (response) {
        },
        ...options,
    }

    Swal.fire({
        title: opt.title,
        text: opt.text,
        icon: opt.icon,
        showCancelButton: opt.showCancelButton,
        confirmButtonColor: opt.showCancelButton,
        cancelButtonColor: opt.cancelButtonColor,
        confirmButtonText: opt.confirmButtonText,
        cancelButtonText: opt.cancelButtonText
    }).then((result) => {
        if (result.isConfirmed) {
            function successCallback(response) {
                showSuccessToast(response.message);
                opt.successCallBack(response);
            }

            function errorCallback(response) {
                showErrorToast(response.message);
                opt.errorCallBack(response);
            }

            ajaxRequest(method, url, null, null, successCallback, errorCallback);
        }
    })
}

/**
 *
 * @param {string} [url] - Ajax URL that will be called when the Delete will be successfully
 * @param {Object} [options] - Options to Configure SweetAlert
 * @param {string} [options.text] - "Are you sure?"
 * @param {string} [options.title] - "You won't be able to revert this!"
 * @param {string} [options.icon] - "warning"
 * @param {boolean} [options.showCancelButton] - true
 * @param {string} [options.confirmButtonColor] - "#3085d6"
 * @param {string} [options.cancelButtonColor] - "#d33"
 * @param {string} [options.confirmButtonText] - "Yes, delete it!"
 * @param {string} [options.cancelButtonText] - "Cancel"
 * @param {function} [options.successCallBack] - function()
 * @param {function} [options.errorCallBack] - function()
 */
function showDeletePopupModal(url, options = {}) {

    // To Preserve OLD
    let opt = {
        title: window.trans["Are you sure"],
        text: window.trans["You wont be able to revert this"],
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["yes_delete"],
        cancelButtonText: window.trans['Cancel'],
        successCallBack: function () {
        },
        errorCallBack: function (response) {
        },
        ...options,
    }
    showSweetAlertConfirmPopup(url, 'DELETE', opt);
}


/**
 *
 * @param {string} [url] - Ajax URL that will be called when the Delete will be successfully
 * @param {Object} [options] - Options to Configure SweetAlert
 * @param {string} [options.text] - "Are you sure?"
 * @param {string} [options.title] - "You won't be able to revert this!"
 * @param {string} [options.icon] - "warning"
 * @param {boolean} [options.showCancelButton] - true
 * @param {string} [options.confirmButtonColor] - "#3085d6"
 * @param {string} [options.cancelButtonColor] - "#d33"
 * @param {string} [options.confirmButtonText] - "Yes, delete it!"
 * @param {string} [options.cancelButtonText] - "Cancel"
 * @param {function} [options.successCallBack]
 * @param {function} [options.errorCallBack]
 */
function showRestorePopupModal(url, options = {}) {

    // To Preserve OLD
    let opt = {
        title: window.trans["Are you sure"],
        text: window.trans["You wont be able to revert this"],
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans['Yes Restore it'],
        cancelButtonText: window.trans['Cancel'],
        successCallBack: function () {
        },
        errorCallBack: function (response) {
        },
        ...options,
    }
    showSweetAlertConfirmPopup(url, 'PUT', opt);
}

/**
 *
 * @param {string} [url] - Ajax URL that will be called when the Delete will be successfully
 * @param {Object} [options] - Options to Configure SweetAlert
 * @param {string} [options.text] - "Are you sure?"
 * @param {string} [options.title] - "You won't be able to revert this!"
 * @param {string} [options.icon] - "warning"
 * @param {boolean} [options.showCancelButton] - true
 * @param {string} [options.confirmButtonColor] - "#3085d6"
 * @param {string} [options.cancelButtonColor] - "#d33"
 * @param {string} [options.confirmButtonText] - "Yes, delete it!"
 * @param {string} [options.cancelButtonText] - "Cancel"
 * @param {function} [options.successCallBack]
 * @param {function} [options.errorCallBack]
 */
function showPermanentlyDeletePopupModal(url, options = {}) {

    // To Preserve OLD
    let opt = {
        title: window.trans["Are you sure"],
        text: window.trans["You are about to Delete this data"],
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: window.trans["Yes Delete Permanently"],
        cancelButtonText: window.trans['Cancel'],
        successCallBack: function () {
        },
        errorCallBack: function (response) {
        },
        ...options,
    }
    showSweetAlertConfirmPopup(url, 'DELETE', opt);
}

// const minutesToDuration = (minutes) => {
//     let h = Math.floor(minutes / 60);
//     let m = minutes % 60;
//     h = h < 10 ? '0' + h : h; // (or alternatively) h = String(h).padStart(2, '0')
//     m = m < 10 ? '0' + m : m; // (or alternatively) m = String(m).padStart(2, '0')
//     return `${h}:${m}:00`;
// }

const getSubjectOptionsList = (SubjectId, $this) => {
    $(SubjectId).val("").removeAttr('disabled').show();
    $(SubjectId).find('option').hide();
    if ($(SubjectId).find('option[data-class-section="' + $this.val() + '"]').length) {
        $(SubjectId).find('option[data-class-section="' + $this.val() + '"]').show().trigger('change');
    } else {
        $(SubjectId).val("data-not-found").attr('disabled', true).show().trigger('change');
    }
}

const getClassSubjectOptionsList = (SubjectId, classId) => {
    $(SubjectId).val("").removeAttr('disabled').show();
    $(SubjectId).find('option').hide();
    if ($(SubjectId).find('option[data-class-id="' + classId + '"]').length) {
        $(SubjectId).find('option[data-class-id="' + classId + '"]').show().trigger('change');
    } else {
        $(SubjectId).val("").attr('disabled', false).show().trigger('change');
    }
}

const getFilterSubjectOptionsList = (SubjectId, $this) => {
    $(SubjectId).val("").removeAttr('disabled').show();
    $(SubjectId).find('option').hide();
    if ($this.val()) {
        if ($(SubjectId).find('option[data-class-section="' + $this.val() + '"]').length) {
            $(SubjectId).find('option[value=""]').show();
            $(SubjectId).find('option[data-class-section="' + $this.val() + '"]').show();
        } else {
            $(SubjectId).val("data-not-found").attr('disabled', true).show();
        }
    } else {
        $(SubjectId).val("");
    }
}

const getExamSubjectOptionsList = (SubjectId, $this) => {
    $(SubjectId).val("").removeAttr('disabled').show();
    $(SubjectId).find('option').hide();
    if ($(SubjectId).find('option[data-exam-id="' + $this.val() + '"]').length) {
        $(SubjectId).find('option[data-exam-id="' + $this.val() + '"]').show();
    } else {
        $(SubjectId).val("data-not-found").attr('disabled', true).show();
    }
}

const getExamOptionsList = (examId, $this) => {
    $(examId).val("").removeAttr('disabled').show();
    $(examId).find('option').hide();
    if ($(examId).find('option[data-session-year="' + $this.val() + '"]').length) {
        $(examId).find('option[data-session-year="' + $this.val() + '"]').show().trigger('change');
    } else {
        $(examId).val("data-not-found").attr('disabled', true).show();
    }
}

// Remove Online Exam Question's Option
const removeOptionWithAnswer = ($this, deleteElement) => {
    let optionNumber = $this.find('.option-number').html();
    $('#answer_select').find('option[value = ' + optionNumber + ']').remove();
    $this.slideUp(deleteElement);
}

function classValidation() {
    $('.subject').each(function (index, subject) {
        $(subject).rules("remove", "noDuplicateValues");
        $(subject).rules("add", {
            "noDuplicateValues": {
                class: "subject",
                group: $(subject).attr('data-group'),
                value: $(subject).find("option:selected").text()
            }
        });
    })
}


function getContrastColor(bgColor) {

    let convert_color_hex;
    if (isHexColor(bgColor)) {
        convert_color_hex = bgColor;
    } else {
        convert_color_hex = rgbToHex(bgColor);
    }

    let hexColor = convert_color_hex.substring(1); // Remove the '#' character
    let r = parseInt(hexColor.substring(0, 2), 16);
    let g = parseInt(hexColor.substring(2, 4), 16);
    let b = parseInt(hexColor.substring(4, 6), 16);

    // Calculate the relative luminance (perceived brightness) of the color
    let brightness = (r * 299 + g * 587 + b * 114) / 1000;
    // brightness = 255 - Math.abs(255 - brightness);
    // Choose white or black text based on the background brightness
    return brightness > 55 ? "#000" : "#fff";
}

function rgbToHex(rgb) {
    // Extract the individual R, G, and B values from the RGB string
    const rgbArray = rgb.match(/\d+/g);

    // Convert the R, G, and B values to hexadecimal and concatenate them
    if (rgbArray) {
        return '#' + rgbArray.map(Number).map(num => {
            // const hex = num.toString(16);
            const hex = num.toString();
            return hex.length === 1 ? '0' + hex : hex; // Ensure two-digit hex values
        }).join('');
    }
    return '#000';
}

function isHexColor(color) {
    // Regular expression to match HEX format (e.g., "#ff00ff" or "ff00ff")
    const hexRegex = /^#?([0-9A-Fa-f]{3}){1,2}$/;
    return hexRegex.test(color);
}

function handlePayInInstallment($document, data) {
    if (data.fees_data.is_installment_paid) {
        $document.find('.pay-in-installment').trigger('click');
        $document.find('.pay-in-installment').attr('disabled', true);
    } else if (data.fees_status == 1) {
        $document.find('.pay-in-installment-row').hide(200);
        $document.find('.pay-in-installment').attr('disabled', true);
        // $document.find('.compulsory-fees-payment').prop('disabled', true);
    } else {
        $document.find('.pay-in-installment').attr('disabled', false);
        // $document.find('.compulsory-fees-payment').prop('disabled', false);
    }
}

function updateStudentIdsHidden(tableId, inputField, buttonClass) {
    var selectedRows = $(tableId).bootstrapTable('getSelections');
    var selectedRowsValues = selectedRows.map(function (row) {
        return row.student_id; // replace 'id' with the field you want
    });
    $(inputField).val(JSON.stringify(selectedRowsValues));

    if (buttonClass != null) {
        if (selectedRowsValues.length) {
            $(buttonClass).show();
        } else {
            $(buttonClass).hide();
        }
    }
}

function getLastDateOfMonth(month, year) {
    // Initialize the date to the first day of the next month
    var date = new Date(year, month, 1);
    // Set the date to the last day of the current month by subtracting one day from the next month's first day
    date.setDate(date.getDate() - 1);

    // Get the day, month, and year
    var day = date.getDate();
    var monthFormatted = ("0" + (date.getMonth() + 1)).slice(-
        2); // Adding 1 to get the right month since months are zero-based
    var yearFormatted = date.getFullYear();

    // Format the date as dd-mm-YYYY
    var formattedDate = `${day < 10 ? '0' : ''}${day}-${monthFormatted}-${yearFormatted}`;
    return formattedDate;
}

function formatMoney(n) {
    return n.toLocaleString().split(".")[0] + "." + n.toFixed(2).split(".")[1];
}


function expense_graph(months, data) {
    var areaData = {
        labels: months,
        datasets: [{
            label: '',
            data: data,
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255,99,132,1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1,
            fill: true, // 3: no fill
        }]
    };

    var areaOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            filler: {
                propagate: true,
            },
        },
        legend: {
            display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        },

    }

    if ($("#expenseChart").length) {
        var areaChartCanvas = $("#expenseChart").get(0).getContext("2d");
        var areaChart = new Chart(areaChartCanvas, {
            type: 'line',
            data: areaData,
            options: areaOptions
        });
    }
}

function class_attendance(classData) {
    if ($("#class-section-attendance").length) {
        var graphGradient = document.getElementById("class-section-attendance").getContext('2d');
        var graphGradient2 = document.getElementById("class-section-attendance").getContext('2d');
        var saleGradientBg = graphGradient.createLinearGradient(5, 0, 5, 100);
        saleGradientBg.addColorStop(0, 'rgba(26, 115, 232, 0.18)');
        saleGradientBg.addColorStop(1, 'rgba(26, 115, 232, 0.02)');
        var saleGradientBg2 = graphGradient2.createLinearGradient(100, 0, 50, 150);
        saleGradientBg2.addColorStop(0, 'rgba(0, 208, 255, 0.19)');
        saleGradientBg2.addColorStop(1, 'rgba(0, 208, 255, 0.03)');

        let borderColors = ['#2ED1B9', '#1F3BB3', '#52CDFF', '#FFAA99', '#FE8896', '#F6D55C', '#FFD608', '#A760FF', '#697481'];

        let class_names = [];
        let sectionWiseArray = [];
        let classIndexMap = {};

        classData.forEach((class_data) => {
            class_data.section_data.forEach((sectionData) => {
                const sectionName = sectionData.section_name;
                const totalPresent = sectionData.total_present;

                let existingSection = sectionWiseArray.find((section) => section.section_name === sectionName);

                if (!existingSection) {
                    existingSection = {
                        section_name: sectionName,
                        data: []
                    };

                    classData.forEach((class_data) => {
                        existingSection.data.push({
                            class_name: class_data.class_name,
                            total_present: "0.00"
                        });
                    });

                    sectionWiseArray.push(existingSection);
                }

                const classIndex = existingSection.data.findIndex((data) => data.class_name === class_data.class_name);
                existingSection.data[classIndex].total_present = totalPresent;

                if (!(class_data.class_name in classIndexMap)) {
                    classIndexMap[class_data.class_name] = classIndex;
                }
            });
        });

        if (sectionWiseArray.length > 0) {
            class_names = sectionWiseArray[0].data.map(item => item.class_name);

            var datasets = sectionWiseArray.map((section, index) => ({
                label: section.section_name,
                data: section.data.map(item => item.total_present),
                backgroundColor: saleGradientBg2,
                borderColor: borderColors[index],
                borderWidth: 1.5,
                fill: true,
                pointBorderWidth: 1,
                pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                pointHoverRadius: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
                pointBorderColor: ['#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff', '#fff',],
                pointBackgroundColor: borderColors[index],
            }));
        }


        var salesTopData = {
            labels: class_names,
            datasets: datasets
        };

        var salesTopOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    gridLines: {
                        display: true,
                        drawBorder: false,
                        color: "#F0F0F0",
                        zeroLineColor: '#F0F0F0',
                    },
                    ticks: {
                        beginAtZero: false,
                        autoSkip: true,
                        // maxTicksLimit: 4,
                        fontSize: 10,
                        color: "#6B778C",
                        min: 0,
                        max: 100
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false,
                        drawBorder: false,
                    },
                    ticks: {
                        beginAtZero: false,
                        autoSkip: true,
                        maxTicksLimit: 50,
                        fontSize: 10,
                        color: "#6B778C"
                    }
                }],
            },
            legend: false,
            legendCallback: function (chart) {
                var text = [];
                text.push('<div class="chartjs-legend"><ul>');
                for (var i = 0; i < chart.data.datasets.length; i++) {
                    //   console.log(chart.data.datasets[i]); // see what's inside the obj.
                    text.push('<li>');
                    text.push('<span class="legend-dots" style="background-color:' + chart.data.datasets[i].borderColor + '">' + '</span>');
                    text.push(chart.data.datasets[i].label);
                    text.push('</li>');
                }
                text.push('</ul></div>');
                return text.join("");
            },

            elements: {
                line: {
                    tension: 0.4,
                }
            },
            tooltips: {
                backgroundColor: 'rgba(31, 59, 179, 1)',
            }
        }
        var salesTop = new Chart(graphGradient, {
            type: 'line',
            data: salesTopData,
            options: salesTopOptions
        });
        document.getElementById('performance-line-legend').innerHTML = salesTop.generateLegend();
    }
}

function getMinutes(minute) {
    if (minute < 10) {
        return '0' + minute;
    } else {
        return minute;
    }
}
