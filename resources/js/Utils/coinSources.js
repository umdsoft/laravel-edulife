// Uzbek names of coin_transactions.source codes (the coin history "Manba" column).
export const COIN_SOURCES = {
    lesson_complete: 'Dars tugatildi',
    test_pass: "Testdan o'tildi",
    course_complete: 'Kurs tugatildi',
    daily_streak: 'Kunlik seriya',
    daily_bonus: 'Kunlik bonus',
    mission: 'Kunlik vazifa',
    daily_mission: 'Kunlik vazifa',
    achievement: 'Yutuq',
    referral: 'Taklif',
    battle_win: "Battle g'alabasi",
    lab_experiment: 'Laboratoriya tajribasi',
    lab_badge: 'Laboratoriya nishoni',
    olympiad_reward: 'Olimpiada mukofoti',
    olympiad_registration: "Olimpiadaga ro'yxat",
    olympiad_refund: 'Olimpiada (qaytarildi)',
    olympiad_demo: 'Olimpiada demo testi',
    tournament: 'Turnir',
    tournament_refund: 'Turnir (qaytarildi)',
    shop: "Do'kon",
    coin_package: 'COIN paketi',
    legacy_transfer: "Avvalgi balans",
    admin: 'Administrator',
    test: 'Sinov',
};

/** "lab_experiment" -> "Laboratoriya tajribasi"; an unknown code never shows raw. */
export const coinSourceLabel = (source) => {
    if (!source) return '—';
    return COIN_SOURCES[source] || 'Boshqa';
};
