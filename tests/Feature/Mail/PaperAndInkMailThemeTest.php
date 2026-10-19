<?php

use App\Mail\VerifyEmailMail;

describe('mail theme', function () {
    it('renders markdown mail on paper with the text wordmark', function () {
        $html = (new VerifyEmailMail('https://entropiq.test/verify'))->render();

        expect($html)
            ->toContain('#f5f0e6')
            ->toContain('#2c4a86')
            ->toContain('entropiq<span style="color: #a3431f;">.</span>')
            ->not->toContain('laravel.com/img/notification-logo');
    });
});
