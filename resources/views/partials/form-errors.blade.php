{{--
    Form errors in the system's own style (instead of the browser's pop-up bubble with an icon):
    the field gets a red border and a plain red message appears under it. Clears as soon as the field is changed.
    Included once in the main layout and on the login page; works for every form automatically.
--}}
<style>
    .field-invalid { border-color: #dc2626 !important; box-shadow: 0 0 0 1px #dc2626 !important; }
    .field-error-msg { color: #dc2626; font-size: 0.8125rem; margin-top: 0.25rem; }
</style>
<script>
(function () {
    function message(el) {
        const v = el.validity;
        const isYear = el.name === 'publication_year';
        if (v.valueMissing) {
            if (el.tagName === 'SELECT') return 'Please choose an option.';
            if (el.type === 'file') return 'Please choose a file.';
            if (el.type === 'checkbox' || el.type === 'radio') return 'Please check this option.';
            return 'This field is required.';
        }
        if (v.rangeOverflow) return isYear ? 'The publication year cannot be later than ' + el.max + '.' : 'The value must be ' + el.max + ' or less.';
        if (v.rangeUnderflow && el.dataset.minMessage) return el.dataset.minMessage;
        if (v.rangeUnderflow && el.type === 'date') return 'This date cannot be in the past.';
        if (v.rangeUnderflow) return isYear ? 'The publication year must be ' + el.min + ' or later.' : 'The value must be ' + el.min + ' or more.';
        if (v.typeMismatch && el.type === 'url') return 'Please enter a valid link that starts with http:// or https://.';
        if (v.typeMismatch && el.type === 'email') return 'Please enter a valid email address.';
        if (v.tooLong) return 'Please use ' + el.maxLength + ' characters or fewer.';
        if (v.tooShort) return 'Please use at least ' + el.minLength + ' characters.';
        if (v.badInput || v.stepMismatch) return 'Please enter a valid number.';
        if (v.patternMismatch) return el.title || 'Please follow the requested format.';
        return el.validationMessage;
    }

    // Where the message goes: under the field (or under its box when the field itself is hidden or inside a label)
    function anchor(el) {
        if (el.type === 'checkbox' || el.type === 'radio') return el.closest('label') || el;
        return el.offsetParent === null ? el.parentElement : el;
    }

    function clear(el) {
        el.classList.remove('field-invalid');
        const msg = anchor(el).nextElementSibling;
        if (msg && msg.classList.contains('field-error-msg')) msg.remove();
    }

    let firstInvalid = null;
    document.addEventListener('invalid', function (e) {
        const el = e.target;
        e.preventDefault();                  // no browser bubble
        clear(el);
        el.classList.add('field-invalid');
        const msg = document.createElement('p');
        msg.className = 'field-error-msg';
        msg.textContent = message(el);
        anchor(el).insertAdjacentElement('afterend', msg);

        // bring the first wrong field into view once per submit
        if (!firstInvalid) {
            firstInvalid = el;
            setTimeout(() => {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus({ preventScroll: true });
                firstInvalid = null;
            }, 0);
        }
    }, true);

    // the red border and message go away as soon as the user fixes the field
    ['input', 'change'].forEach(type => document.addEventListener(type, function (e) {
        const el = e.target;
        if (el.classList && el.classList.contains('field-invalid')) clear(el);
    }, true));
})();
</script>
