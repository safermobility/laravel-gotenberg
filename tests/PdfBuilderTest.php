<?php

use SaferMobility\LaravelGotenberg\Enums\Unit;
use SaferMobility\LaravelGotenberg\PdfBuilder;

test('use string `px` unit to set margin use Enum `Unit::Pixel`', function () {
    $pdfBuilder = new PdfBuilder;

    expect($pdfBuilder->margins(unit: 'px'))
        ->toBeObject()
        ->and($pdfBuilder->margins['unit'])
        ->toEqual(Unit::Pixel->value);
});

test('use invalid string `Px` unit to set margin throw an exception', function () {
    $pdfBuilder = new PdfBuilder;
    $pdfBuilder->margins(unit: 'Px');
})->throws(ValueError::class);

test('use string `mm` unit to set paperSize use Enum `Unit::Millimeter`', function () {
    $pdfBuilder = new PdfBuilder;

    expect($pdfBuilder->paperSize(450, 450, 'mm'))
        ->toBeObject()
        ->and($pdfBuilder->paperSize['unit'])
        ->toEqual(Unit::Millimeter->value);
});

test('use invalid string `mM` unit to set paperSize throw an exception', function () {
    $pdfBuilder = new PdfBuilder;
    expect($pdfBuilder->paperSize(450, 450, 'mM'));
})->throws(ValueError::class);
