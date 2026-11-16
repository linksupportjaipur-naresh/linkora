@extends('layouts.dashboard')

@section('title', 'Manage Links')
@section('page_title', 'Manage Links')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profile-page.css') }}?v={{ filemtime(public_path('assets/css/profile-page.css')) }}">
@endpush

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="page-head">
                <div>
                    <h2>Your links</h2>
                    <p>Drag <i class="fa-solid fa-grip-vertical small"></i> to reorder. Switch a link off to hide it without deleting it.</p>
                </div>
                <button type="button" class="btn btn-primary" data-link-add><i class="fa-solid fa-plus me-1"></i>Add link</button>
            </div>

            @unless ($profile->is_published)
                <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
                    <i class="fa-solid fa-eye-slash"></i>
                    <div class="flex-fill small">Your page isn’t published yet, so visitors can’t see these links.</div>
                    <a href="{{ route('dashboard.profile') }}" class="btn btn-sm btn-primary">Publish</a>
                </div>
            @endunless

            <ul class="link-list" id="link-list" aria-label="Your links"></ul>

            <div class="p-card empty-state d-none" id="links-empty">
                <i class="fa-solid fa-link d-block"></i>
                <p class="mb-3">You haven’t added any links yet.</p>
                <button type="button" class="btn btn-primary" data-link-add><i class="fa-solid fa-plus me-1"></i>Add your first link</button>
            </div>
        </div>

        <div class="col-xl-5 d-none d-xl-block">
            @include('dashboard.partials.preview')
        </div>
    </div>

    {{-- Add / edit modal --}}
    <div class="modal fade" id="link-modal" tabindex="-1" aria-labelledby="link-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="link-form" novalidate>
                <div class="modal-header pb-0">
                    <h2 class="modal-title h5" id="link-modal-title">Add link</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="link-id">
                    <div class="mb-3">
                        <label class="form-label" for="link-title">Title</label>
                        <input type="text" class="form-control" id="link-title" name="title" data-emoji maxlength="80" placeholder="e.g. Watch my latest video" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="link-subtitle">Description <span class="text-muted fw-normal small">(optional)</span></label>
                        <input type="text" class="form-control" id="link-subtitle" name="subtitle" data-emoji maxlength="100" placeholder="e.g. Explore more about me">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="link-url">URL</label>
                        <input type="text" class="form-control" id="link-url" name="url" maxlength="2048" placeholder="https://" inputmode="url" autocomplete="off" required>
                    </div>
                    <fieldset>
                        <legend class="form-label fs-6">Icon <span class="text-muted fw-normal small">(optional)</span></legend>
                        <div class="icon-picker" role="radiogroup">
                            <div class="opt">
                                <input type="radio" name="icon" value="" id="icon-none" checked>
                                <label for="icon-none" title="Automatic"><span class="small fw-semibold">Auto</span></label>
                            </div>
                            @foreach (config('linkora_design.link_icons') as $key => [$cls, $label])
                                <div class="opt">
                                    <input type="radio" name="icon" value="{{ $key }}" id="icon-{{ $key }}">
                                    <label for="icon-{{ $key }}" title="{{ $label }}"><i class="{{ $cls }}"></i><span class="visually-hidden">{{ $label }}</span></label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">“Auto” picks an icon for well-known sites like Instagram or YouTube.</div>
                    </fieldset>
                </div>
                <div class="modal-footer pt-0">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="link-save">Save link</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-labelledby="delete-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-2">
                <div class="modal-body">
                    <div class="mx-auto mb-3" style="width:52px;height:52px;border-radius:16px;background:#FDE2E4;color:#B42318;display:grid;place-items:center;font-size:1.3rem"><i class="fa-regular fa-trash-can"></i></div>
                    <h2 class="h5" id="delete-title">Delete this link?</h2>
                    <p class="text-muted small mb-0">“<span id="delete-name"></span>” and its click history will be removed. This can’t be undone.</p>
                </div>
                <div class="modal-footer justify-content-center pt-0">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" id="delete-confirm">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var links = @json($links->map->toDashboardArray()->values());
    var urls = {
        store: @json(route('dashboard.links.store')),
        reorder: @json(route('dashboard.links.reorder')),
        item: @json(url('dashboard/links')) + '/'
    };
    var $list = $('#link-list'), modal = new bootstrap.Modal('#link-modal'), delModal = new bootstrap.Modal('#delete-modal'), deleting = null;

    function esc(s) { return $('<div>').text(s).html(); }

    function row(l) {
        return $(
            '<li class="link-item' + (l.is_active && !l.is_blocked ? '' : ' is-off') + '" data-id="' + l.id + '">' +
              '<button type="button" class="grip" aria-label="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></button>' +
              '<div class="l-ico"><i class="' + esc(l.iconClass) + '"></i></div>' +
              '<div class="l-body"><div class="l-title">' + esc(l.title) + '</div>' + (l.subtitle ? '<div class="l-url text-body">' + esc(l.subtitle) + '</div>' : '') + '<div class="l-url">' + esc(l.url) + '</div>' +
                (l.is_blocked ? '<div class="small text-danger mt-1"><i class="fa-solid fa-ban me-1"></i>Blocked by our team' + (l.blocked_reason ? ': ' + esc(l.blocked_reason) : '') + '</div>' : '') +
              '</div>' +
              '<div class="l-clicks" title="All-time clicks"><i class="fa-solid fa-arrow-pointer me-1"></i>' + l.clicks + '</div>' +
              '<div class="l-actions d-flex align-items-center gap-1">' +
                '<div class="form-check form-switch m-0" title="Show on my page"><input class="form-check-input" type="checkbox" role="switch" data-toggle ' + (l.is_active && !l.is_blocked ? 'checked' : '') + (l.is_blocked ? ' disabled' : '') + ' aria-label="Show “' + esc(l.title) + '” on my page"></div>' +
                '<button type="button" class="icon-btn" data-edit aria-label="Edit “' + esc(l.title) + '”"><i class="fa-solid fa-pen"></i></button>' +
                '<button type="button" class="icon-btn danger" data-delete aria-label="Delete “' + esc(l.title) + '”"><i class="fa-regular fa-trash-can"></i></button>' +
              '</div>' +
            '</li>'
        );
    }

    function render() {
        $list.empty();
        links.forEach(function (l) { $list.append(row(l)); });
        $('#links-empty').toggleClass('d-none', links.length > 0);
    }

    function find(id) { return links.find(function (l) { return l.id === id; }); }
    function idOf(el) { return parseInt($(el).closest('.link-item').data('id'), 10); }

    // Add / edit
    function openForm(l) {
        $('#link-form')[0].reset();
        $('#link-form .is-invalid').removeClass('is-invalid');
        $('#link-form .invalid-feedback').remove();
        $('#link-modal-title').text(l ? 'Edit link' : 'Add link');
        $('#link-id').val(l ? l.id : '');
        $('#link-title').val(l ? l.title : '');
        $('#link-subtitle').val(l && l.subtitle ? l.subtitle : '');
        $('#link-url').val(l ? l.url : '');
        $('#link-form [name=icon][value="' + (l && l.icon ? l.icon : '') + '"]').prop('checked', true);
        modal.show();
        setTimeout(function () { $('#link-title').trigger('focus'); }, 300);
    }
    $(document).on('click', '[data-link-add]', function () { openForm(null); });
    $list.on('click', '[data-edit]', function () { openForm(find(idOf(this))); });

    $('#link-form').on('submit', function (e) {
        e.preventDefault();
        var id = $('#link-id').val(), $btn = $('#link-save');
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving…');

        $.ajax({
            url: id ? urls.item + id : urls.store,
            method: id ? 'PUT' : 'POST',
            data: { title: $('#link-title').val(), subtitle: $('#link-subtitle').val(), url: $('#link-url').val(), icon: $('#link-form [name=icon]:checked').val() }
        }).done(function (r) {
            if (id) { links = links.map(function (l) { return l.id === r.link.id ? r.link : l; }); }
            else { links.unshift(r.link); }
            render(); modal.hide(); lkToast(r.message); reloadPreview();
        }).fail(function (x) {
            if (x.status === 422 && x.responseJSON && x.responseJSON.errors) {
                $.each(x.responseJSON.errors, function (k, msgs) {
                    var $in = $('#link-form [name="' + k + '"]').first();
                    if ($in.attr('type') === 'radio') { lkToast(msgs[0], 'error'); return; }
                    $in.addClass('is-invalid').after($('<div class="invalid-feedback">').text(msgs[0]));
                });
                $('#link-form .is-invalid').first().trigger('focus');
            } else { lkAjaxError(x); }
        }).always(function () { $btn.prop('disabled', false).text('Save link'); });
    });

    // Enable / disable
    $list.on('change', '[data-toggle]', function () {
        var $cb = $(this), id = idOf(this), on = $cb.is(':checked');
        $cb.closest('.link-item').toggleClass('is-off', !on);
        $.ajax({ url: urls.item + id + '/toggle', method: 'PATCH', data: { is_active: on ? 1 : 0 } })
            .done(function (r) { find(id).is_active = on; lkToast(r.message); reloadPreview(); })
            .fail(function (x) { $cb.prop('checked', !on).closest('.link-item').toggleClass('is-off', on); lkAjaxError(x); });
    });

    // Delete
    $list.on('click', '[data-delete]', function () {
        deleting = find(idOf(this));
        $('#delete-name').text(deleting.title);
        delModal.show();
    });
    $('#delete-confirm').on('click', function () {
        var $b = $(this).prop('disabled', true);
        $.ajax({ url: urls.item + deleting.id, method: 'DELETE' })
            .done(function (r) {
                links = links.filter(function (l) { return l.id !== deleting.id; });
                render(); delModal.hide(); lkToast(r.message); reloadPreview();
            })
            .fail(lkAjaxError)
            .always(function () { $b.prop('disabled', false); });
    });

    // Drag and drop
    Sortable.create($list[0], {
        handle: '.grip', animation: 160, ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen',
        onEnd: function (e) {
            if (e.oldIndex === e.newIndex) return;
            var order = $list.children().map(function () { return parseInt($(this).data('id'), 10); }).get();
            links.sort(function (a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
            $.post(urls.reorder, { order: order })
                .done(function (r) { lkToast(r.message); reloadPreview(); })
                .fail(function (x) { lkAjaxError(x); });
        }
    });

    // Keyboard reordering: focus the grip and use the arrow keys
    $list.on('keydown', '.grip', function (e) {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault();
        var $li = $(this).closest('.link-item');
        if (e.key === 'ArrowUp' && $li.prev().length) $li.insertBefore($li.prev());
        else if (e.key === 'ArrowDown' && $li.next().length) $li.insertAfter($li.next());
        else return;
        $(this).trigger('focus');
        var order = $list.children().map(function () { return parseInt($(this).data('id'), 10); }).get();
        links.sort(function (a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
        clearTimeout(window._reorderT);
        window._reorderT = setTimeout(function () { $.post(urls.reorder, { order: order }).done(function (r) { lkToast(r.message); reloadPreview(); }).fail(lkAjaxError); }, 500);
    });

    render();
    if (new URLSearchParams(location.search).get('add') === '1') openForm(null);
})();
</script>
@endpush
