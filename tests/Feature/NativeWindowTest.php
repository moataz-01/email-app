<?php

use App\Providers\NativeAppServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('desktop opens the local launch route with usable resizing limits', function () {
    config(['nativephp-internal.api_url' => 'http://native.test/']);
    Http::preventStrayRequests();
    Http::fake(['http://native.test/window/open' => Http::response([])]);

    (new NativeAppServiceProvider)->boot();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://native.test/window/open'
        && $request['url'] === route('home')
        && $request['width'] === 1440
        && $request['height'] === 900
        && $request['minWidth'] === 800
        && $request['minHeight'] === 600);
});
