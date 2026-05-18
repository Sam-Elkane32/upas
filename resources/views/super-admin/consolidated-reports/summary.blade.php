@extends('super-admin.consolidated-reports.layout')

@section('header_title')
    Summary of Accomplishments
@endsection

@section('subnav')
    {{-- Standalone page: no tab bar --}}
@endsection

@section('content')
    @include('super-admin.consolidated-reports.partials.summary-content')
@endsection
