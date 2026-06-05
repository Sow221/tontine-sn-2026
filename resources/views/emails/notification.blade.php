@extends('emails.layout')

@section('title', $subject ?? 'TontineSN')

@section('content')
{!! $body ?? '' !!}
@endsection
