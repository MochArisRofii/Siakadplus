<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fees Receipt || {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
</head>
<body>
<div class="container ">
    <div class="row mt-4">
        <div class="col">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        @if(!empty($schoolVerticalLogo))
                        @if (Storage::disk('public')->exists($schoolVerticalLogo->getRawOriginal('data')))
                            <i><img style="height: 5rem;width: 5rem;" src="{{public_path('storage/'.$schoolVerticalLogo->getRawOriginal('data'))}}" alt="logo"></i><br>
                        @else
                        <i><img style="height: 5rem;width: 5rem;" src="{{public_path('assets/horizontal-logo.svg')}}" alt="logo"></i><br>
                        @endif
                            
                        @else
                            @dd($systemVerticalLogo)
                            <i><img style="height: 5rem;width: 5rem;" src="{{public_path('storage/'.$systemVerticalLogo->getRawOriginal('data'))}}" alt="logo"></i><br>
                        @endif
                        <span class="text-default-d3 ml-4" style="font-size:1.5rem"><strong>{{$school['school_name'] ?? ''}}</strong></span><br>
                        <span class="text-default-d3 ml-4" style="font-size:1rem">{{$school['school_address'] ?? ''}}</span>
                        <hr style="border: 1px solid">
                        <h4>Fee Receipt</h4>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                </div>

                <div class="col-sm-6 align-self-start d-sm-flex justify-content-end">
                    <div class="text-grey-m2 mt-2 ml-3">
                        <p><strong><u>Invoice</u></strong><br>
                            <strong>Fee Receipt</strong> :- {{$feesPaid->id ?? ''}}<br>
                        </p>
                    </div>
                </div>
            </div>
            <hr style="border: 1px solid">
            <div class="row ml-3">
                <div class="col-sm-6 align-self-start">
                    <div class="row text-black">
                        <p><strong><u>Student Details :- </u></strong><br>

                            <strong>Name</strong> :- {{$student->user->full_name}} <br>
                            {{--                            <strong>Session</strong> :- {{isset($feesPaid) ? $feesPaid->session_year->name : '-'}} <br>--}}
                            <strong>Class</strong> :- {{$feesPaid->fees->class->full_name ?? ''}}<br>
                    </div>
                </div>
            </div>
            <div class="mt-4 ml-4">
                <table class="table" style="text-align: center">
                    <thead>
                    <tr>
                        <th scope="col">Sr no.</th>
                        <th scope="col" colspan="2">Fee Type</th>
                        <th scope="col">Amount</th>
                    </tr>
                    </thead>
                    @php
                        $no = 1;
                    @endphp
                    <tbody>
                    @php
                        $compulsoryFeesType = $feesPaid->fees->compulsory_fees->pluck('fees_type_name');
                        $compulsoryFeesType = implode(" , ",$compulsoryFeesType->toArray());
                    @endphp
                    {{--Compulsory Fees Listing --}}
                    @if(isset($feesPaid->compulsory_fee) && $feesPaid->compulsory_fee->isNotEmpty())
                        @foreach ($feesPaid->compulsory_fee as $compulsoryFee)
                            @if($compulsoryFee->type == "Full Payment")
                                @foreach ($feesPaid->compulsory_fee as $data)
                                    <tr>
                                        <th scope="row" class="text-left">{{$no++}}</th>
                                        <td colspan="2" class="text-left">
                                            {{$data->type}}<br>
                                            <small class="font-weight-bold">( {{$compulsoryFeesType}} )</small><br>
                                            <small>Mode : <span class="font-weight-bold">({{ $compulsoryFee->mode_name}})</span></small><br>
                                            <small>Date &nbsp;: <span class="font-weight-bold">{{date('d-m-Y',strtotime($compulsoryFee->date))}} </span></small><br>
                                            <small>Due Charges</small>
                                        </td>
                                        <td class="text-right">
                                            {{$data->amount}} {{$school['currency_symbol'] ?? ''}}<br><br><br><br>
                                            <small>{{$compulsoryFee->due_charges ?? 0}} {{$school['currency_symbol'] ?? ''}}</small>
                                            <hr>
                                            {{$data->amount + $compulsoryFee->due_charges}} {{$school['currency_symbol'] ?? ''}}
                                        </td>
                                    </tr>
                                @endforeach
                                {{--                                <tr>--}}
                                {{--                                    <th scope="row" class="text-left">{{$no++}}</th>--}}
                                {{--                                    <td colspan="2" class="text-left">Due Charges</td>--}}
                                {{--                                    <td class="text-right">{{$compulsoryFee->due_charges ?? 0}} {{$school['currency_symbol'] ?? ''}}</td>--}}
                                {{--                                </tr>--}}
                            @elseif($compulsoryFee->type == "Installment Payment")
                                <tr>
                                    <th scope="row" class="text-left">{{$no++}}</th>
                                    <td colspan="2" class="text-left">{{$compulsoryFee->installment_fee->name}}
                                        <br><small>Mode : <span class="font-weight-bold">({{ $compulsoryFee->mode_name}})</span></small>
                                        <br><small>Date &nbsp;: <span class="font-weight-bold">{{date('d-m-Y',strtotime($compulsoryFee->date))}} </span></small>
                                        <br><small>Includes : <span class="font-weight-bold">{{$compulsoryFeesType}} </span></small>
                                    </td>
                                    <td class="text-right">{{$compulsoryFee->amount}} {{$school['currency_symbol'] ?? ''}}</td>
                                </tr>
                            @endif
                        @endforeach
                    @endif

                    {{-- Optional Fees Listing --}}
                    @if(isset($feesPaid->optional_fee) && $feesPaid->optional_fee->isNotEmpty())
                        @foreach ($feesPaid->optional_fee as $optionalFee)
                            <tr>
                                <th scope="row" class="text-left">{{$no++}}</th>
                                <td colspan="2" class="text-left">{{ $optionalFee->fees_class_type->fees_type_name}} <small class="font-weight-bold">({{__("optional")}})</small>
                                    <br><small>Mode : <span class="font-weight-bold">({{ $optionalFee->mode_name }})</span></small>
                                    <br><small>Date &nbsp;: <span class="font-weight-bold">{{date('d-m-Y',strtotime($optionalFee->date))}} </span></small>
                                </td>
                                <td class="text-right">{{$optionalFee->amount}} {{$school['currency_symbol'] ?? ''}}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <th scope="row"></th>
                        <td colspan="2" class="text-left"><strong>Total Amount</strong></td>
                        <td class="text-right">{{$feesPaid->amount}} {{$school['currency_symbol'] ?? ''}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>

</html>
