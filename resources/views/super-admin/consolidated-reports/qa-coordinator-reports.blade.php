@extends('super-admin.consolidated-reports.layout')

@section('header_title')
    QA Coordinator Reports
@endsection

@section('subnav')
    {{-- Standalone page: no tab bar --}}
@endsection

@section('content')
    @include('super-admin.consolidated-reports.partials.qa-coordinator-reports-content')
@endsection
