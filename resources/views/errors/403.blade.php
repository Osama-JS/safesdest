@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Forbidden'))
@section('desc', __('You don’t have permission to access this page. This could be due to insufficient permissions or a restricted area.'))
