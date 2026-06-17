@extends('emails.layout')

@section('title', $subject ?? 'TontineSN')

@section('content')
{!! strip_tags($body ?? '', '<strong><b><br><p><a><ul><li><h1><h2><h3><em><i><span><div>') !!}
@endsection
