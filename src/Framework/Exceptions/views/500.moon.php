@extends("errors::minimal")

@section('title', 'Server Error')
@section('code', '500')
@section('message', $exception->getMessage() ?: 'Server Error')