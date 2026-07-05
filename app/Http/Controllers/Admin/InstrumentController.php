<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instrument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstrumentController extends Controller
{
    public function edit(Instrument $instrument): View
    {
        $scoringConfig = $instrument->scoring_config ?? [];
        $surveyConfig = config("portal.baseline_surveys.{$instrument->slug}");
        $items = $this->resolveItems($instrument);
        $itemAttributes = $this->resolveItemAttributes($scoringConfig);

        $responseLabels = $scoringConfig['response_labels'] ?? [];
        if ($responseLabels === [] && is_array($surveyConfig)) {
            $labelsKey = $surveyConfig['labels_key'] ?? null;
            if (is_string($labelsKey)) {
                $responseLabels = config("portal.{$labelsKey}", []);
            }
        }
        if ($responseLabels === []) {
            $responseLabels = [0 => 'No', 1 => 'Yes'];
        }

        return view('dashboards.instrument-edit', [
            'instrument' => $instrument,
            'items' => $items,
            'itemAttributes' => $itemAttributes,
            'responseLabels' => $responseLabels,
            'instructions' => $scoringConfig['instructions']
                ?? ($surveyConfig['instructions'] ?? 'Select one answer per question.'),
            'description' => $scoringConfig['description']
                ?? ($surveyConfig['description'] ?? ''),
        ]);
    }

    public function update(Request $request, Instrument $instrument): RedirectResponse
    {
        $scoringConfig = $instrument->scoring_config ?? [];
        $itemAttributes = $this->resolveItemAttributes($scoringConfig);

        $rules = [
            'instructions' => ['required', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'items.*.text' => ['required', 'string', 'max:2000'],
            'response_labels' => ['required', 'array', 'min:1'],
            'response_labels.*.value' => ['required', 'integer', 'min:0', 'max:99'],
            'response_labels.*.label' => ['required', 'string', 'max:255'],
        ];

        foreach ($itemAttributes as $attribute) {
            $key = $attribute['key'];
            if (in_array($key, ['id', 'text'], true)) {
                continue;
            }

            if (($attribute['type'] ?? 'string') === 'boolean') {
                $rules["items.*.{$key}"] = ['sometimes', 'boolean'];
            } else {
                $rules["items.*.{$key}"] = ['nullable', 'string', 'max:255'];
            }
        }

        $validated = $request->validate($rules);

        $itemIds = collect($validated['items'])->pluck('id');
        if ($itemIds->unique()->count() !== $itemIds->count()) {
            return back()
                ->withErrors(['items' => 'Each question id must be unique.'])
                ->withInput();
        }

        $items = collect($validated['items'])
            ->map(fn (array $item): array => $this->normalizeItem($item, $itemAttributes))
            ->values()
            ->all();

        $responseLabels = collect($validated['response_labels'])
            ->mapWithKeys(fn (array $row): array => [(string) $row['value'] => trim($row['label'])])
            ->all();

        $scoringConfig['instructions'] = trim($validated['instructions']);
        $scoringConfig['description'] = trim($validated['description'] ?? '');
        $scoringConfig['response_labels'] = $responseLabels;
        $scoringConfig['item_attributes'] = $itemAttributes;

        $instrument->update([
            'items' => $items,
            'scoring_config' => $scoringConfig,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.instruments.edit', $instrument)
            ->with('status', "Questions updated for {$instrument->name}.");
    }

    public function importQuestions(Request $request, Instrument $instrument): RedirectResponse
    {
        $validated = $request->validate([
            'questions_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        try {
            $parsed = $this->parseQuestionsCsv($validated['questions_csv']->getRealPath());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['questions_csv' => $exception->getMessage()]);
        }

        $scoringConfig = $instrument->scoring_config ?? [];
        $scoringConfig['item_attributes'] = $parsed['item_attributes'];

        $instrument->update([
            'items' => $parsed['items'],
            'scoring_config' => $scoringConfig,
        ]);

        $extraFields = collect($parsed['item_attributes'])
            ->pluck('label')
            ->reject(fn (string $label): bool => $label === 'Reverse score')
            ->values()
            ->all();

        $message = count($parsed['items']).' questions imported from CSV.';
        if ($extraFields !== []) {
            $message .= ' Inferred fields: '.implode(', ', $extraFields).'.';
        }
        $message .= ' Review response options and save if needed.';

        return redirect()
            ->route('admin.instruments.edit', $instrument)
            ->with('status', $message);
    }

    public function downloadQuestionsTemplate(Instrument $instrument): StreamedResponse
    {
        $filename = Str::slug($instrument->slug).'-questions-template.csv';
        $items = $this->resolveItems($instrument);
        $itemAttributes = $this->resolveItemAttributes($instrument->scoring_config ?? []);

        return response()->streamDownload(function () use ($items, $itemAttributes): void {
            $handle = fopen('php://output', 'w');

            $headers = collect($itemAttributes)
                ->reject(fn (array $attribute): bool => in_array($attribute['key'], ['id', 'text'], true))
                ->map(fn (array $attribute): string => $this->attributeCsvHeader($attribute))
                ->prepend('text')
                ->prepend('id')
                ->values()
                ->all();

            fputcsv($handle, $headers);

            foreach ($items as $item) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $key = $this->csvHeaderToKey($header, $itemAttributes);
                    if ($key === 'id') {
                        $csvRow[] = $item['id'];
                        continue;
                    }
                    if ($key === 'text') {
                        $csvRow[] = $item['text'];
                        continue;
                    }

                    $attribute = collect($itemAttributes)->firstWhere('key', $key);
                    if (($attribute['type'] ?? 'string') === 'boolean') {
                        $csvRow[] = ! empty($item[$key]) ? 'Yes' : 'No';
                    } else {
                        $csvRow[] = $item[$key] ?? '';
                    }
                }

                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<string, mixed>  $scoringConfig
     * @return array<int, array{key: string, type: string, label: string, standard?: bool}>
     */
    private function resolveItemAttributes(array $scoringConfig): array
    {
        $attributes = $scoringConfig['item_attributes'] ?? null;

        if (is_array($attributes) && $attributes !== []) {
            return $this->ensureStandardAttributes($attributes);
        }

        return $this->defaultItemAttributes();
    }

    /**
     * @return array<int, array{key: string, type: string, label: string, standard?: bool}>
     */
    private function defaultItemAttributes(): array
    {
        return [
            ['key' => 'reverse_score', 'type' => 'boolean', 'label' => 'Reverse score', 'standard' => true],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     * @return array<int, array{key: string, type: string, label: string, standard?: bool}>
     */
    private function ensureStandardAttributes(array $attributes): array
    {
        $normalized = collect($attributes)
            ->map(function (array $attribute): array {
                $key = Str::snake((string) ($attribute['key'] ?? ''));

                return [
                    'key' => $key,
                    'type' => (string) ($attribute['type'] ?? 'string'),
                    'label' => (string) ($attribute['label'] ?? Str::title(str_replace('_', ' ', $key))),
                    'standard' => (bool) ($attribute['standard'] ?? false),
                ];
            })
            ->filter(fn (array $attribute): bool => $attribute['key'] !== '')
            ->unique('key')
            ->values()
            ->all();

        if (! collect($normalized)->contains(fn (array $attribute): bool => $attribute['key'] === 'reverse_score')) {
            array_unshift($normalized, [
                'key' => 'reverse_score',
                'type' => 'boolean',
                'label' => 'Reverse score',
                'standard' => true,
            ]);
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveItems(Instrument $instrument): array
    {
        $surveyConfig = config("portal.baseline_surveys.{$instrument->slug}");
        $items = $instrument->items ?? [];
        $itemAttributes = $this->resolveItemAttributes($instrument->scoring_config ?? []);

        if ($items === [] && is_array($surveyConfig)) {
            $itemsKey = $surveyConfig['items_key'] ?? null;
            if (is_string($itemsKey)) {
                $items = config("portal.{$itemsKey}", []);
            }
        }

        if ($items === []) {
            return [['id' => 'item_1', 'text' => '', 'reverse_score' => false]];
        }

        return collect($items)
            ->map(fn (array $item): array => $this->normalizeItem($item, $itemAttributes))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, array{key: string, type: string, label: string, standard?: bool}>  $itemAttributes
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item, array $itemAttributes): array
    {
        $normalized = [
            'id' => $this->sanitizeItemIdComponent((string) ($item['id'] ?? '')),
            'text' => trim((string) ($item['text'] ?? '')),
        ];

        foreach ($itemAttributes as $attribute) {
            $key = $attribute['key'];
            if (in_array($key, ['id', 'text'], true)) {
                continue;
            }

            if (($attribute['type'] ?? 'string') === 'boolean') {
                $normalized[$key] = filter_var($item[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
            } else {
                $normalized[$key] = trim((string) ($item[$key] ?? ''));
            }
        }

        return $normalized;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, item_attributes: array<int, array{key: string, type: string, label: string, standard?: bool}>}
     */
    private function parseQuestionsCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \InvalidArgumentException('Could not read the CSV file.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        if ($rows === []) {
            throw new \InvalidArgumentException('The CSV file is empty.');
        }

        $headerRow = $rows[0];
        $hasHeader = $this->looksLikeHeaderRow($headerRow);
        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if ($hasHeader) {
            $columnMap = $this->buildColumnMap($headerRow);
        } else {
            $columnMap = [
                'id' => 0,
                'text' => 1,
                'extra' => [],
            ];
        }

        if (! isset($columnMap['id'], $columnMap['text'])) {
            throw new \InvalidArgumentException('CSV must include id and question/text columns.');
        }

        $itemAttributes = $this->buildItemAttributesFromColumns($columnMap);
        $items = [];

        foreach ($dataRows as $lineNumber => $row) {
            $line = $hasHeader ? $lineNumber + 2 : $lineNumber + 1;
            $id = trim($row[$columnMap['id']] ?? '');
            $text = trim($row[$columnMap['text']] ?? '');

            if ($id === '' && $text === '') {
                continue;
            }

            if ($this->isSubScoreRow($id, $text)) {
                continue;
            }

            if ($id === '' || $text === '') {
                throw new \InvalidArgumentException("Row {$line} must include both an item id and question text.");
            }

            $item = [
                'id' => $this->sanitizeItemIdComponent($id),
                'text' => $text,
            ];

            foreach ($columnMap['extra'] as $fieldKey => $columnIndex) {
                $value = trim($row[$columnIndex] ?? '');
                $attribute = collect($itemAttributes)->firstWhere('key', $fieldKey);
                if (($attribute['type'] ?? 'string') === 'boolean') {
                    $item[$fieldKey] = $this->parseBooleanCsvValue($value);
                } else {
                    $item[$fieldKey] = $value;
                }
            }

            if (isset($columnMap['reverse_score'])) {
                $item['reverse_score'] = $this->parseBooleanCsvValue(trim($row[$columnMap['reverse_score']] ?? ''));
            } elseif (! isset($item['reverse_score'])) {
                $item['reverse_score'] = false;
            }

            $item['id'] = $this->buildUniqueItemId($item);

            if (! preg_match('/^[a-z0-9_]+$/', $item['id'])) {
                throw new \InvalidArgumentException("Row {$line} has an invalid id '{$id}'. Use letters, numbers, and underscores.");
            }

            $items[] = $this->normalizeItem($item, $itemAttributes);
        }

        if ($items === []) {
            throw new \InvalidArgumentException('No questions found in the CSV file.');
        }

        $ids = collect($items)->pluck('id');
        if ($ids->unique()->count() !== $ids->count()) {
            throw new \InvalidArgumentException('Duplicate question ids found in the CSV file.');
        }

        return [
            'items' => $items,
            'item_attributes' => $itemAttributes,
        ];
    }

    /**
     * @param  array<int, string>  $row
     */
    private function looksLikeHeaderRow(array $row): bool
    {
        $normalized = array_map(fn (string $value): string => $this->normalizeHeader($value), $row);

        return in_array('id', $normalized, true)
            || in_array('item_id', $normalized, true)
            || in_array('question_id', $normalized, true)
            || in_array('question', $normalized, true)
            || in_array('text', $normalized, true);
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array{id?: int, text?: int, extra: array<string, int>}
     */
    private function buildColumnMap(array $headerRow): array
    {
        $map = ['extra' => []];

        foreach ($headerRow as $index => $header) {
            $field = $this->mapHeaderToField($this->normalizeHeader($header));
            if ($field === null) {
                continue;
            }

            if (in_array($field, ['id', 'text', 'reverse_score'], true)) {
                $map[$field] = $index;
            } else {
                $map['extra'][$field] = $index;
            }
        }

        if (! isset($map['id'])) {
            foreach ($headerRow as $index => $header) {
                if ($this->normalizeHeader($header) === 'item_id') {
                    $map['id'] = $index;
                }
            }
        }

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::snake(strtolower(trim($header)));
    }

    private function mapHeaderToField(string $normalizedHeader): ?string
    {
        static $idHeaders = ['id', 'item_id', 'question_id'];
        static $textHeaders = ['text', 'question', 'question_text'];
        static $reverseHeaders = ['reverse_scored', 'reverse_score', 'reverse'];
        static $skipHeaders = ['response', 'answer'];

        if (in_array($normalizedHeader, $idHeaders, true)) {
            return 'id';
        }

        if (in_array($normalizedHeader, $textHeaders, true)) {
            return 'text';
        }

        if (in_array($normalizedHeader, $reverseHeaders, true)) {
            return 'reverse_score';
        }

        if (in_array($normalizedHeader, $skipHeaders, true)) {
            return null;
        }

        return $normalizedHeader;
    }

    /**
     * @param  array{id?: int, text?: int, extra: array<string, int>, reverse_score?: int}  $columnMap
     * @return array<int, array{key: string, type: string, label: string, standard?: bool}>
     */
    private function buildItemAttributesFromColumns(array $columnMap): array
    {
        $attributes = $this->defaultItemAttributes();

        foreach (array_keys($columnMap['extra']) as $fieldKey) {
            if ($fieldKey === 'reverse_score') {
                continue;
            }

            $attributes[] = [
                'key' => $fieldKey,
                'type' => 'string',
                'label' => Str::title(str_replace('_', ' ', $fieldKey)),
            ];
        }

        return $this->ensureStandardAttributes($attributes);
    }

    private function parseBooleanCsvValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'yes', 'y', 'true', 't'], true);
    }

    private function isSubScoreRow(string $id, string $text): bool
    {
        $haystack = strtolower($id.' '.$text);

        return str_contains($haystack, 'sub score') || str_contains($haystack, 'subscore');
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function buildUniqueItemId(array $item): string
    {
        $id = (string) $item['id'];

        foreach (['target', 'section', 'group', 'subscale'] as $prefixKey) {
            if (! empty($item[$prefixKey])) {
                return $this->sanitizeItemIdComponent($item[$prefixKey].'_'.$id);
            }
        }

        return $id;
    }

    private function sanitizeItemIdComponent(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    /**
     * @param  array{key: string, type: string, label: string}  $attribute
     */
    private function attributeCsvHeader(array $attribute): string
    {
        if ($attribute['key'] === 'reverse_score') {
            return 'Reverse Scored';
        }

        return Str::title(str_replace('_', ' ', $attribute['key']));
    }

    /**
     * @param  array<int, array{key: string, type: string, label: string}>  $itemAttributes
     */
    private function csvHeaderToKey(string $header, array $itemAttributes): string
    {
        $normalized = $this->normalizeHeader($header);
        $mapped = $this->mapHeaderToField($normalized);

        if ($mapped !== null && $mapped !== 'id' && $mapped !== 'text') {
            return $mapped;
        }

        foreach ($itemAttributes as $attribute) {
            if ($this->normalizeHeader($this->attributeCsvHeader($attribute)) === $normalized) {
                return $attribute['key'];
            }
        }

        return $normalized;
    }
}
