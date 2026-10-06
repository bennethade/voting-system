import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/**
 * Package picker used on the paid-vote page and modal. The displayed total
 * is only a preview: the server recalculates everything from the package id.
 */
Alpine.data('packagePicker', (packages, selected = null) => ({
    packages,
    selectedId: selected || null,
    get selected() {
        return this.packages.find((p) => p.id === Number(this.selectedId)) || null;
    },
    choose(id) {
        this.selectedId = id;
    },
}));

/** Bottom-sheet/modal for buying votes from a nominee card. */
Alpine.data('paidVoteModal', (packages, actionTemplate) => ({
    open: false,
    nominee: { slug: '', name: '' },
    packages,
    selectedId: null,
    get selected() {
        return this.packages.find((p) => p.id === Number(this.selectedId)) || null;
    },
    get action() {
        return actionTemplate.replace('__NOMINEE__', encodeURIComponent(this.nominee.slug));
    },
    show(detail) {
        this.nominee = detail;
        this.selectedId = null;
        this.open = true;
    },
}));

/** Disable a form's submit button after the first submit (double-click guard). */
Alpine.data('submitOnce', () => ({
    submitting: false,
    submit(event) {
        if (this.submitting) {
            event.preventDefault();
            return;
        }
        this.submitting = true;
    },
}));

/** Polls a signed status endpoint while a payment is still pending. */
Alpine.data('paymentPoller', (url, initial) => ({
    status: initial,
    attempts: 0,
    init() {
        if (this.status === 'pending') this.poll();
    },
    async poll() {
        if (this.attempts++ > 40) return;
        await new Promise((r) => setTimeout(r, this.attempts < 6 ? 3000 : 8000));
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data.status !== 'pending') {
                window.location.reload();
                return;
            }
        } catch (e) {
            /* network blip: keep polling */
        }
        this.poll();
    },
}));

window.Alpine = Alpine;
Alpine.start();
