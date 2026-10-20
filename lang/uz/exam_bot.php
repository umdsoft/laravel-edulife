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
    ],

    // ---- /start and menu ----
    'welcome_new' => "Assalomu alaykum! 👋\n\n<b>Shanba imtihoni</b> — matematika va fizika bo'yicha haftalik onlayn imtihon. Har shanba alohida variant, avtomatik tekshiruv, o'sish grafigi va ota-onaga hisobot.\n\nRo'yxatdan o'tish 1 daqiqa oladi — 5 ta savol.",
    'welcome_back' => "Assalomu alaykum, :name! 👋\n\nImtihonlar, natijalar va qabul testi — pastdagi tugmalar orqali.",
    'menu' => 'Asosiy menyu:',
    'help' => "Buyruqlar:\n/start — boshlash\n/natijalar — oxirgi natijalar\n/taklif — do'stni taklif qilish havolasi\n/bekor — ro'yxatdan o'tishni qaytadan boshlash\n\nImtihonni o'zi «📝 Imtihonlar» tugmasi orqali Mini App'da topshiriladi.",
    'unknown' => "Tushunmadim. /yordam — buyruqlar ro'yxati.",
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
        'need_registration' => "Avval ro'yxatdan o'ting: /start",
    ],

    // ---- referral ----
    'invite' => [
        'text' => "Do'stingizga shu havolani yuboring — u ro'yxatdan o'tib, qabul testini topshirsa, sizga bonus beriladi:\n:link",
        'unavailable' => 'Taklif havolasi hozircha mavjud emas.',
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
        'open_in_telegram' => "Bu sahifa Telegram ichida ochiladi. Botga o'ting va «📝 Imtihonlar» tugmasini bosing.",
        'not_registered' => "Siz hali ro'yxatdan o'tmagansiz. Botga qayting va /start bosing.",
        'invalid' => "Kirish ma'lumotlari tasdiqlanmadi. Mini App'ni yopib, qayta oching.",
        'blocked' => "Akkaunt bloklangan. Ustozga murojaat qiling.",
        'error' => "Xatolik yuz berdi. Qayta urinib ko'ring.",
    ],

    'menu_button' => 'Imtihonlar',
];
