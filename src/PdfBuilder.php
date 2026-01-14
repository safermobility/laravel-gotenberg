<?php

namespace SaferMobility\LaravelGotenberg;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SaferMobility\LaravelGotenberg\Enums\Format;
use SaferMobility\LaravelGotenberg\Enums\Orientation;
use SaferMobility\LaravelGotenberg\Enums\Unit;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfBuilder implements Responsable
{
    public const ENDPOINT_URL = '/forms/chromium/convert/html';

    public string $viewName = '';

    public array $viewData = [];

    public string $html = '';

    public string $headerViewName = '';

    public array $headerData = [];

    public ?string $headerHtml = null;

    public string $footerViewName = '';

    public array $footerData = [];

    public ?string $footerHtml = null;

    public string $downloadName = '';

    public string $disposition = 'inline';

    public ?array $paperSize = null;

    public ?string $orientation = null;

    public ?array $margins = null;

    public ?float $scale = null;

    protected string $visibility = 'private';

    protected ?Closure $customizeRequest = null;

    protected array $responseHeaders = [
        'Content-Type' => 'application/pdf',
    ];

    protected bool $onLambda = false;

    protected ?string $diskName = null;

    public function view(string $view, array $data = []): self
    {
        $this->viewName = $view;

        $this->viewData = $data;

        return $this;
    }

    public function headerView(string $view, array $data = []): self
    {
        $this->headerViewName = $view;

        $this->headerData = $data;

        return $this;
    }

    public function footerView(string $view, array $data = []): self
    {
        $this->footerViewName = $view;

        $this->footerData = $data;

        return $this;
    }

    public function landscape(): self
    {
        return $this->orientation(Orientation::Landscape);
    }

    public function portrait(): self
    {
        return $this->orientation(Orientation::Portrait);
    }

    public function orientation(string|Orientation $orientation): self
    {
        if ($orientation instanceof Orientation) {
            $orientation = $orientation->value;
        }

        $this->orientation = $orientation;

        return $this;
    }

    public function inline(string $downloadName = ''): self
    {
        $this->name($downloadName);

        $this->disposition = 'inline';

        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function headerHtml(string $html): self
    {
        $this->headerHtml = $html;

        return $this;
    }

    public function footerHtml(string $html): self
    {
        $this->footerHtml = $html;

        return $this;
    }

    public function download(?string $downloadName = null): self
    {
        $this->downloadName ?: $this->name($downloadName ?? 'download');

        $this->disposition = 'attachment';

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->addHeaders($headers);

        return $this;
    }

    public function name(string $downloadName): self
    {
        if (! str_ends_with(strtolower($downloadName), '.pdf')) {
            $downloadName .= '.pdf';
        }

        $this->downloadName = $downloadName;

        return $this;
    }

    public function base64(): string
    {
        $content = $this->doRequest()->getBody();

        return base64_encode($content);
    }

    public function margins(
        float $top = 0,
        float $right = 0,
        float $bottom = 0,
        float $left = 0,
        Unit|string $unit = 'in'
    ): self {
        $unit = $this->getUnitValue($unit);

        $this->margins = compact(
            'top',
            'right',
            'bottom',
            'left',
            'unit',
        );

        return $this;
    }

    public function scale(float $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function format(string|Format $format): self
    {
        if (! $format instanceof Format) {
            $format = Format::from($format);
        }

        $this->paperSize(...$format->pageSize());

        return $this;
    }

    public function paperSize(float $width, float $height, Unit|string $unit = 'in'): self
    {
        $unit = $this->getUnitValue($unit);

        $this->paperSize = compact(
            'width',
            'height',
            'unit',
        );

        return $this;
    }

    protected function getUnitValue(Unit|string $unit): string
    {
        if (! $unit instanceof Unit) {
            $unit = Unit::from($unit);
        }

        return $unit->value;
    }

    public function customize(callable $callback): self
    {
        $this->customizeRequest = $callback;

        return $this;
    }

    public function onLambda(): self
    {
        $this->onLambda = true;

        return $this;
    }

    /**
     * Generate the PDF file and save it to the provided path.
     *
     * If a disk name has been previously provided, the path will
     * be relative to the Laravel Filesystem configuration for that disk.
     * Otherwise, the path will be on the local system disk.
     *
     * IMPORTANT: if saving locally, you are responsible for making sure the
     * provided file path is safe. DO NOT TRUST USER INPUT for the file path!
     */
    public function save(string $path): self
    {
        if ($this->diskName) {
            return $this->saveOnDisk($this->diskName, $path);
        }

        $this->doRequest($path);

        return $this;
    }

    public function disk(string $diskName, string $visibility = 'private'): self
    {
        $this->diskName = $diskName;
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Build the PDF and save the result on the provided Laravel Filesystem at the provided path
     */
    protected function saveOnDisk(string $diskName, string $path): self
    {
        $content = $this->doRequest()->getBody();

        $visibility = $this->visibility;

        Storage::disk($diskName)->put($path, $content, $visibility);

        return $this;
    }

    protected function getHtml(): string
    {
        if ($this->viewName) {
            $this->html = view($this->viewName, $this->viewData)->render();
        }

        if ($this->html) {
            return $this->html;
        }

        return '&nbsp';
    }

    protected function getHeaderHtml(): ?string
    {
        if ($this->headerViewName) {
            $this->headerHtml = view($this->headerViewName, $this->headerData)->render();
        }

        if ($this->headerHtml) {
            return $this->headerHtml;
        }

        return null;
    }

    protected function getFooterHtml(): ?string
    {
        if ($this->footerViewName) {
            $this->footerHtml = view($this->footerViewName, $this->footerData)->render();
        }

        if ($this->footerHtml) {
            return $this->footerHtml;
        }

        return null;
    }

    protected function getAllHtml(): string
    {
        return implode(PHP_EOL, [
            $this->getHeaderHtml(),
            $this->getHtml(),
            $this->getFooterHtml(),
        ]);
    }

    public function doRequest(?string $localSavePath = null): Response
    {
        $request = Http::baseUrl(config('gotenberg.host'));

        $postData = [
            'printBackground' => true,
        ];

        if ($headerHtml = $this->getHeaderHtml()) {
            $request->attach('header', $headerHtml, 'header.html');
        }

        if ($footerHtml = $this->getFooterHtml()) {
            $request->attach('footer', $footerHtml, 'footer.html');
        }

        if ($this->margins) {
            $postData['marginTop'] = $this->margins['top'].$this->margins['unit'];
            $postData['marginBottom'] = $this->margins['bottom'].$this->margins['unit'];
            $postData['marginLeft'] = $this->margins['left'].$this->margins['unit'];
            $postData['marginRight'] = $this->margins['right'].$this->margins['unit'];
        }

        if ($this->paperSize) {
            $postData['paperWidth'] = $this->paperSize['width'].$this->paperSize['unit'];
            $postData['paperHeight'] = $this->paperSize['height'].$this->paperSize['unit'];
        }

        $postData['landscape'] = $this->orientation === Orientation::Landscape->value;
        if ($this->scale) {
            $postData['scale'] = $this->scale;
        }

        $request->attach('index', $this->getHtml(), 'index.html');

        if ($this->customizeRequest) {
            ($this->customizeRequest)($request);
        }

        // Use the HTTP Client's built-in file-saving, if saving to the local filesystem is desired
        if ($localSavePath) {
            $request->sink($localSavePath);
        }

        return $request->post(static::ENDPOINT_URL, $postData);
    }

    public function toResponse($request): StreamedResponse
    {
        $stream = $this->doRequest()->getBody();

        // Partially based on https://github.com/laravel/framework/discussions/49991
        return response()->streamDownload(
            function () use ($stream) {
                while (! $stream->eof()) {
                    echo $stream->read(1024 * 512); // 0.5 MiB chunks
                }
                $stream->close();
            },
            $this->downloadName,
            $this->responseHeaders,
            $this->disposition,
        );
    }

    protected function addHeaders(array $headers): self
    {
        $this->responseHeaders = array_merge($this->responseHeaders, $headers);

        return $this;
    }

    public function contains(string|array $text): bool
    {
        if (is_string($text)) {
            $text = [$text];
        }

        $html = $this->getAllHtml();

        foreach ($text as $singleText) {
            if (str_contains($html, $singleText)) {
                return true;
            }
        }

        return false;
    }
}
