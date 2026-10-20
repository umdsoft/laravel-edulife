<?php

/*
|--------------------------------------------------------------------------
| "Shanba imtihoni" Telegram bot texts (stage 6)
|--------------------------------------------------------------------------
|
| Every message the bot sends lives here so the marketing team can edit it
| without touching code. Telegram HTML is allowed: <b>, <i>, <a href="">.
| :placeholders are filled (and HTML-escaped) by the code.
| A Russian copy (lang/ru/exam_bot.php) can be added later with the same keys.
*/

return [

    // ---- buttons ----
    'btn' => [
        'student' => "O'quvchiman",
        'parent' => 'Ota-onaman',
        'grade_9' => '9-sinf',
        'grade_10' => '10-sinf',
        'grade_11' => '11-sinf',
        'graduate' => 'Bitirganman',
        'math' => 'Matematika',
        'physics' => 'Fizika',
        'both' => 'Ikkalasi',
        'goal_otm_grant' => 'OTM grant',
        'goal_milliy_sertifikat' => 'Milliy sertifikat',
        'goal_sat' => 'SAT',
        'share_phone' => '📱 Telefon raqamni yuborish',
        'consent_yes' => '✅ Roziman',
        'consent_no' => '❌ Rozi emasman',
        'open_app' => '📝 Imtihonlar',
        'placement' => '🚀 Qabul testini boshlash',
        'results' => '📊 Natijalarim',
        'invite' => "🤝 Do'stni taklif qilish",
        'open_exam' => '📝 Imtihonga kirish',
        'open_result' => "📊 Natijani ko'rish",
        'open_report' => "📈 Hisobotni ko'rish",
        // --- Telegram WebView (wave 2) ---
        'open_battle' => '⚔️ Battle',
        'placement_math' => '🚀 Qabul testi: matematika',
        'placement_physics' => '🚀 Qabul testi: fizika',
        // --- end Telegram WebView ---
        // --- TW (WebView-first) ---
        'open' => '🚀 Ochish',
        'open_progress' => '📊 Natijalarni ochish',
        'open_children' => '👨‍👩‍👧 Farzandlarim',
        'share_invite' => "📤 Do'stga yuborish",
        'invite_page' => '🎁 Taklif sahifasi',
        'open_site' => '🌐 Saytda ochish',
        // --- end TW ---
    ],

    // ---- /start and menu ----
    // TW (WebView-first): one short message, everything else happens in the Mini App («🚀 Ochish»)
    'welcome_new' => "Assalomu alaykum! 👋\n\n<b>Shanba imtihoni</b> — matematika va fizikadan haftalik onlayn imtihon, o'sish grafigi va ota-onaga hisobot.\n\n«🚀 Ochish» tugmasini bosing — ro'yxatdan o'tish ilovaning o'zida, 1 daqiqa.\n\n<i>Ilova ochilmasa: /royxat</i>",
    'welcome_back' => "Assalomu alaykum, :name! 👋\n\nImtihon, battle va natijalar — «🚀 Ochish» tugmasida.",
    'menu' => "Hammasi ilovada — «🚀 Ochish»:",
    'help' => "Hammasi ilovada: pastdagi «Ochish» menyu tugmasi yoki «🚀 Ochish».\n\n/start — boshlash\n/imtihon — imtihonlar va qabul testi\n/battle — bilimlar jangi\n/natijalar — natijalar\n/taklif — do'stni taklif qilish\n/royxat — ilova ochilmasa, chatda ro'yxatdan o'tish\n/bekor — chatdagi ro'yxatni bekor qilish",
    'unknown' => "Tushunmadim. Hammasi ilovada — «🚀 Ochish». Buyruqlar: /yordam",
    'too_many' => "Juda ko'p xabar. Birozdan keyin qayta urinib ko'ring.",

    // ---- registration (5 questions) ----
    'reg' => [
        'q_role' => "<b>1/5.</b> Kim ro'yxatdan o'tyapti?",
        'q_grade' => "<b>2/5.</b> Nechanchi sinf? (o'quvchining sinfi)",
        'q_subject' => "<b>3/5.</b> Qaysi fandan imtihon topshiriladi?",
        'q_goal' => "<b>4/5.</b> Maqsad nima?",
        'q_contact_student' => "<b>5/5.</b> Telefon raqamingizni yuboring (pastdagi tugma). Bu raqam saytga kirish uchun login bo'ladi.",
        'q_contact_parent' => "<b>5/5.</b> Telefon raqamingizni yuboring (pastdagi tugma). Farzandingiz imtihonni shu Telegram orqali topshiradi, hisobotlar ham shu yerga keladi.",
        'q_parent_phone' => "Ota-onangizdan birining telefon raqamini yozing, masalan: <code>+998901234567</code>",
        'q_consent_student' => "Ota-onangiz shaxsiy ma'lumotlaringiz (ism, sinf, natijalar) imtihon tizimida saqlanishiga roziligini tasdiqlang. Rozilik ota-onaga bog'langanda ular tomonidan ham tasdiqlanadi.",
        'q_consent_parent' => "Farzandingiz ma'lumotlari (ism, sinf, natijalar) imtihon tizimida saqlanishi va sizga hisobot yuborilishiga rozimisiz?",
        'contact_not_own' => 'Iltimos, aynan o\'zingizning raqamingizni pastdagi tugma orqali yuboring.',
        'phone_invalid' => "Raqam noto'g'ri. +998XXXXXXXXX ko'rinishida yozing.",
        'phone_uz_only' => "Hozircha faqat O'zbekiston raqamlari (+998) qabul qilinadi.",
        'consent_required' => "Rozilik bo'lmasa ro'yxatdan o'tib bo'lmaydi. Fikringiz o'zgarsa — /start.",
        'use_buttons' => 'Iltimos, tugmalardan birini tanlang.',
        'restarted' => "Ro'yxatdan o'tish qaytadan boshlandi.",
        'done_student' => "Tayyor! 🎉 Siz ro'yxatdan o'tdingiz.\n\nEndi bepul <b>qabul testi</b>ni topshiring — u mavzular bo'yicha darajangizni aniqlaydi va har shanba sizga mos variant tuziladi.",
        'done_parent' => "Tayyor! 🎉 Farzandingiz ro'yxatdan o'tdi.\n\nEndi bepul <b>qabul testi</b>ni shu Telegram orqali topshirsin. Natija va har yakshanba hisobot shu yerga keladi.",
        'role_conflict' => "Bu raqam boshqa turdagi akkauntga tegishli. Saytga o'z akkauntingiz bilan kiring va shu havolani oching: :url",
        'already_linked' => "Bu raqamdagi akkaunt boshqa Telegram akkauntiga ulangan. Yordam uchun ustozga murojaat qiling.",
        'failed' => "Kechirasiz, ro'yxatdan o'tishda xatolik yuz berdi. /start bilan qayta urinib ko'ring.",
        // --- TW (WebView-first) ---
        'phone_taken' => "Bu raqam bilan akkaunt allaqachon bor. Raqamni «Telefon raqamni ulashish» tugmasi bilan yuboring (Telegram uni tasdiqlaydi) yoki saytga kirib Telegramni ulang: :url",
        'cancelled' => "Chatdagi ro'yxatdan o'tish bekor qilindi.",
        // --- end TW ---
    ],

    // ---- linking an existing web account (/start link_<token>) ----
    'link' => [
        'done' => "Akkauntingiz Telegramga ulandi ✅ Endi natijalar va eslatmalar shu yerga keladi.",
        'expired' => "Havola eskirgan yoki noto'g'ri. Saytdan «Telegramni ulash» tugmasini qayta bosing.",
        'not_configured' => 'Telegram bot hali sozlanmagan.',
    ],

    // ---- results ----
    'results' => [
        'title' => "<b>Oxirgi natijalar:</b>",
        'line' => "• :title — <b>:score / :max</b> (:percent%)",
        'none' => "Hali natija yo'q. Qabul testidan boshlang!",
        'need_registration' => "Avval ro'yxatdan o'ting — «🚀 Ochish» tugmasini bosing (ilova ochilmasa: /royxat).",
        // --- TW (WebView-first) ---
        'last' => "📊 Oxirgi natija: <b>:title</b> — <b>:score / :max</b> (:percent%)\nBarcha natijalar va o'sish grafigi — ilovada.",
        'guardian' => "👨‍👩‍👧 Farzandlaringiz natijalari va yakshanba hisobotlari — ilovada.",
        // --- end TW ---
    ],

    // ---- referral ----
    'invite' => [
        'text' => "Do'stingizga shu havolani yuboring — u ro'yxatdan o'tib, qabul testini topshirsa, sizga bonus beriladi:\n:link",
        'unavailable' => 'Taklif havolasi hozircha mavjud emas.',
        // --- TW (WebView-first) ---
        'short' => "🤝 Do'stingizni taklif qiling: u ro'yxatdan o'tib qabul testini topshirsa, sizga bonus coin beriladi.",
        'share_text' => "Shanba imtihoni — matematika va fizikadan haftalik bepul imtihon. Qo'shil! 🚀",
        // --- end TW ---
    ],

    // ---- reminders ----
    'reminder' => [
        'eve' => "⏰ Ertaga <b>:title</b>!\nBoshlanish: <b>:time</b>. Davomiyligi :duration daqiqa.\nYaxshi dam oling va telefoningizni zaryadlab qo'ying 🙂",
        'hour' => "⏳ <b>:title</b> 1 soatdan keyin boshlanadi (:time).",
        'start' => "🚀 <b>:title</b> boshlandi! Omad!",
    ],

    // ---- results after grading ----
    'attempt' => [
        'student' => "✅ <b>:title</b> natijasi: <b>:score / :max</b> (:percent%)",
        'student_delta_up' => "O'tgan haftaga nisbatan: <b>+:delta</b> ball 📈",
        'student_delta_down' => "O'tgan haftaga nisbatan: :delta ball",
        'student_videos' => "Xato qilingan masalalar yechimi (video) natija sahifasida.",
        'guardian' => "👨‍👩‍👧 :name — <b>:title</b>: <b>:score / :max</b> (:percent%)",
    ],

    // ---- Sunday report to the guardian ----
    'report' => [
        'title' => "📈 <b>:name — haftalik hisobot</b> (:week)",
        'subject_line' => "• :subject: <b>:percent%</b>:delta",
        'delta' => ' (:sign:delta)',
        'recommend_title' => "Keyingi haftaga tavsiya mavzular:",
        'recommend_line' => "• :topic",
        'no_result' => "• :subject: bu hafta imtihon topshirilmadi",
    ],

    // ---- Mini App page ----
    'app' => [
        'title' => 'Shanba imtihoni',
        'loading' => 'Yuklanmoqda…',
        'open_in_telegram' => "Bu sahifa Telegram ichida ochiladi. Botga o'ting va «Ochish» tugmasini bosing.",
        'not_registered' => "Siz hali ro'yxatdan o'tmagansiz.",
        'invalid' => "Kirish ma'lumotlari tasdiqlanmadi. Mini App'ni yopib, qayta oching.",
        'blocked' => "Akkaunt bloklangan. Ustozga murojaat qiling.",
        'error' => "Xatolik yuz berdi. Qayta urinib ko'ring.",
        // --- Telegram WebView (wave 2) ---
        'starting' => 'Test boshlanmoqda…',
        'cookies_blocked' => "Brauzer kirish ma'lumotini saqlay olmadi (cookie bloklangan). Telegram ilovasida oching yoki saytda kiring.",
        // --- end Telegram WebView ---
        // --- TW (WebView-first) ---
        'staff' => "Bu ustoz yoki admin akkaunti. Telegram ilovasi o'quvchi va ota-onalar uchun — panelni saytda oching.",
        'contact_invalid' => "Telefon raqam tasdiqlanmadi. Qayta ulashing yoki raqamni qo'lda yozing.",
        'site_link_expired' => "Kirish havolasi eskirgan. Telegram ilovasida «Saytda ochish» tugmasini qayta bosing.",
        'registering' => "Ro'yxatdan o'tish ochilmoqda…",
        // --- end TW ---
    ],

    'menu_button' => 'Ochish',

    // --- Telegram WebView (wave 2): /imtihon, /battle ---
    'exam_cmd' => [
        'text' => "📝 <b>Shanba imtihoni</b>\nHar shanba alohida variant, avtomatik tekshiruv va o'sish grafigi. Imtihon shu yerning o'zida — Mini App'da topshiriladi.",
        'running' => "⏳ Sizda boshlangan imtihon bor: <b>:title</b>. Davom ettiring!",
        'next' => "📅 Keyingi imtihon: <b>:title</b> — :date",
    ],
    'battle' => [
        'text' => "⚔️ <b>Bilimlar jangi</b>\nTasodifiy raqib yoki do'stingiz bilan 1 ga 1 bellashuv. Savollar ikkalangizga bir xil, vaqtni server hisoblaydi.",
        'invite_share' => "Men bilan bilimlar jangida bellashasanmi? ⚔️",
    ],
    // --- end Telegram WebView ---
];
