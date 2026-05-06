(function($) {
    'use strict';

    $(function() {
        const $container = $('#cnes-layout-container');
        if (!$container.length) return;

        const salaId = $container.data('sala-id');
        const types = ['normal', 'vip', 'discapacidad', 'pasillo', 'inactivo'];

        // Click on seat to cycle types
        $container.on('click', '.seat', function() {
            const $seat = $(this);
            let currentType = $seat.attr('data-tipo');
            let nextIndex = (types.indexOf(currentType) + 1) % types.length;
            let nextType = types[nextIndex];

            // Update UI
            $seat.removeClass(types.map(t => 'type-' + t).join(' '));
            $seat.addClass('type-' + nextType);
            $seat.attr('data-tipo', nextType);
            $seat.attr('title', `${$seat.data('fila')}${$seat.data('numero')} - ${nextType.charAt(0).toUpperCase() + nextType.slice(1)}`);

            if (nextType === 'pasillo' || nextType === 'inactivo') {
                $seat.addClass('is-inactive');
            } else {
                $seat.removeClass('is-inactive');
            }

            // Recalculate row numbering skipping pasillos
            let fila = $seat.data('fila');
            let realNumber = 1;
            $container.find(`.seat[data-fila="${fila}"]`).each(function() {
                let $s = $(this);
                let tipo = $s.attr('data-tipo');
                
                let displayNum = $s.data('numero'); // fallback
                if (tipo === 'pasillo') {
                    $s.attr('title', `${fila} - Pasillo`);
                    $s.text(' ');
                } else {
                    displayNum = realNumber;
                    realNumber++;
                    $s.attr('title', `${fila}${displayNum} - ${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
                    $s.text(`${fila}${displayNum}`);
                }
            });
        });

        // Save Layout via AJAX
        $('#cnes-save-layout').on('click', function() {
            const $btn = $(this);
            const $spinner = $container.find('.spinner');
            const asientos = [];

            $('.seat').each(function() {
                const $seat = $(this);
                asientos.push({
                    fila: $seat.data('fila'),
                    numero: $seat.data('numero'),
                    tipo: $seat.attr('data-tipo')
                });
            });

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cnes_guardar_layout_sala',
                    nonce: cnes_admin.nonce,
                    sala_id: salaId,
                    asientos: asientos
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Optional: update capacity display if exists
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error de comunicación con el servidor.');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
    });

})(jQuery);
