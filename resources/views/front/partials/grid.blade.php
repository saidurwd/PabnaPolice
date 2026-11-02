{{-- Listing table (replaces CGridView): title link + published date, with the filter row. --}}
<form method="get" action="{{ url()->current() }}">
<div class="grid-view">
    <table class="table table-bordered table-striped table-hover smart-form">
        <thead>
            <tr><th>Title</th><th>Published Date</th></tr>
            <tr class="filters">
                <td><input class="form-control" name="title" type="text" value="{{ request('title') }}"></td>
                <td><input class="form-control" name="date" type="text" value="{{ request('date') }}"></td>
            </tr>
        </thead>
        <tbody>
        @forelse ($rows as $row)
            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                <td><a href="{{ $linkTo($row) }}">{{ $row->title }}</a></td>
                <td style="width:220px;">{{ legacy_date($row->published_at) }}</td>
            </tr>
        @empty
            <tr><td colspan="2" class="empty"><span class="empty">No result found.</span></td></tr>
        @endforelse
        </tbody>
    </table>
    <button type="submit" style="display:none"></button>
    <div class="pager widget-footer">{{ $rows->links() }}</div>
</div>
</form>
