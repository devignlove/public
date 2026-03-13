/**
 * Metabox JavaScript for OZI Theme
 *
 * Handles featured media picker, video picker, and advanced info repeater functionality.
 */

(function ($) {
    'use strict';

    // ========================================
    // Featured Media Picker
    // ========================================
    $('#ozi-fm-choose').on('click', function (e) {
        e.preventDefault();
        const frame = wp.media({
            title: wp.i18n.__('Sélectionner une image ou une vidéo', 'ozitheme'),
            library: { type: ['image', 'video'] },
            multiple: false,
            button: { text: wp.i18n.__('Utiliser ce média', 'ozitheme') }
        });

        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            $('#_thumbnail_id').val(att.id);
            const $p = $('#ozi-fm-preview').empty();

            if (att.type === 'video') {
                $('<video/>', {
                    src: att.url,
                    muted: true,
                    playsinline: true,
                    controls: true
                }).css({
                    width: '100%', height: '100%', objectFit: 'contain'
                }).appendTo($p);
            } else {
                $('<img/>', {
                    src: att.url,
                    alt: ''
                }).css({
                    width: '100%', height: '100%', objectFit: 'contain'
                }).appendTo($p);
            }
        });

        frame.open();
    });

    $('#ozi-fm-clear').on('click', function (e) {
        e.preventDefault();
        $('#_thumbnail_id').val('');
        $('#ozi-fm-preview').html('<em>' + wp.i18n.__('Aucun média sélectionné', 'ozitheme') + '</em>');
    });

    // ========================================
    // Video Picker (MP4 / WebM)
    // ========================================
    $(document).on('click', '.ozi-pick-video', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const frame = wp.media({
            title: wp.i18n.__('Choisir une vidéo', 'ozitheme'),
            library: { type: 'video' },
            multiple: false,
            button: { text: wp.i18n.__('Utiliser cette vidéo', 'ozitheme') }
        });

        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            $('#' + target).val(att.id);
            const label = $('button[data-target="' + target + '"]').closest('div').find('small');
            label.text(att.url || '—');
        });

        frame.open();
    });

    $(document).on('click', '.ozi-clear-video', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        $('#' + target).val('');
        const label = $('button[data-target="' + target + '"]').closest('div').find('small');
        label.text('—');
    });

    // ========================================
    // Background / Image Picker
    // ========================================
    $(document).on('click', '.ozi-pick-bg', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const prev = $(this).data('prev');
        const urlEl = $(this).data('url');

        const frame = wp.media({
            title: wp.i18n.__('Choisir une image', 'ozitheme'),
            library: { type: 'image' },
            multiple: false,
            button: { text: wp.i18n.__('Utiliser', 'ozitheme') }
        });

        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            $('#' + target).val(att.id);
            $('#' + prev).empty().append($('<img/>', { src: att.url, alt: '' }));
            $('#' + urlEl).text(att.url || '—');
        });

        frame.open();
    });

    $(document).on('click', '.ozi-clear-bg', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const prev = $(this).data('prev');
        const urlEl = $(this).data('url');

        $('#' + target).val('');
        $('#' + prev).empty();
        $('#' + urlEl).text('—');
    });

    // ========================================
    // Generic Image Picker (for infos section)
    // ========================================
    $(document).on('click', '.ozi-pick', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const prev = $(this).data('prev');

        const frame = wp.media({
            title: wp.i18n.__('Choisir une image', 'ozitheme'),
            library: { type: 'image' },
            multiple: false,
            button: { text: wp.i18n.__('Utiliser', 'ozitheme') }
        });

        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            $('#' + target).val(att.id);
            const $p = $('#' + prev).empty();
            $('<img/>', { src: att.url, alt: '' }).appendTo($p);
            $p.next('.ozi-muted').text(att.url || '—');
        });

        frame.open();
    });

    $(document).on('click', '.ozi-clear', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        const prev = $(this).data('prev');

        $('#' + target).val('');
        $('#' + prev).empty();
        $('#' + prev).next('.ozi-muted').text('—');
    });

    // ========================================
    // Repeater Delete
    // ========================================
    $(document).on('click', '.ozi-del', function (e) {
        e.preventDefault();
        $(this).closest('.item').remove();
    });

    // ========================================
    // Repeater Adders
    // ========================================

    function getNextIndex($list) {
        let max = -1;
        $list.children('.item').each(function () {
            const i = parseInt($(this).attr('data-i'), 10);
            if (!isNaN(i) && i > max) max = i;
        });
        return max + 1;
    }

    // Add Infos Section
    $('#ozi-add-infos').on('click', function () {
        const $list = $('#ozi-infos');
        const i = getNextIndex($list);

        const html = `
            <div class="item" data-i="${i}">
                <button type="button" class="button-link-delete ozi-del" data-group="infos">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                <div class="ozi-column">
                    <p><label>${wp.i18n.__('Titre', 'ozitheme')}<br>
                        <input type="text" name="ozi_infos[${i}][title]" value="" style="width:100%"></label></p>
                    <p><label>${wp.i18n.__('Texte', 'ozitheme')}<br>
                        <textarea name="ozi_infos[${i}][text]" rows="2" style="width:100%"></textarea></label></p>
                </div>
                <div class="ozi-column">
                    <div>
                        <div class="ozi-thumb" id="ozi-sec-prev-${i}"></div>
                        <div class="ozi-muted">—</div>
                    </div>
                    <div class="ozi-actions">
                        <button type="button" class="button ozi-pick" data-target="ozi-sec-id-${i}" data-prev="ozi-sec-prev-${i}">${wp.i18n.__('Choisir image', 'ozitheme')}</button>
                        <button type="button" class="button-link-delete ozi-clear" data-target="ozi-sec-id-${i}" data-prev="ozi-sec-prev-${i}">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                        <input type="hidden" id="ozi-sec-id-${i}" name="ozi_infos[${i}][image_id]" value="0">
                    </div>
                </div>
            </div>
        `;

        $list.append(html);
    });

    // Add Tech Characteristic
    $('#ozi-add-tech-car').on('click', function () {
        const $list = $('#ozi-tech-car');
        const i = getNextIndex($list);

        const html = `
            <div class="item" data-i="${i}">
                <button type="button" class="button-link-delete ozi-del" data-group="tech-car">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                <div class="ozi-row">
                    <p><input type="text" name="ozi_tech_carac[${i}][label]" value="" placeholder="${wp.i18n.__('Nom', 'ozitheme')}" style="width:100%"></p>
                    <p><input type="text" name="ozi_tech_carac[${i}][value]" value="" placeholder="${wp.i18n.__('Valeur', 'ozitheme')}" style="width:100%"></p>
                </div>
            </div>
        `;

        $list.append(html);
    });

    // Add Tech Equipment
    $('#ozi-add-tech-eqp').on('click', function () {
        const $list = $('#ozi-tech-eqp');
        const i = getNextIndex($list);

        const html = `
            <div class="item" data-i="${i}">
                <button type="button" class="button-link-delete ozi-del" data-group="tech-eqp">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                <div class="ozi-row">
                    <p><input type="text" name="ozi_tech_equip[${i}][label]" value="" placeholder="${wp.i18n.__('Nom', 'ozitheme')}" style="width:100%"></p>
                    <p><input type="text" name="ozi_tech_equip[${i}][value]" value="" placeholder="${wp.i18n.__('Valeur', 'ozitheme')}" style="width:100%"></p>
                </div>
            </div>
        `;

        $list.append(html);
    });

    // Add Review
    $('#ozi-add-review').on('click', function () {
        const $list = $('#ozi-reviews');
        const i = getNextIndex($list);

        const html = `
            <div class="item" data-i="${i}">
                <button type="button" class="button-link-delete ozi-del" data-group="reviews">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                <div class="ozi-row">
                    <p><input type="text" name="ozi_reviews[${i}][author]" value="" placeholder="${wp.i18n.__('Auteur', 'ozitheme')}" style="width:100%"></p>
                    <p><input type="text" name="ozi_reviews[${i}][text]" value="" placeholder="${wp.i18n.__('Commentaire', 'ozitheme')}" style="width:100%"></p>
                </div>
            </div>
        `;

        $list.append(html);
    });

    // Add FAQ
    $('#ozi-add-faq').on('click', function () {
        const $list = $('#ozi-faq');
        const i = getNextIndex($list);

        const html = `
            <div class="item" data-i="${i}">
                <button type="button" class="button-link-delete ozi-del" data-group="faq">${wp.i18n.__('Supprimer', 'ozitheme')}</button>
                <div class="ozi-row">
                    <p><input type="text" name="ozi_faq[${i}][q]" value="" placeholder="${wp.i18n.__('Question', 'ozitheme')}" style="width:100%"></p>
                    <p><input type="text" name="ozi_faq[${i}][a]" value="" placeholder="${wp.i18n.__('Réponse', 'ozitheme')}" style="width:100%"></p>
                </div>
            </div>
        `;

        $list.append(html);
    });

})(jQuery);
