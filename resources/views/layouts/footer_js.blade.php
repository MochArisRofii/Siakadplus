<script src="{{ asset('/assets/js/Chart.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
<script src="{{ asset('/assets/select2/select2.min.js') }}"></script>

<script src="{{ asset('/assets/js/off-canvas.js') }}"></script>
<script src="{{ asset('/assets/js/hoverable-collapse.js') }}"></script>
<script src="{{ asset('/assets/js/misc.js') }}"></script>
<script src="{{ asset('/assets/js/settings.js') }}"></script>
<script src="{{ asset('/assets/js/todolist.js') }}"></script>
<script src="{{ asset('/assets/js/ekko-lightbox.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.tagsinput.min.js') }}"></script>




{{--<script src="{{ asset('/assets/bootstrap-table/bootstrap-table.min.js') }}"></script>--}}

<script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js"></script>
<script src="{{ asset('/assets/bootstrap-table/bootstrap-table-mobile.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/bootstrap-table-export.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/fixed-columns.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/tableExport.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/jspdf.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/jspdf.plugin.autotable.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/reorder-rows.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/jquery.tablednd.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/loadash.min.js') }}"></script>

<script src="{{ asset('/assets/js/jquery.cookie.js') }}"></script>
<script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('/assets/js/datepicker.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.repeater.js') }}"></script>
<script src="{{ asset('/assets/tinymce/tinymce.min.js') }}"></script>

<script src="{{ asset('/assets/color-picker/jquery-asColor.min.js') }}"></script>
<script src="{{ asset('/assets/color-picker/color.min.js') }}"></script>

<script src="{{ asset('/assets/js/custom/validate.js') }}"></script>
<script src="{{ asset('/assets/js/jquery-additional-methods.min.js')}}"></script>
<script src="{{ asset('/assets/js/custom/function.js') }}"></script>
<script src="{{ asset('/assets/js/custom/common.js') }}"></script>
<script src="{{ asset('/assets/js/custom/custom.js') }}"></script>
<script src="{{ asset('/assets/js/custom/bootstrap-table/actionEvents.js') }}"></script>
<script src="{{ asset('/assets/js/custom/bootstrap-table/formatter.js') }}"></script>
<script src="{{ asset('/assets/js/custom/bootstrap-table/queryParams.js') }}"></script>

<script src="{{ asset('/assets/ckeditor-4/ckeditor.js') }}"></script>
<script src="{{ asset('/assets/ckeditor-4/adapters/jquery.js') }}" async></script>

<script src="{{ asset('/assets/js/momentjs.js') }}"></script>
<script type='text/javascript'>
    @if ($errors->any())
    @foreach ($errors->all() as $error)
    $.toast({
        text: '{{ $error }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endforeach
    @endif

    @if (Session::has('success'))
    $.toast({
        text: '{{ Session::get('success') }}',
        showHideTransition: 'slide',
        icon: 'success',
        loaderBg: '#f96868',
        position: 'top-right'
    });
    @endif

    @if (Session::has('error'))
    $.toast({
        text: '{{ Session::get('error') }}',
        showHideTransition: 'slide',
        icon: 'error',
        loaderBg: '#f2a654',
        position: 'top-right'
    });
    @endif
</script>
<script>
    let date_format = '{{ $schoolSettings['date_format'] ?? $systemSettings['date_format'] }}'.replace('Y', 'YYYY').replace('m', 'MM').replace('d', 'DD');

    let date_time_format = '{{ $schoolSettings['date_format'] ?? $systemSettings['date_format'] }} {{ $schoolSettings['time_format'] ?? $systemSettings['time_format'] }}'.replace('Y', 'YYYY').replace('m', 'MM').replace('d', 'DD').replace('h', 'hh').replace('H', 'HH').replace('i', 'mm').replace('a', 'a').replace('A', 'A');
</script>
