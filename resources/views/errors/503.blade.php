@extends('errors::minimal')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Service Unavailable'))
@section('desc', __('The server is currently down for maintenance or overloaded. Please try again later.'))
