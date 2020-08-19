<!doctype html>
<html dir="ltr" lang="en" class="no-js">
@include('global/head')
<body @if (isset($bodyClass)) class="{{ $bodyClass }}" @endif>
@if (app()->environment() === 'local')
<div class="grid-overlay">
    <div class="row flex justify-content-center">
        <div class="content">
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
            <div class="grid__item"></div>
        </div>
    </div>
</div>
@endif
<div class="site-wrapper" id="app">
    @include('global/header')
    @include('shared/notification')
    <notification></notification>
    <main class="flex flex-col align-items-center">
        @yield('content')
    </main>
</div>
@include('global/footer')
<script src="/dist/main.js?{{ time() }}"></script>
@if (app()->environment() !== 'local' && (url()->current() === route('home') || url()->current() === route('about.how-it-works') || url()->current() === route('admin.dashboard')))
    <script type="text/javascript">
        (function(d, src, c) { var t=d.scripts[d.scripts.length - 1],s=d.createElement('script');s.id='la_x2s6df8d';s.async=true;s.src=src;s.onload=s.onreadystatechange=function(){var rs=this.readyState;if(rs&&(rs!='complete')&&(rs!='loaded')){return;}c(this);};t.parentElement.insertBefore(s,t.nextSibling);})(document,
            'https://awe-der.ladesk.com/scripts/track.js',
            function(e){ LiveAgent.createButton('rxj6nsq9', e); });
    </script>
@endif
</body>
</html>
