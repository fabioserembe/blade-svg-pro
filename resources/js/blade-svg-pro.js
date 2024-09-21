document.addEventListener("DOMContentLoaded", function () {
    const svgs = document.querySelectorAll('[data-name=blade-svg-pro]');

    svgs.forEach(svg => {
        // Trova gli elementi che potrebbero rappresentare lo sfondo
        const hasBackground = svg.querySelector('rect, circle, ellipse');

        // Se non ci sono sfondi, applica la logica di bounding box
        if (!hasBackground) {
            const path = svg.querySelector('path, g');
            if (path) {
                const bbox = path.getBBox(); // Ottiene il bounding box

                // Imposta il viewBox dinamicamente solo per le icone senza sfondo
                svg.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
            }
        }
    });
});