<div data-install class="{{ $installClass ?? 'mt-6 rounded-3xl bg-card px-4 py-4 text-center shadow-sm ring-1 ring-line' }}">
    <p class="text-base font-semibold text-ink">Add this to your phone</p>
    <p data-install-ios class="mt-1 hidden text-sm leading-snug text-muted">In Safari, tap Share, then Add to Home Screen.</p>
    <p data-install-other class="mt-1 text-sm leading-snug text-muted">Use the browser menu, then Install app or Add to Home screen.</p>
    <button type="button" data-install-button class="mt-3 hidden min-h-12 w-full touch-manipulation rounded-full bg-pine text-base font-semibold text-white shadow-sm shadow-pine/25">
        Add to home screen
    </button>
</div>
