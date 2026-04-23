// Daino — entry point del bundle.
//
// Por ahora solo verifica que el pipeline Vite+Tailwind+Bun funciona y que
// el manifest carga correctamente desde public/index.php. Los módulos reales
// se añaden cuando arranquemos el scaffolding de game/ y ui/
// (ver docs/refactorizacion/05-Estructura-Proyecto.md).

import '@css/main.css';

console.info('[Daino] main.js cargado — pipeline OK');

// HMR manual para CSS/JS simples (Vite lo hace auto, esto solo suprime el reload)
if (import.meta.hot) {
    import.meta.hot.accept();
}
