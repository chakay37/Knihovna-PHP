document.addEventListener('click', (event) => {
  if (event.target.closest('[data-print]')) {
    window.print();
  }
});

document.addEventListener('submit', (event) => {
  const message = event.target.dataset.confirm;
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});

document.addEventListener('click', (event) => {
  if (event.target.tagName === 'SELECT') {
    return;
  }

  const select = event.target.closest('.select-wrap')?.querySelector('select');
  if (select?.showPicker) {
    select.showPicker();
  }
});

document.addEventListener('change', (event) => {
  const form = event.target.closest('.sort-form');
  if (!form) {
    return;
  }

  if (event.target.id === 'sort-field') {
    form.querySelector('#sort-dir-input').value = event.target.selectedOptions[0].dataset.dir;
  }

  form.submit();
});

document.addEventListener('click', (event) => {
  const clear = event.target.closest('[data-clear-search]');
  if (!clear) {
    return;
  }

  const field = clear.closest('.search-field-wrap').querySelector('.search-field');
  field.value = '';
  clear.closest('form').submit();
});
