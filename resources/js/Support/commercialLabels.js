export function labelCommercialStatus(status) {
    return {
        invited: 'Invitat',
        contacted: 'Contactat',
        demo_scheduled: 'Demo programat',
        trial_started: 'Trial pornit',
        closed_won: 'Castigat',
        closed_lost: 'Pierdut',
    }[status] || status || '-';
}

export function labelCommercialStage(stage) {
    return {
        prospecting: 'Prospectare',
        contacted: 'Contactat',
        follow_up: 'Follow-up',
        demo: 'Demo',
        trial: 'Trial',
        negotiation: 'Negociere',
        won: 'Castigat',
        lost: 'Pierdut',
    }[stage] || stage || '-';
}

export function labelCommercialPlan(plan) {
    return {
        starter: 'Brand de baza',
        pro: 'Brand complet',
        enterprise: 'Enterprise',
        free: 'Demo',
    }[plan] || plan || '-';
}

export function labelCommercialRisk(level) {
    return {
        high: 'Ridicat',
        medium: 'Mediu',
        low: 'Scazut',
    }[level] || level || '-';
}

export function commercialRiskTone(level) {
    if (level === 'high') {
        return 'bg-rose-100 text-rose-700';
    }

    if (level === 'medium') {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
}
