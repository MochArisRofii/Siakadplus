@extends('layouts.master')

@section('title')
    {{ __('Pay Compulsory Fees') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Pay Compulsory Fees') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body d-flex justify-content-center">
                        <form class="pt-3 create-form form-validation col-sm-12 col-md-5" method="post" action="{{ route('fees.compulsory.store') }}" data-success-function="successFunction" novalidate="novalidate">
                            <input type="hidden" name="fees_id" id="compulsory-fees-id" value="{{$fees->id}}"/>
                            <input type="hidden" name="student_id" id="student-id" value="{{$student->id}}"/>
                            <input type="hidden" name="parent_id" id="parent-id" value="{{$student->student->guardian_id}}"/>
                            <input type="hidden" name="installment_mode" id="installment-mode" value="0"/>
                            <input type="hidden" name="total_amount" id="total-amount" value="{{$fees->total_compulsory_fees}}"/>
                            <input type="hidden" id="total_compulsory_fees" name="total_compulsory_fees" value="{{$fees->total_compulsory_fees}}">
                            <input type="hidden" id="remaining_amount" value="{{$fees->remaining_amount}}">
                            <input type="hidden" id="total_installment_amount" value="0">
                            <h4>{{$student->full_name.':-'.$student->student->class_section->full_name}}</h4><br>
                            <div class="form-group">
                                <label for="payment-date">{{ __('date') }} <span class="text-danger">*</span></label>
                                <input id="payment-date" type="text" name="date" class="datepicker-popup paid-date form-control" placeholder="{{ __('date') }}" autocomplete="off" required>
                            </div>

                            <hr>
                            <div class="form-group col-sm-12 col-md-12">
                                <div class="compulsory-fees-content">
                                    <table class="table">
                                        <tbody>
                                        @foreach($fees->compulsory_fees as $compulsoryFee)
                                            <tr>
                                                <td class="text-left"></td>
                                                <td colspan="2" class="text-left">
                                                    <label>{{$compulsoryFee->fees_type_name}}</label>
                                                </td>
                                                <td class="text-right">{{$compulsoryFee->amount.' '.$currencySymbol}}</td>
                                            </tr>
                                        @endforeach
                                        @if(count($fees->installments))
                                            <tr class="pay-in-installment-row">
                                                <td class="text-left"></td>
                                                <td colspan="2" class="text-left">
                                                    <label for="pay-in-installment-chk">{{__("Pay in installment")}}</label>
                                                </td>
                                                <td class="text-right">
                                                    <input type="checkbox" id="pay-in-installment-chk" class="form-check-input pay-in-installment">
                                                </td>
                                            </tr>
                                        @endif

                                        @foreach($fees->installments as $key=>$installment)
                                            <tr class="installment_rows" style="display: none;">
                                                @if(!empty($installment->is_paid))
                                                    {{--If installment is paid--}}
                                                    <td>
                                                        <span class="remove-installment-fees-paid text-left" data-id="{{$installment->is_paid->id}}">
                                                            <i class="fa fa-times text-danger" style="cursor:pointer" aria-hidden="true"></i>
                                                        </span>
                                                    </td>

                                                    <td colspan="2" class="text-left">
                                                        <label>{{$installment->name}}<br>
                                                            <small class="text-success">{{__("paid_on")}} : {{$installment->is_paid->date}}</small>
                                                            @if(!empty($installment->is_paid->advance_fees))
                                                                <br>
                                                                @foreach($installment->is_paid->advance_fees as $advance)
                                                                    <br>{{__("Advance")}} ({{date('Y-m-d',strtotime($advance['created_at']))}})
                                                                @endforeach
                                                            @endif
                                                        </label>
                                                    </td>

                                                    <td class="text-right">
                                                        @php
                                                            $advance_payment = 0;
                                                            if (!empty($installment->is_paid->advance_fees)) {
                                                                foreach($installment->is_paid->advance_fees as $advance)
                                                                {
                                                                    $advance_payment += $advance['amount'];
                                                                }
                                                            }
                                                        @endphp
                                                        <label>{{($installment->is_paid->amount - $advance_payment).' '.$currencySymbol}}
                                                            <br><small>{{$installment->is_paid->due_charges.' '.$currencySymbol}}</small><br>

                                                            @if(!empty($installment->is_paid->advance_fees))
                                                                @foreach($installment->is_paid->advance_fees as $advance)
                                                                    <br>{{$advance['amount'].' '.$currencySymbol}}
                                                                @endforeach
                                                            @endif
                                                            <hr>
                                                            <br>{{$installment->is_paid->amount+$installment->is_paid->due_charges.' '.$currencySymbol}}


                                                        </label>

                                                    </td>
                                                @else
                                                    {{--If Installment is not Paid--}}
                                                    <td>
                                                        <input type="checkbox" id="installment-fees-{{$key}}" name="installment_fees[{{$key}}][id]" class="installment-checkbox {{($installment->due_charges_amount > 0) ? 'default-checked-installment' : ''}}" value="{{$installment->id}}" data-amount="{{$installment->total_amount}}" aria-label=""
                                                            {{--                                                            {!! ($installment->due_charges_amount > 0) ? "checked  onclick=\"return false\"" : ''!!}--}}
                                                        />
                                                        <input type="hidden" name="installment_fees[{{$key}}][due_charges]" class="due-charges-amount" value="{{$installment->due_charges_amount}}" disabled/>
                                                        <input type="hidden" name="installment_fees[{{$key}}][amount]" class="installment-amount" value="{{$installment->minimum_amount}}" disabled/>
                                                    </td>

                                                    <td colspan="2" class="text-left">
                                                        <label for="installment-fees-{{$key}}">{{$installment->name}}<br>
                                                            <small class="{{$installment->due_charges_amount > 0 ? "text-danger" : "text-success"}}">{{__("Due date on").' '.$installment->due_date}}</small>
                                                            <br><small class="{{$installment->due_charges_amount > 0 ? "text-danger" : "text-success"}}">
                                                                {{__("Charges").' '.$installment->due_charges}}
                                                                {{$installment->due_charges_type=="percentage" ? "%" : $currencySymbol}}
                                                            </small>
                                                        </label>
                                                    </td>

                                                    <td class="text-right">
                                                        {{--<input type="number" value="{{round($installment->minimum_amount,2)}}" min="{{round($installment->minimum_amount,2)}}" max="{{round($installment->maximum_amount,2)}}" class="ml-auto form-control col-5 text-right custom-installment-amount" aria-label="" disabled {{($installment->due_charges_amount > 0) ? "readonly" : ''}}/><br>--}}

                                                        <span class="final-installment-amount">{{round($installment->minimum_amount,2).' '.$currencySymbol}}</span>
                                                        <br>
                                                        <label>
                                                            <small>{{round($installment->due_charges_amount,2).' '.$currencySymbol}}</small><br>
                                                            <hr>
                                                            <span class="final-installment-amount">{{round($installment->total_amount,2).' '.$currencySymbol}}</span>
                                                        </label>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        <tr class="installment_rows" style="display: none;">
                                            <td class="text-left"></td>
                                            <td colspan="2" class="text-left"><label>{{__("Advance")}}</label></td>
                                            <td class="justify-content-end row">
                                                <input type="number" id="advance" name="advance" aria-label="" class="form-control col-6 text-right " min="0" max="{{$fees->remaining_amount}}" value="0" {{!$oneInstallmentPaid ? "disabled" : ""}}/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-left"></td>
                                            <td colspan="2" class="text-left"><label>{{__("Total Amount")}}</label></td>
                                            <td class="text-right"><span id="total_amount_text">{{$fees->total_compulsory_fees}}</span>{{' '.$currencySymbol}}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="row mode-container">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('mode') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="mode" class="cash-compulsory-mode  mode" value="1" checked>
                                                {{ __('cash') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="mode" class="cheque-compulsory-mode mode" value="2">
                                                {{ __('cheque') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group cheque-no-container" style="display: none">
                                <label for="cheque_no">{{ __('cheque_no') }} <span class="text-danger">*</span></label>
                                <input type="number" id="cheque_no" name="cheque_no" placeholder="{{ __('cheque_no') }}" class="form-control cheque-no" required/>
                            </div>
                            <input class="btn btn-theme" type="submit" value={{ __('pay') }} />
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('js')
    <script>
        $('#payment-date').datepicker({
            format: "dd-mm-yyyy",
        }).datepicker("setDate", 'now');

        @if($student->fees_paid && $student->fees_paid->is_used_installment)
        $('.pay-in-installment').trigger('click').attr("disabled", true);
        @endif

        function successFunction() {
            window.location.href = "{{route('fees.paid.index')}}";
        }
    </script>
@endsection
