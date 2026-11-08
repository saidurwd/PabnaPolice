@props(['nav', 'navGallery', 'navDocument'])
                <!--- nav bar --->
                <div class="col-xl-12 col-lg-12 col-md-12 col-12 nav_bar bg_red_gradient_site" id="mainnav">
                    <div class="fr_lr">
                        <nav class="navbar">
                            <div class="container-fluid">
                                <div class="col-lg-12">
                                    <div class="navbar-header no-padding bg_black text-center">
                                        <div class="navbar-toggle no-padding padding-0-10 white nvcollapse cursor-pointer no-float" data-bs-toggle="collapse" data-bs-target="#nav2">
                                            <i class="fa fa-bars" aria-hidden="true"></i> Menu <i class="fa fa-bars" aria-hidden="true"></i>
                                        </div>
                                    </div>
                                    <div class="collapse navbar-collapse nd relative" id="nav2" style="z-index:170;">
                                        <div class="navdiv clearfix">
                                            <ul class="nav navbar-nav">
                                                <li class="dropdown"><a href="{{ url('/') }}">হোম</a></li>
                                                @foreach ($nav as $item)
                                                    <li class="dropdown">
                                                        <a href="#">{{ $item['category']->title }}</a>
                                                        <ul class="dropdown-menu">
                                                            @foreach ($item['contents'] as $c)
                                                                <li class="dropdown-submenu"><a href="{{ route('front.content.view', $c->id) }}">{{ $c->title }}</a></li>
                                                            @endforeach
                                                            @if (count($item['expanded']))
                                                                <li class="dropdown-submenu">
                                                                    @foreach ($item['expanded'] as $sub)
                                                                        <a href="{{ route('front.content.index', $sub['category']->id) }}">{{ $sub['category']->title }} <i class="fa fa-caret-right"></i></a>
                                                                        <ul class="dropdown-menu">
                                                                            @foreach ($sub['contents'] as $c)
                                                                                <li class="dropdown-submenu"><a href="{{ route('front.content.view', $c->id) }}">{{ $c->title }}</a></li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @endforeach
                                                                </li>
                                                            @endif
                                                            @foreach ($item['children'] as $child)
                                                                <li class="dropdown-submenu"><a href="{{ route('front.content.index', $child->id) }}">{{ $child->title }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </li>
                                                @endforeach
                                                @if ($navGallery)
                                                    <li class="dropdown">
                                                        <a href="#">{{ $navGallery['category']->title }}</a>
                                                        <ul class="dropdown-menu">
                                                            @foreach ($navGallery['items'] as $g)
                                                                <li class="dropdown-submenu"><a href="{{ route('front.gallery.index', $g->id) }}">{{ $g->title }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </li>
                                                @endif
                                                @if ($navDocument)
                                                    <li class="dropdown"><a href="{{ route('front.document.index', $navDocument->id) }}">{{ $navDocument->title }}</a></li>
                                                @endif
                                                <li class="dropdown"><a href="{{ route('front.contact') }}">যোগাযোগ</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
                <!-- end nav bar -->