import React from 'react';
import { createRoot } from 'react-dom/client';

function LandingTest() {
    return (
        <div style={{ padding: '40px', fontFamily: 'Arial, sans-serif' }}>
            <h1>React ya está funcionando dentro de Portal4N</h1>
            <p>Esta es una prueba mínima antes de traer la landing completa.</p>
        </div>
    );
}

const rootElement = document.getElementById('react-root');

if (rootElement) {
    createRoot(rootElement).render(<LandingTest />);
}