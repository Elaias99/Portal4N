import React from 'react';
import { createRoot } from 'react-dom/client';
import TrackingTable from './TrackingTable';

const container = document.getElementById('latam-tracking-workspace');
const propsNode = document.getElementById('latam-tracking-workspace-props');

if (container && propsNode) {
    const props = JSON.parse(propsNode.textContent || '{}');
    createRoot(container).render(<TrackingTable {...props} />);
}