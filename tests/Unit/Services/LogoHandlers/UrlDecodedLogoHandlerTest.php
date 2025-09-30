<?php

declare(strict_types=1);

use App\Contracts\LogoHandlerInterface;
use App\Services\LogoHandlers\UrlDecodedLogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

it('can handle percent-encoded data URIs', function () {
    $handler = new UrlDecodedLogoHandler();
    $request = new LogoRequest(
        raw: 'data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUg',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    // Should handle it by passing decoded version to next handler
    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldReceive('handle')->once()->andReturn(
        new LogoResult(
            dataUri: 'data:image/png;base64,test',
            width: 14,
            height: 14,
            mime: 'png'
        )
    );
    $handler->setNext($mockNext);

    expect($handler->handle($request))->toBeInstanceOf(LogoResult::class);
});

it('cannot handle regular data URIs', function () {
    $handler = new UrlDecodedLogoHandler();
    $request = new LogoRequest(
        raw: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('cannot handle raw base64', function () {
    $handler = new UrlDecodedLogoHandler();
    $request = new LogoRequest(
        raw: 'iVBORw0KGgoAAAANSUhEUg',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('decodes and passes to next handler', function () {
    $handler = new UrlDecodedLogoHandler();

    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(function (LogoRequest $req) {
            // Verify that the decoded URI is passed
            return $req->raw === 'data:image/svg+xml;base64,PHN2Zz4=';
        }))
        ->andReturn(new LogoResult(
            dataUri: 'data:image/svg+xml;base64,PHN2Zz4=',
            width: 14,
            height: 14,
            mime: 'svg+xml'
        ));

    $handler->setNext($mockNext);

    $request = new LogoRequest(
        raw: 'data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2Zz4%3D',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);
    expect($result)->toBeInstanceOf(LogoResult::class);
});

it('rejects decoded URIs that do not start with data:image/', function () {
    $handler = new UrlDecodedLogoHandler();

    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldNotReceive('handle');
    $handler->setNext($mockNext);

    $request = new LogoRequest(
        raw: 'data%3Atext%2Fplain%3Bbase64%2CaGVsbG8=', // data:text/plain after decode
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('handles case-insensitive data URI pattern', function () {
    $handler = new UrlDecodedLogoHandler();

    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldReceive('handle')->once()->andReturn(
        new LogoResult(
            dataUri: 'data:image/png;base64,test',
            width: 14,
            height: 14,
            mime: 'png'
        )
    );
    $handler->setNext($mockNext);

    $request = new LogoRequest(
        raw: 'DATA%3AIMAGE%2FPNG%3Bbase64%2Ctest', // uppercase DATA/IMAGE
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeInstanceOf(LogoResult::class);
});

it('passes to next handler when pattern does not match', function () {
    $handler = new UrlDecodedLogoHandler();

    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldReceive('handle')->once()->andReturn(
        new LogoResult(
            dataUri: 'data:image/png;base64,test',
            width: 14,
            height: 14,
            mime: 'png'
        )
    );
    $handler->setNext($mockNext);

    $request = new LogoRequest(
        raw: 'some-slug',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);
    expect($result)->toBeInstanceOf(LogoResult::class);
});

afterEach(function () {
    Mockery::close();
});
