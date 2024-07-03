<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style>
        html, body {
            margin: 0px !important;
        }
        .full-width
        {
            width: 100%;
        }

        .header th{
            padding: 10px 0px;
            background-color: red;
        }
        table {
            border-collapse: collapse;
            border: none;
            font-size: 14px;
        }
        .student-image {
            width: 30%;
            padding: 0px 10px;
            text-align: center;
            vertical-align: middle;
            height: 80px;
        }
        .student-data {
            text-align: left;
            padding-left: 10px;
            padding: 5px;
        }
        .card-title {
            padding: 6px 0px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .school-name {
            padding-right: 10px !important;
            text-align: right;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom-right-radius: 10px;
        }
        .student-profile {
            border: 3px solid black;
            border-radius: 6px;
            background-size: contain;
            background-position: center center;
            background-repeat: no-repeat;
            padding: 2px;
        }
        .footer {
            background-color: blue;
            position: fixed;
            width: 96%;
            padding: 2px 10px;
            font-size: 12px;
            bottom: 0%;
            height: 15px;
            text-align: right;
            letter-spacing: 1.5px;
        }
        .school-logo {
            border-bottom-left-radius: 10px;
        }
    </style>
</head>
<body>
    @foreach ($students as $student)
        <table class="table full-width">
            <tr class="header">
                <th class="school-logo">
                    @if ($schoolSettings->data)
                        <img height="40" src="{{ public_path('storage/').$schoolSettings->getRawOriginal('data') }}" alt="">
                    @else
                        <img height="40" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
                    @endif
                </th>
                <th class="school-name" colspan="2">Matrushri R D Varsani Kumar Vidyalaya Bhuj - Mundra Road Near</th>
            </tr>
            <tr>
                <th class="card-title" colspan="3">Student Identification Card</th>
            </tr>
            <tr>
                <td class="student-image" rowspan="5">
                    @if ($student->image)
                        <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('storage/').$student->getRawOriginal('image') }}" alt="">
                    @else
                        <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('assets/dummy_logo.jpg') }}" alt="">    
                    @endif
                    
                </td>
                <th class="student-data">Student Name :</th>
                <td>{{ $student->full_name }}</td>
            </tr>
            <tr>
                <th class="student-data">Class Section :</th>
                <td>{{ $student->student->class_section->full_name }}</td>
            </tr>
            <tr>
                <th class="student-data">Roll No. :</th>
                <td>{{ $student->student->roll_number }}</td>
            </tr>
            <tr>
                <th class="student-data">Session Year :</th>
                <td>{{ $sessionYear->name }}</td>
            </tr>
            <tr>
                <th class="student-data">Guardian Contact :</th>
                <td>{{ $student->student->guardian->mobile }}</td>
            </tr>
        </table>
        <div class="footer">
            Valid Until {{ $valid_until }}
        </div>
    @endforeach
</body>
</html>