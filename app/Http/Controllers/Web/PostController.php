<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('author')
            ->latest('published_at')
            ->paginate(12);

        return view('posts.index', compact('posts'));
    }

    public function show(Post $post)
    {
        abort_if(! $post->published_at || $post->published_at->isFuture(), 404);

        return view('posts.show', compact('post'));
    }

    public function ogImage(Post $post): Response
    {
        abort_if(! $post->published_at || $post->published_at->isFuture(), 404);

        $title = htmlspecialchars($post->title, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630">
<rect width="1200" height="630" fill="#2D2F53"/>
<text x="60" y="100" font-family="Arial" font-size="28" fill="#009639" font-weight="bold">TontineSN Blog</text>
<text x="60" y="200" font-family="Arial" font-size="48" fill="white" font-weight="bold">{$title}</text>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
