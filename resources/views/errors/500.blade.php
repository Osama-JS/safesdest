@extends('errors::minimal')

@section('title', __('Server Error'))
@section('code', '500')
@section('message', __('Server Error'))
@section('desc', __('Something went wrong on our end. We’re working to resolve the issue as quickly as possible. Please try again later. If the problem persists, contact our support team.'))

