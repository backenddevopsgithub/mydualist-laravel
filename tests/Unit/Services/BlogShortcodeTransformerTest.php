<?php

use App\Services\BlogShortcodeTransformer;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->transformer = app(BlogShortcodeTransformer::class);
});

test('warning box shortcode transforms to post info box alert markup', function () {
    $html = $this->transformer->transform('[warning_box]Share your list again[/warning_box]');

    expect($html)
        ->toContain('class="post-info-box alert"')
        ->toContain('Share your list again')
        ->not->toContain('[warning_box]');
});

test('tip box shortcode transforms to info light markup', function () {
    $html = $this->transformer->transform('[tip_box]Helpful tip[/tip_box]');

    expect($html)
        ->toContain('class="post-info-box info-light"')
        ->toContain('Helpful tip');
});

test('info box shortcode transforms with quoted text wrapper', function () {
    $html = $this->transformer->transform('[info_box]Remember Allah[/info_box]');

    expect($html)
        ->toContain('class="post-info-box"')
        ->toContain('“Remember Allah”');
});

test('comment box shortcode transforms to check variant', function () {
    $html = $this->transformer->transform('[comment_box]Leave a note[/comment_box]');

    expect($html)->toContain('class="post-info-box check"')
        ->toContain('Leave a note');
});

test('success box shortcode transforms to tip variant', function () {
    $html = $this->transformer->transform('[success_box]Well done[/success_box]');

    expect($html)->toContain('class="post-info-box tip"')
        ->toContain('Well done');
});

test('quranic border shortcode preserves inner html', function () {
    $html = $this->transformer->transform('[quranic_border]اللَّهُمَّ[/quranic_border]');

    expect($html)
        ->toContain('<div class="quranic-border">اللَّهُمَّ</div>')
        ->not->toContain('[quranic_border]');
});

test('hadith border shortcode transforms wrapper', function () {
    $html = $this->transformer->transform('[hadith_border]The Prophet said...[/hadith_border]');

    expect($html)
        ->toContain('<div class="hadith-border">The Prophet said...</div>');
});

test('bismillah shortcode transforms to image block', function () {
    $html = $this->transformer->transform('<p>Intro</p>[bismillah]<p>Body</p>');

    expect($html)
        ->toContain('class="bismillah"')
        ->toContain('class="bismillah-arabic"')
        ->toContain('In the Name of Allah—the Most Compassionate, Most Merciful.')
        ->not->toContain('[bismillah]');
});

test('linebreak shortcode transforms to styled hr', function () {
    $html = $this->transformer->transform('[linebreak]');

    expect($html)->toBe('<hr class="style-seven">');
});

test('bullet list shortcode transforms numbered point block', function () {
    $html = $this->transformer->transform('[bullet_list bullet_no="3"]Repeat daily[/bullet_list]');

    expect($html)
        ->toContain('class="points bullet_lists"')
        ->toContain('<h4>3</h4>')
        ->toContain('Repeat daily');
});

test('pilgrim faq shortcode transforms accordion markup', function () {
    $html = $this->transformer->transform('[pilgrim_faq title="What is the dua?"]Answer text[/pilgrim_faq]');

    expect($html)
        ->toContain('class="faq"')
        ->toContain('id="whatisthedua?"')
        ->toContain('class="faq-heading">What is the dua?</p>')
        ->toContain('class="faq-text">Answer text</p>');
});

test('nested shortcodes transform inside out', function () {
    $html = $this->transformer->transform(
        '[quranic_border][warning_box]Stay consistent[/warning_box][/quranic_border]',
    );

    expect($html)
        ->toContain('<div class="quranic-border"><div class="post-info-box alert">')
        ->toContain('Stay consistent')
        ->not->toContain('[');
});

test('unclosed hadith border is repaired and transformed with warning log', function () {
    Log::spy();

    $html = $this->transformer->transform('[hadith_border]Travel dua text');

    expect($html)->toContain('<div class="hadith-border">Travel dua text</div>');
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('BlogShortcodeTransformer repaired unclosed shortcode tags.', \Mockery::on(
            fn (array $context): bool => $context['tag'] === 'hadith_border' && $context['missing_closing_tags'] === 1,
        ));
});

test('plain html outside shortcodes is preserved', function () {
    $input = '<p>Friday is blessed.</p>[linebreak]<h2>Section</h2>';
    $html = $this->transformer->transform($input);

    expect($html)->toBe('<p>Friday is blessed.</p><hr class="style-seven"><h2>Section</h2>');
});

test('popup cta shortcode is left unchanged', function () {
    $shortcode = '[popup_cta popupid=123 video_embed_url="https://example.com" title="Video" button_text="Watch"][/popup_cta]';

    expect($this->transformer->transform($shortcode))->toBe($shortcode);
});

test('blog content stylesheet defines scoped selectors', function () {
    $css = file_get_contents(resource_path('css/blog-content.css'));

    foreach ([
        '.blog-content .quranic-border',
        '.blog-content .hadith-border',
        '.blog-content .post-info-box',
        '.blog-content .bismillah',
        '.blog-content hr.style-seven',
        '.blog-content .faq',
        '.blog-content .bullet_lists',
    ] as $selector) {
        expect($css)->toContain($selector);
    }

    expect($css)->not->toMatch('/^\.quranic-border\b/m')
        ->not->toMatch('/^\.hadith-border\b/m')
        ->not->toMatch('/^\.post-info-box\b/m');
});
