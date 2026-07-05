<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $instrument->name }}</h2>
                <p class="text-sm text-gray-500">{{ $instrument->version }} - {{ count($items) }} questions</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.instruments.questions.template', $instrument) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">CSV template</a>
                <form method="POST" action="{{ route('admin.instruments.questions.import', $instrument) }}" enctype="multipart/form-data" class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-2 py-1">
                    @csrf
                    <input type="file" name="questions_csv" accept=".csv,text/csv" required class="max-w-[10rem] text-xs text-gray-700">
                    <button type="submit" class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-500">Import CSV</button>
                </form>
                <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
                <button type="submit" form="instrument-form" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save assessment</button>
            </div>
        </div>
    </x-slot>

    @php
        $extraAttributes = collect($itemAttributes)->reject(fn ($attribute) => in_array($attribute['key'], ['id', 'text'], true));
    @endphp

    <div class="py-4 px-4 sm:px-6 lg:px-8 max-w-[1600px] mx-auto w-full">
        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.instruments.update', $instrument) }}" id="instrument-form" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="bg-white shadow-sm rounded-lg p-4 grid gap-4 lg:grid-cols-3">
                <div>
                    <label for="description" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Description</label>
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm">{{ old('description', $description) }}</textarea>
                </div>
                <div>
                    <label for="instructions" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Instructions</label>
                    <textarea id="instructions" name="instructions" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">{{ old('instructions', $instructions) }}</textarea>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $instrument->is_active)) class="rounded border-gray-300 text-indigo-600">
                        Active on participant dashboards
                    </label>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Answer scale</h3>
                    <button type="button" id="add-label" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">Add score</button>
                </div>
                <div class="overflow-x-auto">
                    <div id="label-rows" class="flex min-w-full gap-2">
                        @php
                            $oldLabels = old('response_labels');
                            $labelRows = is_array($oldLabels)
                                ? $oldLabels
                                : collect($responseLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();
                        @endphp
                        @foreach ($labelRows as $index => $row)
                            <div class="label-row flex-shrink-0 flex-1 min-w-[9rem] max-w-[12rem] rounded border border-gray-200 bg-gray-50 px-2 py-2">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <input type="number" name="response_labels[{{ $index }}][value]" value="{{ $row['value'] }}" min="0" max="99" required class="w-12 rounded border-gray-300 text-xs font-semibold text-center" title="Score value">
                                    <button type="button" class="remove-label text-xs text-red-600 hover:text-red-700">x</button>
                                </div>
                                <input type="text" name="response_labels[{{ $index }}][label]" value="{{ $row['label'] }}" required class="block w-full rounded border-gray-300 text-xs" placeholder="Label">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="font-semibold text-gray-900">Questions</h3>
                        <p class="text-xs text-gray-500">Attributes stay compact on the left; question text uses the rest of the row.</p>
                    </div>
                    <button type="button" id="add-item" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Add question</button>
                </div>

                <div class="overflow-x-auto">
                    <div class="min-w-[40rem]">
                        <div class="hidden md:flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <div class="w-7 shrink-0 text-center">#</div>
                            <div class="w-[7.5rem] shrink-0">ID</div>
                            @if ($extraAttributes->isNotEmpty())
                                <div class="flex shrink-0 items-center gap-2">
                                    @foreach ($extraAttributes as $attribute)
                                        <div class="{{ ($attribute['type'] ?? 'string') === 'boolean' ? 'w-14 text-center' : 'w-[6.5rem]' }}">
                                            {{ $attribute['key'] === 'reverse_score' ? 'Rev' : Str::limit($attribute['label'], 10) }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">Question</div>
                            <div class="w-14 shrink-0 text-center"></div>
                        </div>

                        <div id="item-rows" class="divide-y divide-gray-100">
                    @php
                        $oldItems = old('items');
                        $itemRows = is_array($oldItems) ? $oldItems : $items;
                    @endphp
                    @foreach ($itemRows as $index => $item)
                        <div class="item-row flex items-start gap-2 px-3 py-2">
                            <div class="w-7 shrink-0 pt-2 text-center text-xs text-gray-500 item-number">{{ $index + 1 }}</div>
                            <div class="w-[7.5rem] shrink-0">
                                <input type="text" name="items[{{ $index }}][id]" value="{{ $item['id'] }}" required pattern="[a-z0-9_]+" class="block w-full rounded border-gray-300 font-mono text-xs" aria-label="Question ID">
                            </div>
                            @if ($extraAttributes->isNotEmpty())
                                <div class="flex shrink-0 items-start gap-2 pt-1">
                                    @foreach ($extraAttributes as $attribute)
                                        @if (($attribute['type'] ?? 'string') === 'boolean')
                                            <label class="inline-flex w-14 shrink-0 items-center justify-center gap-1 text-[11px] text-gray-600" title="{{ $attribute['label'] }}">
                                                <input type="hidden" name="items[{{ $index }}][{{ $attribute['key'] }}]" value="0">
                                                <input type="checkbox" name="items[{{ $index }}][{{ $attribute['key'] }}]" value="1" @checked(!empty($item[$attribute['key']])) class="rounded border-gray-300 text-indigo-600">
                                                <span class="sr-only">{{ $attribute['label'] }}</span>
                                            </label>
                                        @else
                                            <input type="text" name="items[{{ $index }}][{{ $attribute['key'] }}]" value="{{ $item[$attribute['key']] ?? '' }}" class="w-[6.5rem] shrink-0 rounded border-gray-300 text-xs" aria-label="{{ $attribute['label'] }}" placeholder="{{ $attribute['label'] }}">
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <textarea name="items[{{ $index }}][text]" required rows="1" class="question-input block w-full rounded border-gray-300 text-sm leading-snug resize-y min-h-[2.25rem]" aria-label="Question text">{{ $item['text'] }}</textarea>
                            </div>
                            <div class="w-14 shrink-0 pt-1 text-center">
                                <button type="button" class="remove-item text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <template id="label-row-template">
        <div class="label-row flex-shrink-0 flex-1 min-w-[9rem] max-w-[12rem] rounded border border-gray-200 bg-gray-50 px-2 py-2">
            <div class="flex items-center justify-between gap-1 mb-1">
                <input type="number" data-name="value" min="0" max="99" required class="w-12 rounded border-gray-300 text-xs font-semibold text-center">
                <button type="button" class="remove-label text-xs text-red-600 hover:text-red-700">x</button>
            </div>
            <input type="text" data-name="label" required class="block w-full rounded border-gray-300 text-xs" placeholder="Label">
        </div>
    </template>

    <template id="item-row-template">
        <div class="item-row flex items-start gap-2 px-3 py-2">
            <div class="w-7 shrink-0 pt-2 text-center text-xs text-gray-500 item-number">#</div>
            <div class="w-[7.5rem] shrink-0">
                <input type="text" data-name="id" required pattern="[a-z0-9_]+" class="block w-full rounded border-gray-300 font-mono text-xs" aria-label="Question ID">
            </div>
            @if ($extraAttributes->isNotEmpty())
                <div class="flex shrink-0 items-start gap-2 pt-1">
                    @foreach ($extraAttributes as $attribute)
                        @if (($attribute['type'] ?? 'string') === 'boolean')
                            <label class="inline-flex w-14 shrink-0 items-center justify-center gap-1 text-[11px] text-gray-600" title="{{ $attribute['label'] }}">
                                <input type="hidden" data-name="{{ $attribute['key'] }}" value="0">
                                <input type="checkbox" data-name="{{ $attribute['key'] }}" value="1" class="rounded border-gray-300 text-indigo-600">
                                <span class="sr-only">{{ $attribute['label'] }}</span>
                            </label>
                        @else
                            <input type="text" data-name="{{ $attribute['key'] }}" class="w-[6.5rem] shrink-0 rounded border-gray-300 text-xs" aria-label="{{ $attribute['label'] }}" placeholder="{{ $attribute['label'] }}">
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <textarea data-name="text" required rows="1" class="question-input block w-full rounded border-gray-300 text-sm leading-snug resize-y min-h-[2.25rem]" aria-label="Question text"></textarea>
            </div>
            <div class="w-14 shrink-0 pt-1 text-center">
                <button type="button" class="remove-item text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
            </div>
        </div>
    </template>

    <script>
        function reindexLabels() {
            document.querySelectorAll('#label-rows .label-row').forEach((row, index) => {
                row.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = `response_labels[${index}][${input.dataset.name}]`;
                });
                row.querySelectorAll('input[name^="response_labels["]').forEach((input) => {
                    if (input.dataset.name) return;
                    const match = input.name.match(/\[([^\]]+)\]$/);
                    if (match) input.name = `response_labels[${index}][${match[1]}]`;
                });
            });
        }

        function reindexItems() {
            document.querySelectorAll('#item-rows .item-row').forEach((row, index) => {
                const number = row.querySelector('.item-number');
                if (number) number.textContent = String(index + 1);
                row.querySelectorAll('[data-name]').forEach((input) => {
                    input.name = `items[${index}][${input.dataset.name}]`;
                });
                row.querySelectorAll('[name^="items["]').forEach((input) => {
                    if (input.dataset.name) return;
                    const match = input.name.match(/\[([^\]]+)\]$/);
                    if (match) input.name = `items[${index}][${match[1]}]`;
                });
            });
        }

        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = `${Math.max(textarea.scrollHeight, 36)}px`;
        }

        function bindQuestionInputs(root = document) {
            root.querySelectorAll('.question-input').forEach((textarea) => {
                autoResize(textarea);
                textarea.addEventListener('input', () => autoResize(textarea));
            });
        }

        document.getElementById('add-label')?.addEventListener('click', () => {
            document.getElementById('label-rows').appendChild(document.getElementById('label-row-template').content.cloneNode(true));
            reindexLabels();
        });

        document.getElementById('add-item')?.addEventListener('click', () => {
            const clone = document.getElementById('item-row-template').content.cloneNode(true);
            document.getElementById('item-rows').appendChild(clone);
            reindexItems();
            bindQuestionInputs(document.getElementById('item-rows').lastElementChild);
        });

        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-label')) {
                if (document.querySelectorAll('#label-rows .label-row').length <= 1) return;
                event.target.closest('.label-row')?.remove();
                reindexLabels();
            }
            if (event.target.classList.contains('remove-item')) {
                if (document.querySelectorAll('#item-rows .item-row').length <= 1) return;
                event.target.closest('.item-row')?.remove();
                reindexItems();
            }
        });

        bindQuestionInputs();
    </script>
</x-app-layout>
