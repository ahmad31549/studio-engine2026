<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PdfGeneratorController extends Controller
{
    private const DEFAULT_WELCOME_TITLE = 'Your Files are Ready!';
    private const DEFAULT_WELCOME_MESSAGE = "Thank you for your purchase! We've put a lot of love into these assets. If you need any help, just message us on Etsy.";
    private const DEFAULT_DOWNLOAD_DESC = 'Instant Access | Digital Download | No Expiry';
    private const ARTIST_DOWNLOAD_DESC = 'Artist-crafted .brushset file | Instant digital delivery | Ready for Procreate on iPad';

    public function index()
    {
        $records = $this->getRecords();

        return view('pdf-records', compact('records'));
    }

    public function dashboard()
    {
        $records = $this->getRecords();

        return view('dashboard', compact('records'));
    }

    public function create(Request $request)
    {
        $preset = $request->query('preset');
        $record = null;

        if ($preset === 'drdoom') {
            $record = (object) [
                'id' => null,
                'store_name' => 'DrDOOMARTS',
                'store_link' => 'etsy.com/shop/DrDOOMARTS',
                'created_by' => 'Independent Artist',
                'title' => 'DrDOOMARTS-Procreate-Guide',
                'theme' => 'black-style',
                'pdf_mode' => 'dark',
                'welcome_title' => 'Your Procreate Brush Library Is Ready',
                'welcome_msg' => 'Thank you for supporting independent art. Every brush in this set was created, tested, and packed by a working digital artist for a smooth Procreate experience on iPad.',
                'products' => [[
                    'name' => 'Procreate Brushset Download',
                    'link' => '',
                    'type' => $this->iconCatalog()['fa-paint-brush'],
                    'desc' => self::ARTIST_DOWNLOAD_DESC,
                ]],
                'step1' => 'Tap the download button below and save your .brushset or ZIP file to the Files app on your iPad.',
                'step2' => 'Open Files, find the download, and tap it once. Procreate will launch and begin importing automatically.',
                'step3' => 'Open your Brush Library in Procreate and start creating. If you need help, send me a message on Etsy and I will guide you.',
            ];
        } elseif ($preset === 'thor') {
            $record = (object) [
                'id' => null,
                'store_name' => 'ThorPresets',
                'store_link' => 'etsy.com/shop/ThorPresets',
                'title' => 'ThorPresets-Download-Card',
                'theme' => 'gold-style',
            ];
        }

        $defaults = $this->getRecords()->first();

        return view('pdf-generator', ['record' => $record, 'defaults' => $defaults]);
    }

    public function edit($id)
    {
        $record = $this->getRecordById($id);
        if (!$record) {
            abort(404);
        }

        return view('pdf-generator', compact('record'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:200',
            'products' => 'required|array|min:1',
        ]);

        $data = $this->normalizeRecordData([
            'user_id' => Auth::id(),
            'title' => $request->input('title', $request->input('store_name') . ' - PDF'),
            'store_name' => $request->store_name,
            'store_link' => $request->store_link,
            'created_by' => $request->input('created_by'),
            'welcome_title' => $request->input('welcome_title', self::DEFAULT_WELCOME_TITLE),
            'welcome_msg' => $request->input('welcome_msg', self::DEFAULT_WELCOME_MESSAGE),
            'theme' => $request->input('theme', 'gd'),
            'pdf_mode' => $request->input('pdf_mode', 'light'),
            'products' => $request->input('products', []),
            'message' => $request->input('message'),
            'step1' => $request->input('step1'),
            'step2' => $request->input('step2'),
            'step3' => $request->input('step3'),
            'show_review' => $request->boolean('show_review', true),
            'review_text' => $request->input('review_text', 'Your feedback helps our small shop grow!'),
        ]);

        if ($request->filled('id')) {
            $id = (string) $request->input('id');
            $record = $this->getRecordById($id);
            if (!$record) {
                abort(404);
            }

            $data['id'] = $id;
            $data['updated_at'] = now()->toDateTimeString();
            $data['created_at'] = $record->created_at ?? $data['updated_at'];

            $this->saveRecord($id, $data);
            $msg = 'Record updated.';
        } else {
            $id = (string) Str::uuid();
            $data['id'] = $id;
            $data['created_at'] = now()->toDateTimeString();
            $data['updated_at'] = $data['created_at'];

            $this->saveRecord($id, $data);
            $msg = 'PDF record saved.';
        }

        return response()->json(['success' => true, 'id' => $id, 'message' => $msg]);
    }

    public function preview($id)
    {
        $record = $this->getRecordById($id);
        if (!$record) {
            abort(404);
        }

        return view('pdf-preview', compact('record'));
    }

    public function destroy($id)
    {
        $this->deleteRecord($id);

        return redirect()->route('pdf.index')->with('success', 'Record deleted.');
    }

    private function getRecordsPath(): string
    {
        $path = storage_path('app/pdf_records/' . Auth::id());
        if (!file_exists($path)) {
            @mkdir($path, 0755, true);
        }

        return $path;
    }

    private function getRecords()
    {
        $path = $this->getRecordsPath();
        $files = glob($path . '/*.json');
        $records = [];

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $data = $this->normalizeRecordData($data);
                $data['id'] = basename($file, '.json');
                $records[] = (object) $data;
            }
        }

        usort($records, function ($a, $b) {
            return strtotime($b->created_at ?? '0') <=> strtotime($a->created_at ?? '0');
        });

        return collect($records);
    }

    private function getRecordById($id)
    {
        $path = $this->getRecordsPath() . '/' . $id . '.json';
        if (!file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);
        if ($data) {
            $data = $this->normalizeRecordData($data);
            $data['id'] = $id;

            return (object) $data;
        }

        return null;
    }

    private function saveRecord($id, $data): void
    {
        $path = $this->getRecordsPath() . '/' . $id . '.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function deleteRecord($id): void
    {
        $path = $this->getRecordsPath() . '/' . $id . '.json';
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private function iconCatalog(): array
    {
        return [
            'fa-palette' => '<i class="fa-solid fa-palette" aria-hidden="true"></i>',
            'fa-clapperboard' => '<i class="fa-solid fa-clapperboard" aria-hidden="true"></i>',
            'fa-mountain-sun' => '<i class="fa-solid fa-mountain-sun" aria-hidden="true"></i>',
            'fa-camera' => '<i class="fa-solid fa-camera" aria-hidden="true"></i>',
            'fa-image' => '<i class="fa-solid fa-image" aria-hidden="true"></i>',
            'fa-music' => '<i class="fa-solid fa-music" aria-hidden="true"></i>',
            'fa-ruler-combined' => '<i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>',
            'fa-paint-brush' => '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>',
            'fa-lightbulb' => '<i class="fa-solid fa-paint-brush" aria-hidden="true"></i>',
            'fa-fire' => '<i class="fa-solid fa-fire" aria-hidden="true"></i>',
            'fa-star' => '<i class="fa-solid fa-star" aria-hidden="true"></i>',
        ];
    }

    private function defaultProductIcon(): string
    {
        return $this->iconCatalog()['fa-palette'];
    }

    private function normalizeRecordData(array $data): array
    {
        $data['title'] = $this->normalizePlainText($data['title'] ?? null, $data['title'] ?? null);
        $data['store_name'] = $this->normalizePlainText($data['store_name'] ?? null, $data['store_name'] ?? null);
        $data['store_link'] = isset($data['store_link']) ? trim((string) $data['store_link']) : null;
        $data['created_by'] = $this->normalizePlainText($data['created_by'] ?? null);
        $data['welcome_title'] = $this->normalizeWelcomeTitle($data['welcome_title'] ?? null);
        $data['welcome_msg'] = $this->normalizePlainText($data['welcome_msg'] ?? null, self::DEFAULT_WELCOME_MESSAGE);
        $data['message'] = $this->normalizePlainText($data['message'] ?? null);
        $data['step1'] = $this->normalizePlainText($data['step1'] ?? null);
        $data['step2'] = $this->normalizePlainText($data['step2'] ?? null);
        $data['step3'] = $this->normalizePlainText($data['step3'] ?? null);
        $data['products'] = $this->normalizeProducts($data['products'] ?? []);

        return $data;
    }

    private function normalizeProducts(mixed $products): array
    {
        if (!is_array($products)) {
            return [];
        }

        return array_values(array_map(function ($product) {
            $product = is_array($product) ? $product : [];
            $desc = $this->normalizePlainText($product['desc'] ?? null, '') ?? '';

            if ($desc !== '' && preg_match('/[^\x20-\x7E]/', $desc)) {
                $lower = strtolower($desc);

                if (str_contains($lower, 'procreate') || str_contains($lower, 'brushset')) {
                    $desc = self::ARTIST_DOWNLOAD_DESC;
                } elseif (str_contains($lower, 'instant access')) {
                    $desc = self::DEFAULT_DOWNLOAD_DESC;
                }
            }

            return [
                'name' => $this->normalizePlainText($product['name'] ?? null, '') ?? '',
                'link' => isset($product['link']) ? trim((string) $product['link']) : '',
                'type' => $this->normalizeProductIcon($product['type'] ?? null),
                'desc' => $desc,
            ];
        }, $products));
    }

    private function normalizeWelcomeTitle(?string $value): string
    {
        $title = $this->normalizePlainText($value, self::DEFAULT_WELCOME_TITLE) ?? self::DEFAULT_WELCOME_TITLE;

        if (preg_match('/[^\x20-\x7E]/', $title) && str_contains($title, 'Your Files are Ready!')) {
            return self::DEFAULT_WELCOME_TITLE;
        }

        return $title;
    }

    private function normalizePlainText(?string $value, ?string $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return $fallback;
        }

        $text = preg_replace('/\s+/', ' ', $text);

        return $text === '' ? $fallback : $text;
    }

    private function normalizeProductIcon(?string $value): string
    {
        $catalog = $this->iconCatalog();
        $iconName = $this->extractIconName($value);

        if ($iconName !== '' && isset($catalog[$iconName])) {
            return $catalog[$iconName];
        }

        return $this->defaultProductIcon();
    }

    private function extractIconName(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (!preg_match_all('/fa-[a-z0-9-]+/i', $raw, $matches)) {
            return '';
        }

        $ignored = ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-sharp'];

        foreach ($matches[0] as $match) {
            $iconName = strtolower($match);
            if (in_array($iconName, $ignored, true)) {
                continue;
            }

            if ($iconName === 'fa-sparkles') {
                return 'fa-star';
            }

            if ($iconName === 'fa-lightbulb') {
                return 'fa-paint-brush';
            }

            return $iconName;
        }

        return '';
    }
}
