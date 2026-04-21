/**
 * Logica de Seleccion de Asientos — Cine Sala Estrella
 */

document.addEventListener('DOMContentLoaded', () => {
    const mapaAsientos = document.getElementById('mapaAsientos');
    const listaAsientosTags = document.getElementById('asientosSeleccionadosTags');
    const countAsientos = document.getElementById('countAsientos');
    const totalPagar = document.getElementById('totalPagar');
    const btnContinuar = document.getElementById('btnContinuar');
    
    const PRECIO_UNITARIO = 4500;
    let asientosSeleccionados = [];

    // Generar Mapa de Asientos (A-I, 1-14)
    const filas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
    const totalAsientosPorFila = 14;

    // Asientos ocupados de ejemplo (aleatorios para simular realismo)
    const asientosOcupados = [
        'A5', 'A6', 'B7', 'B8', 'C3', 'C4', 'C11', 'C12',
        'E7', 'E8', 'F1', 'F2', 'F13', 'F14', 'G7', 'G8',
        'I1', 'I2', 'I7', 'I8'
    ];

    function initMapa() {
        filas.forEach(letra => {
            const filaElement = document.createElement('div');
            filaElement.className = 'fila';

            // Etiqueta izquierda
            const labelIzq = document.createElement('div');
            labelIzq.className = 'fila-label';
            labelIzq.textContent = letra;
            filaElement.appendChild(labelIzq);

            // Grupo Izquierda (1-7)
            const grupoIzq = document.createElement('div');
            grupoIzq.className = 'asientos-grupo';
            for (let i = 1; i <= 7; i++) {
                grupoIzq.appendChild(createAsiento(letra, i));
            }
            filaElement.appendChild(grupoIzq);

            // Pasillo
            const pasillo = document.createElement('div');
            pasillo.className = 'pasillo';
            filaElement.appendChild(pasillo);

            // Grupo Derecha (8-14)
            const grupoDer = document.createElement('div');
            grupoDer.className = 'asientos-grupo';
            for (let i = 8; i <= 14; i++) {
                grupoDer.appendChild(createAsiento(letra, i));
            }
            filaElement.appendChild(grupoDer);

            // Etiqueta derecha
            const labelDer = document.createElement('div');
            labelDer.className = 'fila-label';
            labelDer.textContent = letra;
            filaElement.appendChild(labelDer);

            mapaAsientos.appendChild(filaElement);
        });
    }

    function createAsiento(fila, numero) {
        const id = `${fila}${numero}`;
        const asiento = document.createElement('div');
        asiento.className = 'asiento';
        asiento.dataset.id = id;
        asiento.textContent = numero;

        if (asientosOcupados.includes(id)) {
            asiento.classList.add('ocupado');
        } else {
            asiento.addEventListener('click', () => toggleAsiento(asiento));
        }

        return asiento;
    }

    function toggleAsiento(element) {
        const id = element.dataset.id;
        
        if (asientosSeleccionados.includes(id)) {
            // Deseleccionar
            asientosSeleccionados = asientosSeleccionados.filter(a => a !== id);
            element.classList.remove('seleccionado');
        } else {
            // Seleccionar
            asientosSeleccionados.push(id);
            element.classList.add('seleccionado');
        }

        updateResumen();
    }

    function updateResumen() {
        // Ordenar asientos alfabéticamente
        asientosSeleccionados.sort();

        // Actualizar tags
        listaAsientosTags.innerHTML = '';
        if (asientosSeleccionados.length === 0) {
            listaAsientosTags.innerHTML = '<span class="resumen-valor" style="font-weight: normal; color: var(--color-gris);">Ninguno</span>';
        } else {
            asientosSeleccionados.forEach(id => {
                const tag = document.createElement('span');
                tag.className = 'tag-asiento';
                tag.textContent = id;
                listaAsientosTags.appendChild(tag);
            });
        }

        // Actualizar contador y total
        countAsientos.textContent = asientosSeleccionados.length;
        const total = asientosSeleccionados.length * PRECIO_UNITARIO;
        totalPagar.textContent = `$${total.toLocaleString('es-CL')}`;

        // Habilitar/Deshabilitar botón Continuar
        btnContinuar.disabled = asientosSeleccionados.length === 0;
    }

    function centrarMapa() {
        if (mapaAsientos && window.innerWidth < 992) {
            const scrollCentro = (mapaAsientos.scrollWidth - mapaAsientos.clientWidth) / 2;
            mapaAsientos.scrollLeft = scrollCentro;
        }
    }

    initMapa();
    updateResumen();
    // Pequeño delay para asegurar que el DOM ha calculado los anchos correctamente
    setTimeout(centrarMapa, 100);
});
