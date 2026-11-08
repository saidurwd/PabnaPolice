<?php

return [
    /*
    | Passwords inherited from the Yii app are unsalted SHA1. They are always accepted at login.
    | When true, they are rehashed to bcrypt on the user's next login (and new passwords use bcrypt).
    | Leave false until the Yii admin is retired: it cannot verify bcrypt hashes.
    */
    'password_upgrade' => (bool) env('LEGACY_PASSWORD_UPGRADE', false),

    /*
    | Phase 4 migrations rename tables/columns and convert character sets, which makes the old Yii application
    | unusable. They refuse to run until this is true (switch Yii off and back up the database first).
    */
    'yii_retired' => (bool) env('YII_RETIRED', false),
];
