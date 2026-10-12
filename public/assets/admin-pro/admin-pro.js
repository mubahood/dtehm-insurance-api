/* DTEHM admin enhancements. Works with laravel-admin's pjax navigation (all handlers are delegated). */
(function ($) {
  if (!$ || window.__adminPro) return;
  window.__adminPro = true;

  var money = function (n) { return 'UGX ' + Math.round(n).toLocaleString('en-US'); };

  // ---- Confirm before dangerous forms:  <form data-confirm="Sure?">
  $(document).on('submit', 'form[data-confirm]', function (e) {
    if (!window.confirm($(this).data('confirm'))) { e.preventDefault(); e.stopImmediatePropagation(); return false; }
  });

  // ---- Never submit twice (double click / slow network)
  $(document).on('submit', '.ic form, #ic-pay-form', function () {
    var $b = $(this).find('button[type=submit], button:not([type])');
    setTimeout(function () { $b.prop('disabled', true).addClass('disabled'); }, 0);
  });

  // ---- Record payment dialog
  $(document).on('click', '.ic-pay-btn', function () {
    var d = $(this).data();
    var $f = $('#ic-pay-form');
    $f.attr('action', d.url).find('button').prop('disabled', false).removeClass('disabled');
    $('#ic-pay-name').text(d.name || '');
    var presets = [];
    if (d.due > 0) presets.push(['Due now', d.due]);
    if (d.next > 0 && d.next !== d.due) presets.push(['Next month', d.next]);
    if (d.balance > 0 && d.balance !== d.due) presets.push(['Everything', d.balance]);
    $('#ic-pay-presets').html(presets.map(function (p) {
      return '<button type="button" class="btn btn-xs btn-default" data-amount="' + p[1] + '">' + p[0] + ': ' + money(p[1]) + '</button>';
    }).join(''));
    $('#ic-pay-amount').val(d.due > 0 ? d.due : (d.next || '')).attr('max', d.balance || null);
    $('#ic-pay-modal').modal('show');
    setTimeout(function () { $('#ic-pay-amount').focus().select(); }, 300);
  });
  $(document).on('click', '#ic-pay-presets [data-amount]', function () { $('#ic-pay-amount').val($(this).data('amount')).focus(); });
  $(document).on('submit', '#ic-pay-form', function (e) {
    var v = parseFloat($('#ic-pay-amount').val() || '0'), max = parseFloat($('#ic-pay-amount').attr('max') || '0');
    if (!(v > 0)) { e.preventDefault(); alert('Enter the amount received.'); return false; }
    if (max > 0 && v > max) { e.preventDefault(); alert('This member only owes ' + money(max) + '.'); return false; }
    if (!window.confirm('Record ' + money(v) + ' for ' + $('#ic-pay-name').text() + '?')) { e.preventDefault(); return false; }
  });

  // ---- Quick search: Ctrl+K (or Cmd+K) jumps to any admin page
  var items = [], on = 0;
  function collect() {
    var seen = {};
    items = [];
    $('.sidebar-menu a[href]').each(function () {
      var href = $(this).attr('href'), text = $.trim($(this).text()).replace(/\s+/g, ' ');
      if (!href || href === '#' || href.indexOf('javascript') === 0 || !text || seen[href]) return;
      seen[href] = 1;
      var group = $.trim($(this).closest('.treeview').children('a').text()).replace(/\s+/g, ' ');
      items.push({ href: href, text: text, group: group && group !== text ? group : '' });
    });
  }
  function render(q) {
    q = (q || '').toLowerCase();
    var list = items.filter(function (i) { return !q || (i.text + ' ' + i.group).toLowerCase().indexOf(q) >= 0; }).slice(0, 40);
    on = Math.min(on, Math.max(list.length - 1, 0));
    $('#ap-palette ul').html(list.map(function (i, n) {
      return '<li data-href="' + i.href + '" class="' + (n === on ? 'on' : '') + '"><span>' + $('<i>').text(i.text).html() + '</span><small>' + $('<i>').text(i.group).html() + '</small></li>';
    }).join('') || '<li><small>No page matches</small></li>');
  }
  function open() {
    if (!$('#ap-palette').length) {
      $('body').append('<div id="ap-palette"><div class="ap-box"><input type="text" placeholder="Go to page… (e.g. payments, users, insurance)"><ul></ul><div class="ap-hint">↑ ↓ to move · Enter to open · Esc to close</div></div></div>');
    }
    collect(); on = 0;
    $('#ap-palette').show().find('input').val('').focus();
    render('');
  }
  function close() { $('#ap-palette').hide(); }
  function go(href) {
    close();
    if (!href) return;
    if ($.pjax && $('#pjax-container').length) $.pjax({ url: href, container: '#pjax-container' }); else window.location = href;
  }
  $(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); open(); return; }
    if (!$('#ap-palette').is(':visible')) return;
    var n = $('#ap-palette li[data-href]').length;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowDown') { on = Math.min(on + 1, n - 1); render($('#ap-palette input').val()); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { on = Math.max(on - 1, 0); render($('#ap-palette input').val()); e.preventDefault(); }
    else if (e.key === 'Enter') { go($('#ap-palette li.on').data('href')); e.preventDefault(); }
  });
  $(document).on('input', '#ap-palette input', function () { on = 0; render($(this).val()); });
  $(document).on('click', '#ap-palette li[data-href]', function () { go($(this).data('href')); });
  $(document).on('click', '#ap-palette', function (e) { if (e.target.id === 'ap-palette') close(); });
  $(document).on('click', '.ap-launch', function (e) { e.preventDefault(); open(); });

  // Visible hint in the top bar
  $(function () {
    if (!$('.ap-launch').length) {
      $('.navbar-custom-menu > .nav').prepend('<li><a class="ap-launch" title="Quick search (Ctrl+K)"><i class="fa fa-search"></i> <span class="hidden-xs">Search pages</span> <kbd>Ctrl K</kbd></a></li>');
    }
  });
})(window.jQuery);
