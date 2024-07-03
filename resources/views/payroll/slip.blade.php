<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <style>
        .full-width {
            width: 100%;
        }

        .text-left {
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .title-heading {
            background-color: rgb(191, 191, 191);
            padding: 5px 0px;
            border-radius: 8px;
        }
        .salary-title {
            font-size: 18px;
            padding: 10px 0px;
        }
        .salary-month-title {
            font-size: 14px;
            color: gray;
        }
        .salary-month {
            font-size: 18px;
        }
        .school-name {
            font-size: 20px;
        }
        .school-address {
            font-size: 14px;
            color: gray;
            padding: 0px 3px;
        }
        .end-header {
            margin-bottom: 30px;
        }
        .row {
            display: flex;
        }
        .col-md-6 {
            position: fixed;
            width: 100%;
        }
        .net-salary {
            left: 60%;
            border: 1px solid gray;
            border-radius: 8px;
            width: 40%;
        }
        .employee-detail tr th {
            padding: 5px 5px;
        }
        .label {
            color: gray;
        }
        table {
            border-collapse: collapse;
            border: none;
        }
        .net-salary tr th {
            padding: 5px 20px;
            text-align: left;
        }
        .net-salary-amount {
            font-size: 25px;
            letter-spacing: 2px;
            border-left: 3px solid #5FD068;
            padding-left: 8px;
        }
        .employee-detail {
            width: 60%;
        }
        .net-salary-lable {
            font-size: 12px;
            color: grey;
        }
        .net-salary-div {
            padding-bottom: 5px;
        }
        .net-salary-hr {
            color: gray;
            margin-bottom: 0px;
        }
        .net-amount-cell {
            background-color: #EDFCF1;
            border-radius: 8px 8px 0px 0px;
        }
        .salary-detail {
            position: relative;
            top: 20%;
            border: 1px solid grey;
            border-radius: 8px;
        }
        .salary-detail table tr th, .salary-detail table tr td{
            padding: 10px 20px;
        }
        .salary-detail table tr .uppercase {
            border-bottom: 1px solid gray;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .salary-detail table tr .table-footer {
            background-color: lightgrey;
        }
        .total-payable {
            position: relative;
            top: 24%;
            border: 1px solid grey;
            border-radius: 8px;
        }
        .total-payable table tr th {
            padding: 5px 10px;
        }
        .total-balance {
            font-size: 12px;
            color: gray;
            font-style: normal;
            margin-top: 0px;
        }
        .payable-amount {
            background-color: #EDFCF1;
            border-radius: 0px 8px 8px 0px;
        }
        .paid-leaves {
            color: gray;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="body">
        <table class="full-width">
            <tr>
                <th class="text-left" width="50">
                    <img class="school-logo" height="50" src="{{ public_path('storage/').$vertical_logo->getRawOriginal('data') }}" alt="">
                </th>
                <th class="text-left">
                    <div class="school-name">
                        {{ $schoolSetting['school_name'] }}
                    </div>
                    <div class="school-address">
                        {{ $schoolSetting['school_address'] }}
                    </div>
                </th>
                <th class="text-right" width="140">
                    <div class="salary-month-title">
                        Salary Slip For The Month
                    </div>
                    <div class="salary-month">
                        {{ $salary->title }}
                    </div>
                </th>
            </tr>
        </table>

        <hr class="end-header">

        @php
            $lwp = 0;
            $lwp_amount = 0;
            $allowance = 0;
            $deduction = 0;
        @endphp

        @if ($salary->paid_leaves < $total_leaves)
        {{ number_format($total_leaves - $salary->paid_leaves, 2) }}
            @php
                $lwp = number_format($total_leaves - $salary->paid_leaves, 2);
            @endphp
        
        @endif
        <div class="row">
            <div class="col-md-6 employee-detail">
                <table class="full-width">
                    <tr>
                        <th colspan="2" class="text-left uppercase">
                            Employee Summary
                        </th>
                    </tr>
                    <tr>
                        <th class="text-left label">
                            Employee Name
                        </th>
                        <td class="text-left">
                            : {{ $salary->staff->user->full_name }}
                        </td>
                    </tr>
                    <tr>
                        <th class="text-left label">
                            Employee ID
                        </th>
                        <td class="text-left">
                            : {{ $salary->staff->id }}
                        </td>
                    </tr>
                    <tr>
                        <th class="text-left label">
                            Pay Month
                        </th>
                        <td class="text-left">
                            : {{ $salary->title }}
                        </td>
                    </tr>
                    <tr>
                        <th class="text-left label">
                            Date
                        </th>
                        <td class="text-left">
                            : {{ format_date($salary->date) }}
                        </td>
                    </tr>
                    
                </table>
            </div>
            
            <div class="col-md-6 net-salary">
                <table class="full-width net-salary-div">
                    <tr>
                        <th colspan="2" class="net-amount-cell">
                            <div class="net-salary-amount">
                                {{ number_format($salary->amount,2) }}
                                <p class="net-salary-lable">Employee Net Salary</p>
                            </div>
                            <hr class="net-salary-hr">
                        </th>
                    </tr>
                    <tr>
                        <th class="label">
                            Paid Days
                        </th>
                        <td>
                            : {{ $days - $lwp }}
                        </td>
                    </tr>
                    <tr>
                        <th class="label">
                            LWP Days
                        </th>
                        <td>
                            : {{ $lwp }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row salary-detail">
            <table class="full-width">
                <tr>
                    <th class="text-left uppercase">
                        Earnings
                    </th>
                    <th class="text-right uppercase">
                        Amount
                    </th>
                    <th class="text-left uppercase">
                        Deductions
                    </th>
                    <th class="text-right uppercase">
                        Amount
                    </th>
                </tr>
                <tr>
                    <td class="text-left">
                        Basic
                    </td>
                    <td class="text-right">
                        {{ number_format($salary->basic_salary,2) }}
                    </td>
                    <td class="text-left">
                        Leave Without Pay
                        <br>
                        <span class="paid-leaves">Paid Leaves : {{ $salary->paid_leaves }}</span>
                    </td>
                    <td class="text-right">
                        @if ($lwp)
                            @php
                                $lwp_amount = ($salary->basic_salary / 30) * $lwp;
                            @endphp
                            {{ number_format($lwp_amount,2) }}
                        @else
                            00.00
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-left">
                        Allowance
                    </td>
                    <td class="text-right">
                        @if ($salary->amount > ($salary->basic_salary - $lwp_amount))
                            @php
                                $allowance = $salary->amount - ($salary->basic_salary - $lwp_amount);
                            @endphp
                            {{ number_format($allowance, 2) }}

                        @else
                            00.00
                        @endif
                    </td>
                    <td class="text-left">
                        Deduction
                    </td>
                    <td class="text-right">
                        @if ($salary->amount < ($salary->basic_salary - $lwp_amount))
                            @php
                                $deduction = ($salary->basic_salary - $lwp_amount) - $salary->amount;
                            @endphp
                            {{ number_format($deduction, 2) }}

                        @else
                            00.00
                        @endif
                    </td>
                </tr>
                <tr>
                    <th class="text-left table-footer" style="border-bottom-left-radius: 8px">
                        Gross Earnings
                    </th>
                    <td class="text-right table-footer">
                        {{ number_format($salary->basic_salary + $allowance, 2) }}
                    </td>
                    <th class="text-left table-footer">
                        Total Deduction
                    </th>
                    <td class="text-right table-footer" style="border-bottom-right-radius: 8px">
                        {{ number_format($lwp_amount + $deduction, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="row total-payable">
            <table class="full-width">
                <tr>
                    <th class="uppercase text-left">
                        Total Net Payable
                        <span class="text-muted total-balance"><br>
                            Gross Earnings - Total Deductions
                        </span>
                    </th>
                    <th class="text-right payable-amount">
                        {{ number_format($salary->amount,2) }}
                    </th>
                </tr>
            </table>
        </div>
        
        
    </div>
</body>
</html>