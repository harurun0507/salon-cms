/**
 * Shared insert helpers for admin card grids (news / blog).
 * - placement "start": prepend as first card + scroll into view
 * - placement "end" (default): insert before the trailing add-card
 */
function insertAdminWorkspaceCard(card, options) {
    const grid = options.grid;
    const addCard = options.addCard;
    const cardSelector = options.cardSelector;
    const atStart = options.placement === 'start';

    if (!card || !grid || !addCard || !cardSelector) {
        return;
    }

    if (atStart) {
        const firstCard = grid.querySelector(cardSelector);
        if (firstCard) {
            firstCard.before(card);
        } else {
            addCard.before(card);
        }
    } else {
        addCard.before(card);
    }

    if (atStart) {
        requestAnimationFrame(function () {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        });
    }
}

window.AdminWorkspaceCards = {
    insert: insertAdminWorkspaceCard,
};

export { insertAdminWorkspaceCard };
