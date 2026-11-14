<?php

describe('mobile horizontal overflow guards', function () {
    it('keeps the landing calculator grid to one shrinkable column on mobile', function () {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('grid max-w-6xl grid-cols-1 items-start', false);
    });

    it('lets long words in legal headings wrap', function (string $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('prose prose-gray mx-auto max-w-3xl break-words [&_h1]:hyphens-auto', false);
    })->with(['privacy', 'terms', 'offer', 'consent']);
});
