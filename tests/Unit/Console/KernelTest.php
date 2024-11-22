<?php

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;

beforeEach(function () {
    $this->app = app();
    /** @var Dispatcher|\Mockery\MockInterface $dispatcher */
    $this->dispatcher = \Mockery::mock(Dispatcher::class)->makePartial();
    $this->kernel = new Kernel($this->app, $this->dispatcher);
});

afterEach(function () {
    \Mockery::close();
});

it('extends ConsoleKernel', function () {
    expect($this->kernel)->toBeInstanceOf(Illuminate\Foundation\Console\Kernel::class);
});

it('has an empty commands array', function () {
    $commands = (new ReflectionClass($this->kernel))->getProperty('commands');
    $commands->setAccessible(true);
    expect($commands->getValue($this->kernel))->toBeArray()->toBeEmpty();
});

it('has a schedule method', function () {
    expect(method_exists($this->kernel, 'schedule'))->toBeTrue();
});

it('calls schedule method without throwing exceptions', function () {
    $schedule = $this->app->make(Schedule::class);

    $method = new ReflectionMethod($this->kernel, 'schedule');
    $method->setAccessible(true);

    expect(fn() => $method->invoke($this->kernel, $schedule))->not->toThrow(Exception::class);
});

it('returns void from schedule method', function () {
    $schedule = $this->app->make(Schedule::class);

    $method = new ReflectionMethod($this->kernel, 'schedule');
    $method->setAccessible(true);

    $result = $method->invoke($this->kernel, $schedule);
    expect($result)->toBeNull();
});