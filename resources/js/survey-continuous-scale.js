function initSurveyContinuousScales(scope = document) {
    scope.querySelectorAll('[data-survey-continuous-scale]').forEach((root) => {
        if (root.dataset.initialized === 'true') {
            return;
        }

        root.dataset.initialized = 'true';

        const range = root.querySelector('[data-range]');
        const number = root.querySelector('[data-number]');
        const clearButton = root.querySelector('[data-clear]');

        if (!range || !number || !clearButton) {
            return;
        }

        const min = Number(root.dataset.min ?? range.min ?? 0);
        const max = Number(root.dataset.max ?? range.max ?? 100);
        const step = Number(root.dataset.step ?? range.step ?? 1);

        const clamp = (value) => {
            const snapped = Math.round(value / step) * step;
            return Math.min(max, Math.max(min, snapped));
        };

        const syncNumberFromRange = () => {
            number.value = range.value;
        };

        const syncRangeFromNumber = () => {
            if (number.value === '') {
                range.value = String(min);
                return;
            }

            const parsed = Number(number.value);
            if (Number.isNaN(parsed)) {
                return;
            }

            const clamped = clamp(parsed);
            number.value = String(clamped);
            range.value = String(clamped);
        };

        range.addEventListener('input', syncNumberFromRange);
        range.addEventListener('change', syncNumberFromRange);
        number.addEventListener('input', syncRangeFromNumber);
        number.addEventListener('change', syncRangeFromNumber);

        clearButton.addEventListener('click', () => {
            number.value = '';
            range.value = String(min);
            number.focus();
        });

        const initial = root.dataset.initial ?? '';
        if (initial !== '') {
            const clamped = clamp(Number(initial));
            number.value = String(clamped);
            range.value = String(clamped);
        } else {
            range.value = String(min);
            // Keep number empty until the participant chooses a value.
        }
    });
}

document.addEventListener('DOMContentLoaded', () => initSurveyContinuousScales());

export { initSurveyContinuousScales };
