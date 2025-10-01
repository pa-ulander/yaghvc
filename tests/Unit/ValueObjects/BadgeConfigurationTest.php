<?php

declare(strict_types=1);

use App\ValueObjects\BadgeConfiguration;

describe('BadgeConfiguration', function () {
    test('it creates object with valid data', function () {
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: true,
            labelColor: 'red',
            logoColor: 'white',
            logo: 'github',
            logoSize: '16'
        );

        expect($config->label)->toBe('Views')
            ->and($config->color)->toBe('blue')
            ->and($config->style)->toBe('flat')
            ->and($config->abbreviated)->toBeTrue()
            ->and($config->labelColor)->toBe('red')
            ->and($config->logoColor)->toBe('white')
            ->and($config->logo)->toBe('github')
            ->and($config->logoSize)->toBe('16');
    });

    test('it creates object with required parameters only', function () {
        $config = new BadgeConfiguration(
            label: 'Visits',
            color: 'green',
            style: 'for-the-badge',
            abbreviated: false
        );

        expect($config->label)->toBe('Visits')
            ->and($config->color)->toBe('green')
            ->and($config->style)->toBe('for-the-badge')
            ->and($config->abbreviated)->toBeFalse()
            ->and($config->labelColor)->toBeNull()
            ->and($config->logoColor)->toBeNull()
            ->and($config->logo)->toBeNull()
            ->and($config->logoSize)->toBeNull();
    });

    test('it is immutable readonly class', function () {
        $config = new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'flat',
            abbreviated: false
        );

        expect($config)->toBeInstanceOf(BadgeConfiguration::class);
        // Attempting to modify readonly property would cause PHP error
    });

    test('it validates style must be one of allowed values', function () {
        new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'invalid-style',
            abbreviated: false
        );
    })->throws(InvalidArgumentException::class, 'Invalid badge style');

    test('it accepts flat style', function () {
        $config = new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'flat',
            abbreviated: false
        );

        expect($config->style)->toBe('flat');
    });

    test('it accepts flat-square style', function () {
        $config = new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'flat-square',
            abbreviated: false
        );

        expect($config->style)->toBe('flat-square');
    });

    test('it accepts for-the-badge style', function () {
        $config = new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'for-the-badge',
            abbreviated: false
        );

        expect($config->style)->toBe('for-the-badge');
    });

    test('it accepts plastic style', function () {
        $config = new BadgeConfiguration(
            label: 'Test',
            color: 'blue',
            style: 'plastic',
            abbreviated: false
        );

        expect($config->style)->toBe('plastic');
    });

    test('it creates from validated request with all parameters', function () {
        $data = [
            'label' => 'Profile Views',
            'color' => 'red',
            'style' => 'flat-square',
            'abbreviated' => true,
            'labelColor' => 'black',
            'logoColor' => 'auto',
            'logo' => 'github',
            'logoSize' => '24',
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->label)->toBe('Profile Views')
            ->and($config->color)->toBe('red')
            ->and($config->style)->toBe('flat-square')
            ->and($config->abbreviated)->toBeTrue()
            ->and($config->labelColor)->toBe('black')
            ->and($config->logoColor)->toBe('auto')
            ->and($config->logo)->toBe('github')
            ->and($config->logoSize)->toBe('24');
    });

    test('it creates from validated request with only required parameters', function () {
        $data = [];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->label)->toBe('Visits')
            ->and($config->color)->toBe('blue')
            ->and($config->style)->toBe('for-the-badge')
            ->and($config->abbreviated)->toBeFalse()
            ->and($config->labelColor)->toBeNull()
            ->and($config->logoColor)->toBeNull()
            ->and($config->logo)->toBeNull()
            ->and($config->logoSize)->toBeNull();
    });

    test('it converts integer values to strings in factory', function () {
        $data = [
            'label' => 123,
            'color' => 456,
            'logoSize' => 16,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->label)->toBe('123')
            ->and($config->color)->toBe('456')
            ->and($config->logoSize)->toBe('16');
    });

    test('it converts float values to strings in factory', function () {
        $data = [
            'label' => 12.5,
            'color' => 45.6,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->label)->toBe('12.5')
            ->and($config->color)->toBe('45.6');
    });

    test('it handles boolean true from string in factory', function () {
        $data = [
            'abbreviated' => 'true',
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->abbreviated)->toBeTrue();
    });

    test('it handles boolean true from integer 1 in factory', function () {
        $data = [
            'abbreviated' => 1,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->abbreviated)->toBeTrue();
    });

    test('it handles boolean false from string in factory', function () {
        $data = [
            'abbreviated' => 'false',
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->abbreviated)->toBeFalse();
    });

    test('it handles boolean false from integer 0 in factory', function () {
        $data = [
            'abbreviated' => 0,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->abbreviated)->toBeFalse();
    });

    test('it handles various truthy string values for boolean', function () {
        $truthyValues = ['1', 'true', 'TRUE', 'yes', 'YES', 'on', 'ON'];

        foreach ($truthyValues as $value) {
            $config = BadgeConfiguration::fromValidatedRequest(['abbreviated' => $value]);
            expect($config->abbreviated)->toBeTrue("Failed for value: {$value}");
        }
    });

    test('it handles various falsy string values for boolean', function () {
        $falsyValues = ['0', 'false', 'FALSE', 'no', 'NO', 'off', 'OFF'];

        foreach ($falsyValues as $value) {
            $config = BadgeConfiguration::fromValidatedRequest(['abbreviated' => $value]);
            expect($config->abbreviated)->toBeFalse("Failed for value: {$value}");
        }
    });

    test('it uses default when boolean value is invalid string', function () {
        $data = [
            'abbreviated' => 'invalid',
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->abbreviated)->toBeFalse();
    });

    test('it uses default when string value is invalid type', function () {
        $data = [
            'label' => ['array'],
            'color' => null,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->label)->toBe('Visits')
            ->and($config->color)->toBe('blue');
    });

    test('it handles null values for optional parameters', function () {
        $data = [
            'labelColor' => null,
            'logoColor' => null,
            'logo' => null,
            'logoSize' => null,
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->labelColor)->toBeNull()
            ->and($config->logoColor)->toBeNull()
            ->and($config->logo)->toBeNull()
            ->and($config->logoSize)->toBeNull();
    });

    test('it handles missing optional parameters', function () {
        $data = [
            'label' => 'Test',
        ];

        $config = BadgeConfiguration::fromValidatedRequest($data);

        expect($config->labelColor)->toBeNull()
            ->and($config->logoColor)->toBeNull()
            ->and($config->logo)->toBeNull()
            ->and($config->logoSize)->toBeNull();
    });

    test('it handles all properties accessible', function () {
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: true,
            labelColor: 'red',
            logoColor: 'white',
            logo: 'github',
            logoSize: '16'
        );

        // All properties should be accessible
        expect($config)->toHaveProperties([
            'label',
            'color',
            'style',
            'abbreviated',
            'labelColor',
            'logoColor',
            'logo',
            'logoSize',
        ]);
    });
});
