// noinspection JSJQueryEfficiency

/**
 * Table Query Params
 */
function classQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}

function feesQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}

function leaveDetailQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val()
    };
}

function schoolQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        package_id: $('#filter_package_id').val(),
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}

function ExamClassQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('#filter_exam_name').val(),
        class_id: $('#filter_class_name').val()
    };
}

function timetableQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        medium_id: $('#filter_medium_id').val()
    };
}

function getExamResult(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        exam_id: $('.result_exam').val(),
        session_year_id: $('#filter_session_year_id').val(),
        class_section_id: $('#filter_class_section_id').val(),
    };
}


function SubjectQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        medium_id: $('#filter_subject_id').val(),
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}


function ExpenseQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        category_id: $('#filter_category_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        month: $('#filter_month').val(),
    };
}

function payrollQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        month: $('#month').val(),
        year: $('#year').val(),
    };
}

function payrollListQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        year: $('#filter_year').val(),
    };
}

function leaveQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#session_year_id').val(),
        filter_upcoming: $('#filter_upcoming').val(),
        month_id: $('#filter_month_id').val(),
        user_id: $('#filter_user_id').val(),
    };
}

function AssignTeacherQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_id').val(),
    };
}


function webSettingsQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

function StudentDetailQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_section_id').val(),

    };
}


function AssignmentSubmissionQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        subject_id: $('#filter-subject-id').val(),
        class_section_id: $('#filter-class-section-id').val(),

    };
}

function CreateAssignmentSubmissionQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        session_year_id: $("#filter_session_year_id").val()
    };
}

function CreateLessonQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        lesson_id: $('#filter_lesson_id').val(),
    };
}

function CreateTopicQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_subject_id: $('#filter-subject-id').val(),
        class_id: $('#filter-class-section-id').val(),
        lesson_id: $('#filter_lesson_id').val(),
    };
}

function uploadMarksqueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#exam-class-section-id').val(),
        'class_subject_id': $('#class_subject_id').val(),
        'exam_id': $('#exam-id').val(),
    };
}

function feesPaidListQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        fees_id: $('#filter_fees_id').val(),
        class_id: $('#filter_class_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        mode: $('#filter_mode').val(),
        paid_status: $('#filter_paid_status').val()
    };
}

function feesPaymentTransactionQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        payment_status: $('#filter_payment_status').val(),
        session_year_id: $('#filter_session_year_id').val(),
    };
}

function subscriptionTransactionQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        payment_status: $('#filter_payment_status').val(),
        display_type: (tableListType === "" || tableListType === "online" || tableListType == null) ? 'transaction' : 'subscription_bill_payment',
    };
}

function studentRollNumberQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#filter_roll_number_class_section_id').val(),
        'sort_by': $('#sort_by').val(),
        'order_by': $('#order_by').val(),
    };
}

function onlineExamQueryParams(p) {
    let tableListType = $('.table-list-type.active').text();
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
        'class_section_id': $('#filter-class-section-id').val(),
        'class_subject_id': $('#filter-subject-id').val(),
        'subject_id': $('#filter-class-subject-id').val(),
        'session_year_id': $('#filter_session_year_id').val(),
    };
}


function onlineExamQuestionsQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#filter-class-section-id').val(),
        'class_subject_id': $('#filter-subject-id').val(),
        'subject_id': $('#filter-class-subject-id').val(),
    };
}

function studentDetailsQueryParams(p) {
    let tableListType = $('.student-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        class_id: $('#filter_class_section_id').val(),
        session_year_id: $('#filter_session_year_id').val(),
        show_deactive: (tableListType === "" || tableListType === "active" || tableListType == null) ? 0 : 1,
    };
}

function attendanceQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#timetable_class_section').val(),
        'date': $('#date').val(),
    }
}

function holidayQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'session_year_id': $('#filter_session_year_id').val(),
        'month': $('#filter_month').val(),
    }
}

function galleryQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'session_year_id': $('#filter_session_year_id').val(),
    }
}

function userStatusQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        role: $('.role').val(),
        class_section_id: $('.class_section_id').val(),
    }
}

function queryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    if (tableListType === 'Trashed') {
        $('.btn-update-rank').hide();
    } else {
        $('.btn-update-rank').show();
    }
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}

function promoteStudentQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'class_section_id': $('#student_class_section').val(),
        'session_year_id': $('#session_year_id').val(),
    };
}

function examQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        session_year_id: $('#filter_session_year_id').val(),
        medium_id: $('#filter_medium_id').val(),
        show_deleted: (tableListType === "" || tableListType === "All" || tableListType == null) ? 0 : 1,
    };
}

function subscriptionQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
    };
}

function subscriptionReportQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        status: $('#status').val()
    };
}

$("#filter_class_id,#filter_class_section_id,#filter_teacher_id,#filter_subject_id,#filter_medium_id,#filter_subject_id").on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})


$('#filter-question-class-section-id,#filter-subject-id,#filter-class-section-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})


//Show All / Trashed list Event
$('.table-list-type').on('click', function (e) {
    e.preventDefault();
    //Highlight the current selected type
    $('.table-list-type').removeClass('active').parent("b").contents().unwrap();
    $(this).wrap("<b></b>").addClass('active');

    //Refresh the bootstrap table so that data can be loaded according to the selected type
    //Based on this selected value new query param will be added in Bootstrap Table Query Params
    $('#table_list').bootstrapTable('refresh');
})


$('.student-list-type').on('click', function (e) {
    e.preventDefault();
    //Highlight the current selected type
    $('.student-list-type').removeClass('active').parent("b").contents().unwrap();
    $(this).wrap("<b></b>").addClass('active');

    //Refresh the bootstrap table so that data can be loaded according to the selected type
    //Based on this selected value new query param will be added in Bootstrap Table Query Params
    $('#table_list').bootstrapTable('refresh');
})

function transferStudentQueryParams(p) {
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        'current_class_section': $('#transfer_class_section').val(),
    };
}

function activeDeactiveQueryParams(p) {
    let tableListType = $('.table-list-type.active').data('value');
    return {
        limit: p.limit,
        sort: p.sort,
        order: p.order,
        offset: p.offset,
        search: p.search,
        show_deactive: (tableListType === "" || tableListType === "active" || tableListType == null) ? 0 : 1,
    };
}
