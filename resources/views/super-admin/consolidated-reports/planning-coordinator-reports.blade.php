@extends('super-admin.consolidated-reports.layout')

@section('header_title')
    Planning Coordinator Reports
@endsection

@section('subnav')
    {{-- Standalone page: no tab bar --}}
@endsection

@section('content')
    @include('super-admin.consolidated-reports.partials.planning-coordinator-reports-content')
@endsection
