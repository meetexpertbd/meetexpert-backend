<?php

namespace App\Enums;

enum RegistrationFrom: string
{
    case AdminPanel = 'admin_panel';
    case Web = 'web';
    case App = 'app';
}
