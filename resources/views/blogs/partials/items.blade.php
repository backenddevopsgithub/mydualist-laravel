@foreach ($posts as $post)
    @include('blogs.partials.item', [
        'post' => $post,
        'itemIndex' => ($posts->firstItem() ?? 0) + $loop->index,
    ])
@endforeach
