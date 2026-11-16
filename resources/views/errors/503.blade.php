@extends('errors.layout')
@section('title', 'Back soon')
@section('code', '503')
@section('heading', 'We’ll be right back')
@section('message', 'Linkora is getting a quick update. Please check back in a few minutes.')
@section('actions')
    <button class="btn primary" type="button" onclick="location.reload()"><i class="fa-solid fa-rotate"></i>Try again</button>
@endsection
