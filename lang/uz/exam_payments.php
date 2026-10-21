<?php

/*
 * Paid exams (docs/exam/QARORLAR.md item 9): texts of the Telegram reminders for a paid Saturday exam.
 * Telegram HTML is allowed; :price is filled in by the code ("15 000").
 */

return [
    // the web_app button of a reminder for a student who has not paid (instead of «📝 Imtihonga kirish»)
    'pay_button' => "💳 To'lash",
    // added under the reminder text for that student
    'reminder_pay_hint' => "💳 Bu imtihon pullik: <b>:price so'm</b>. To'lang yoki shanba imtihonlari kiradigan obuna oling.",
];
