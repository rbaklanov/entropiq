<?php

use App\Models\User;

describe('User::canonicalPhone', function () {
    it('keeps an 11 digit number starting with 7', function () {
        expect(User::canonicalPhone('79610893196'))->toBe('79610893196');
    });

    it('strips a plus sign and punctuation', function () {
        expect(User::canonicalPhone('+7 (961) 089-31-96'))->toBe('79610893196');
    });

    it('rewrites a leading 8 to 7', function () {
        expect(User::canonicalPhone('89610893196'))->toBe('79610893196');
    });
});

describe('User::isAdmin', function () {
    beforeEach(function () {
        config()->set('services.sms.demo_phone', '79990000000');
        config()->set('services.admin.phones', ['79610893196']);
    });

    it('returns true when the phone is in the admin list', function () {
        $user = new User(['phone' => '79610893196']);

        expect($user->isAdmin())->toBeTrue();
    });

    it('matches a plus prefixed phone against a canonical admin list', function () {
        $user = new User(['phone' => '+79610893196']);

        expect($user->isAdmin())->toBeTrue();
    });

    it('returns false for a regular user', function () {
        $user = new User(['phone' => '79001234567']);

        expect($user->isAdmin())->toBeFalse();
    });

    it('returns false when the admin list is empty', function () {
        config()->set('services.admin.phones', []);

        $user = new User(['phone' => '79610893196']);

        expect($user->isAdmin())->toBeFalse();
    });

    it('returns false for the SMS demo phone even if listed as admin', function () {
        config()->set('services.admin.phones', ['79990000000']);

        $user = new User(['phone' => '79990000000']);

        expect($user->isAdmin())->toBeFalse();
    });

    it('returns false when the phone is empty', function () {
        $user = new User(['phone' => '']);

        expect($user->isAdmin())->toBeFalse();
    });
});

describe('services.admin.phones config parsing', function () {
    afterEach(function () {
        putenv('ADMIN_PHONES');
        putenv('SMS_DEMO_PHONE');
    });

    it('parses formatted comma-separated phones and normalizes 8 to 7', function () {
        putenv('ADMIN_PHONES=79610893196, +7 (900) 111-22-33, 89112223344');
        putenv('SMS_DEMO_PHONE=79990000000');

        $config = require config_path('services.php');

        expect($config['admin']['phones'])->toBe([
            '79610893196',
            '79001112233',
            '79112223344',
        ]);
    });

    it('filters out invalid phone lengths, demo phone and empty entries', function () {
        putenv('ADMIN_PHONES=123, 79990000000, , 89610893196, 712345678901');
        putenv('SMS_DEMO_PHONE=79990000000');

        $config = require config_path('services.php');

        expect($config['admin']['phones'])->toBe([
            '79610893196',
        ]);
    });

    it('returns empty array when ADMIN_PHONES is empty', function () {
        putenv('ADMIN_PHONES=');

        $config = require config_path('services.php');

        expect($config['admin']['phones'])->toBe([]);
    });
});
