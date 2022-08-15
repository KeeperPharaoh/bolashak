@extends('voyager::bread.read')
<?php

?>
<style>
    .content {
        margin-top: 0px !important;
    }
</style>
@section('content')
    <div class="content">
        <div>
            <h3>ФИО: {{$dataTypeContent->full_name}} </h3>
            <h3>Телефон: {{$dataTypeContent->phone_number}} </h3>
        </div>
        <br>
        <h3>Результаты тестов: </h3>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>Название теста</th>
                <th>Результат</th>
            </tr>
            </thead>
            <tbody>
            @foreach($results as $test)
                <tr>
                    <td>{{$test->test}}</td>
                    <td>{{$test->result}}</td>
                </tr>
            @endforeach
            </tbody>
    </div>
@endsection

