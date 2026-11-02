<?php

return [
    /*
    | Users with this role may do everything (checked before any permission). It is created by the migration that
    | converted the old user groups ("Super Users") and cannot be deleted or renamed in the admin.
    */
    'super_role' => env('ADMIN_SUPER_ROLE', 'Super Users'),
];
