@extends('layouts.master')

@section('title')
    {{__('fcm_key')}}
@endsection


@section('content')

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{__('fcm_key')}}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form id="formdata" class="setting-form" action="{{route('system-settings.update',1)}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-12 col-sm-12">
                                    <label for="data">{{__('fcm_server_key')}}</label>
                                    <input type="hidden" name="name" id="name" value="{{$name}}">
                                    <textarea rows="3" name="data" id="data" required placeholder="{{__('fcm_server_key')}}" class="form-control">{{$data ?? ''}}</textarea>
                                </div>
                            </div>
                            <input class="btn btn-theme" type="submit" value="Submit">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
