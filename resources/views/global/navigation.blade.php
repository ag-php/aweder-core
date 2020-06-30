<nav class="site-nav col-span-9 col sm-col-span-2">
    <input type="checkbox" id="menu-trigger" class="hidden" />
    <label class="site-nav__burger hidden s-flex" for="menu-trigger"></label>
    <ul class="site-menu">
        <li class="site-menu__item">
            <a href="{{ route('about.how-it-works') }}" class="site-menu__link">How it works</a>
        </li>
        <li class="site-menu__item">
            <a href="{{ route('login') }}" class="site-menu__link">Merchant login</a>
        </li>
        <li class="site-menu__item">
            <a href="{{ route('register') }}" class="site-menu__link">Merchant signup</a>
        </li>
    </ul>
</nav>


{{--<nav class="site-nav col col--lg-12-9 col--sm-6-2 col--sm-offset-6-5">--}}
    {{--<input type="checkbox" id="mobile-menu-trigger" />--}}
    {{--<label class="site-nav__burger" for="mobile-menu-trigger"></label>--}}
    {{--<ul class="site-menu site-menu--header">--}}
        {{--<li class="site-menu__item">--}}
            {{--<a href="{{ route('about.how-it-works') }}" class="site-menu__link">--}}
                {{--How it works--}}
            {{--</a>--}}
        {{--</li>--}}
        {{--<li class="site-menu__item">--}}
            {{--<a href="{{ route('login') }}" class="site-menu__link button button--outline-cream">--}}
                {{--<span class="button__content">Merchant login</span>--}}
            {{--</a>--}}
        {{--</li>--}}
        {{--<li class="site-menu__item">--}}
            {{--<a href="{{ route('register') }}" class="site-menu__link button button--filled-cream">--}}
                {{--<span class="button__content">Merchant sign up</span>--}}
            {{--</a>--}}
        {{--</li>--}}
    {{--</ul>--}}
{{--</nav>--}}
