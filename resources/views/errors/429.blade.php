@extends('errors::minimal')

@section('title', __('Too Many Requests'))
@section('code', '429')
@section('message', __('Too Many Requests'))
@section('desc', __('You’ve made too many requests in a short period of time. Please wait a moment before trying again.'))
