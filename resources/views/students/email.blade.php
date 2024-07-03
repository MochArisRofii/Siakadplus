<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
</head>
<body>
<h3>Hello {{$name}},<br>Username:{{$username}}<br>Password:{{$password}}</h3>
@if($child_name && $child_admission_number && $child_password)
    <h3>
        Child:{{$child_name}}
        <br>
        Login Credentials
        <br>
        GR Number : {{$child_admission_number}}
        <br>
        Password:{{$child_password}}
    </h3>
    <small style="color: red;">* If you have already changed the Parent Account Password, then this password will not work.</small>
@endif
</body>
</html>
