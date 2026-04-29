export function createCobranzaState() {
    return {
        tipo: 'cobranza',
        pendientes: [],
        index: 0,
        guided: false,
        pendingActionAfterHide: null,
    };
}