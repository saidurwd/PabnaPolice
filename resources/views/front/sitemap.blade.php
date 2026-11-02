<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if ($u['lastmod'])
        <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
        <priority>{{ $u['priority'] }}</priority>
    </url>
@endforeach
</urlset>
