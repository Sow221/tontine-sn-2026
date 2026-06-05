<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        return view('posts.index', compact('posts'));
    }

    public function show(Post $post)
    {
        $title = $post->title ?? 'Post';
        $excerpt = $post->excerpt ?? substr(strip_tags($post->content ?? ''), 0, 160);

        $ogImage = route('posts.og', ['post' => $post->id]);

        return view('posts.show', [
            'post' => $post,
            'title' => $title,
            'excerpt' => $excerpt,
            'ogImage' => $ogImage,
        ]);
    }

    public function ogImage(Post $post)
    {
        $title = $post->title ?? 'Post';

        // destination path in storage/app/public/og
        $filename = "post-{$post->id}-" . md5($post->updated_at ?? $post->created_at ?? $post->id) . '.png';
        $relative = "og/{$filename}";

        // if exists locally, serve it directly
        if (file_exists(storage_path('app/public/' . $relative))) {
            return response()->file(storage_path('app/public/' . $relative), [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'image/png',
            ]);
        }

        $width = 1200;
        $height = 630;

        // Prefer Intervention Image if available and local font exists
        $fontPath = public_path('fonts/Inter-Regular.ttf');
        if (class_exists('Intervention\\Image\\ImageManagerStatic') && file_exists($fontPath)) {
            $img = \Intervention\Image\ImageManagerStatic::canvas($width, $height, '#ffffff');
            // background accent
            $img->rectangle(0, $height - 120, $width, $height, function ($draw) {
                $draw->background('#009639');
            });
            // title text (basic)
            $img->text($title, 60, 180, function ($font) {
                $font->file(public_path('fonts/Inter-Regular.ttf'));
                $font->size(48);
                $font->color('#222222');
                $font->align('left');
                $font->valign('top');
            });

            // footer
            $img->text('TontineSN', $width - 220, $height - 80, function ($font) {
                $font->file(public_path('fonts/Inter-Regular.ttf'));
                $font->size(20);
                $font->color('#ffffff');
                $font->align('left');
                $font->valign('top');
            });

            // ensure directory
            if (!is_dir(storage_path('app/public/og'))) {
                mkdir(storage_path('app/public/og'), 0755, true);
            }
            $img->save(storage_path('app/public/' . $relative));

            return response()->file(storage_path('app/public/' . $relative), [
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Type' => 'image/png',
            ]);
        }

        // Fallback to GD (simple)
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 255, 255, 255);
        $textColor = imagecolorallocate($im, 34, 34, 34);
        $accent = imagecolorallocate($im, 0, 150, 80);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);
        imagefilledrectangle($im, 0, $height - 120, $width, $height, $accent);
        $fontSize = 5;
        $lines = wordwrap($title, 45, "\n");
        $y = 80;
        foreach (explode("\n", $lines) as $line) {
            $bbox = imagefontwidth($fontSize) * strlen($line);
            $x = (int)(($width - $bbox) / 2);
            imagestring($im, $fontSize, $x, $y, $line, $textColor);
            $y += imagefontheight($fontSize) + 10;
        }

        if (!is_dir(storage_path('app/public/og'))) {
            mkdir(storage_path('app/public/og'), 0755, true);
        }
        imagepng($im, storage_path('app/public/' . $relative));
        imagedestroy($im);

        return response()->file(storage_path('app/public/' . $relative), [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/png',
        ]);
    }
}

