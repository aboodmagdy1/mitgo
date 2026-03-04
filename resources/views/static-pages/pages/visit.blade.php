@extends('static-pages.layouts.main')
@section('title', $page->title)
@section('content')
  @if(app()->getLocale() == 'ar')
    {!! $page->page_layout_ar !!}
  @else
    {!! $page->page_layout_en !!}
  @endif
@endsection


