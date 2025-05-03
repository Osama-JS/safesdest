@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Unauthorized'))
@section('desc', __('You are not authorized to access this page. Please log in with the correct credentials to continue.'))
