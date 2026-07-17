function initSurveyContinuousScales() {
    document.querySelectorAll('[data-survey-continuous-scale]').forEach((root) => {
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

        const clamp = (value) => Math.min(max, Math.max(min, value));

        const syncRangeFromNumber = () => {
            if (number.value === '') {
                range.value = String(min);
                return;
            }

            const parsed = clamp(Number(number.value));
            if (Number.isNaN(parsed)) {
                range.value = String(min);
                return;
            }

            number.value = String(parsed);
            range.value = String(parsed);
        };

        range.addEventListener('input', () => {
            number.value = range.value;
        });

        number.addEventListener('input', syncRangeFromNumber);
        number.addEventListener('change', syncRangeFromNumber);

        clearButton.addEventListener('click', () => {
            number.value = '';
            range.value = String(min);
            number.focus();
        });

        const initial = root.dataset.initial ?? '';
        if (initial !== '') {
            number.value = initial;
            range.value = initial;
        } else {
            range.value = String(min);
        }
    });
}

document.addEventListener('DOMContentLoaded', initSurveyContinuousScales);

export { initSurveyContinuousScales };
