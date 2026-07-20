<?php

namespace App\Helpers;

use App\Models\User;

class MenuHelper
{
    public static function getMainNavItems()
    {
        return [
            [
                'icon' => 'dashboard',
                'name' => 'Dashboard',
                'path' => '/',
            ],
            [
                'icon' => 'taxonomies',
                'name' => 'Taxonomies',
                'subItems' => [
                    ['name' => 'Category', 'path' => '/taxonomy/categories', 'pro' => false],
                    ['name' => 'Subcategory', 'path' => '/taxonomy/subcategories', 'pro' => false],
                    ['name' => 'Skills', 'path' => '/taxonomy/skills', 'pro' => false],
                ],
            ],
            [
                'icon' => 'task',
                'name' => 'Expert applications',
                'path' => '/admin/expert-applications',
                'matchPathPrefix' => '/admin/expert-applications',
                'admin_only' => true,
            ],
            [
                'icon' => 'experts',
                'name' => 'Experts',
                'path' => '/admin/experts',
                'matchPathPrefix' => '/admin/experts',
                'admin_only' => true,
            ],
            [
                'icon' => 'bookings',
                'name' => 'Bookings',
                'path' => '/admin/bookings',
                'matchPathPrefix' => '/admin/bookings',
                'admin_only' => true,
            ],
            [
                'icon' => 'users',
                'name' => 'Users',
                'path' => '/admin/users',
                'matchPathPrefix' => '/admin/users',
                'admin_only' => true,
            ],
            [
                'icon' => 'users',
                'name' => 'All types users',
                'path' => '/admin/all-users',
                'matchPathPrefix' => '/admin/all-users',
                'admin_only' => true,
            ],
            /* [
                'icon' => 'authentication',
                'name' => 'Authentication',
                'subItems' => [
                    ['name' => 'Sign In', 'path' => '/signin', 'pro' => false],
                    ['name' => 'Sign Up', 'path' => '/signup', 'pro' => false],
                ],
            ], */
        ];
    }

    public static function getMenuGroups()
    {
        return [
            [
                'title' => 'Menu',
                'items' => self::filterNavItemsForCurrentUser(self::getMainNavItems()),
            ],
        ];
    }

    public static function isActive($path)
    {
        return request()->is(ltrim($path, '/'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function filterNavItemsForCurrentUser(array $items): array
    {
        $user = auth()->user();

        return array_values(array_filter($items, function (array $item) use ($user) {
            if (! empty($item['admin_only']) && (! $user || $user->user_type !== User::USER_TYPE_ADMIN)) {
                return false;
            }

            return true;
        }));
    }

    public static function getIconSvg($iconName)
    {
        $icons = [
            'dashboard' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" fill="currentColor"></path></svg>',

            'task' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.75586 5.50098C7.75586 5.08676 8.09165 4.75098 8.50586 4.75098H18.4985C18.9127 4.75098 19.2485 5.08676 19.2485 5.50098L19.2485 15.4956C19.2485 15.9098 18.9127 16.2456 18.4985 16.2456H8.50586C8.09165 16.2456 7.75586 15.9098 7.75586 15.4956V5.50098ZM8.50586 3.25098C7.26322 3.25098 6.25586 4.25834 6.25586 5.50098V6.26318H5.50195C4.25931 6.26318 3.25195 7.27054 3.25195 8.51318V18.4995C3.25195 19.7422 4.25931 20.7495 5.50195 20.7495H15.4883C16.7309 20.7495 17.7383 19.7421 17.7383 18.4995L17.7383 17.7456H18.4985C19.7411 17.7456 20.7485 16.7382 20.7485 15.4956L20.7485 5.50097C20.7485 4.25833 19.7411 3.25098 18.4985 3.25098H8.50586ZM16.2383 17.7456H8.50586C7.26322 17.7456 6.25586 16.7382 6.25586 15.4956V7.76318H5.50195C5.08774 7.76318 4.75195 8.09897 4.75195 8.51318V18.4995C4.75195 18.9137 5.08774 19.2495 5.50195 19.2495H15.4883C15.9025 19.2495 16.2383 18.9137 16.2383 18.4995L16.2383 17.7456Z" fill="currentColor"></path></svg>',

            'experts' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 6.5a2.25 2.25 0 1 1 4.5 0 2.25 2.25 0 0 1-4.5 0ZM10.5 4.25a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5ZM5.5 14.5a3.25 3.25 0 0 1 3.25-3.25h3.5a3.25 3.25 0 0 1 3.25 3.25V19h-10v-4.5Zm1.5 0a1.75 1.75 0 0 1 1.75-1.75h3.5a1.75 1.75 0 0 1 1.75 1.75V17.5H7v-3Zm8.75-6.75a2.25 2.25 0 1 1 4.5 0 2.25 2.25 0 0 1-4.5 0Zm2.25-2.25a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm-1.5 9.5a3.25 3.25 0 0 0-1.45-2.71 3.25 3.25 0 0 1 2.2-.79h.5a3.25 3.25 0 0 1 3.25 3.25V19H16.5v-4.5Z" fill="currentColor"/></svg>',

            'users' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 5.75a2.75 2.75 0 1 1 5.5 0 2.75 2.75 0 0 1-5.5 0ZM11 4.5a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5ZM4.75 15.25a3.5 3.5 0 0 1 3.5-3.5h5.5a3.5 3.5 0 0 1 3.5 3.5V19h-12.5v-3.75Zm1.5 0a2 2 0 0 1 2-2h5.5a2 2 0 0 1 2 2V17.5h-9.5v-2.25ZM15.75 8.5a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm1.5 0a.5.5 0 1 0 1 0 .5.5 0 0 0-1 0Zm.25 4.25h2a2.75 2.75 0 0 1 2.75 2.75V19H16.5v-3.5a1.25 1.25 0 0 0-1.25-1.25h-1.5a4.23 4.23 0 0 0 2.75-4Z" fill="currentColor"/></svg>',

            'bookings' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 2.75a.75.75 0 0 1 .75.75v1h8.5V3.5a.75.75 0 0 1 1.5 0v1h1.25A2.25 2.25 0 0 1 21.25 6.75v13.5A2.25 2.25 0 0 1 19 22.25H5A2.25 2.25 0 0 1 2.75 20V6.75A2.25 2.25 0 0 1 5 4.5h1.25V3.5A.75.75 0 0 1 7 2.75ZM5 6c-.414 0-.75.336-.75.75v13.5c0 .414.336.75.75.75h14c.414 0 .75-.336.75-.75V6.75A.75.75 0 0 0 19 6H5Zm3.5 4.25a.75.75 0 0 1 .75.75v1.69l1.28 1.28a.75.75 0 1 1-1.06 1.06l-1.5-1.5a.75.75 0 0 1-.22-.53V11a.75.75 0 0 1 .75-.75Zm6 0a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5a.75.75 0 0 1 .75-.75Z" fill="currentColor"/></svg>',

            'taxonomies' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 6.25C5.08579 6.25 4.75 6.58579 4.75 7C4.75 7.41421 5.08579 7.75 5.5 7.75H7C7.41421 7.75 7.75 7.41421 7.75 7C7.75 6.58579 7.41421 6.25 7 6.25H5.5ZM9.25 7C9.25 6.58579 9.58579 6.25 10 6.25H18.5C18.9142 6.25 19.25 6.58579 19.25 7C19.25 7.41421 18.9142 7.75 18.5 7.75H10C9.58579 7.75 9.25 7.41421 9.25 7ZM5.5 11.25C5.08579 11.25 4.75 11.5858 4.75 12C4.75 12.4142 5.08579 12.75 5.5 12.75H7C7.41421 12.75 7.75 12.4142 7.75 12C7.75 11.5858 7.41421 11.25 7 11.25H5.5ZM9.25 12C9.25 11.5858 9.58579 11.25 10 11.25H18.5C18.9142 11.25 19.25 11.5858 19.25 12C19.25 12.4142 18.9142 12.75 18.5 12.75H10C9.58579 12.75 9.25 12.4142 9.25 12ZM5.5 16.25C5.08579 16.25 4.75 16.5858 4.75 17C4.75 17.4142 5.08579 17.75 5.5 17.75H7C7.41421 17.75 7.75 17.4142 7.75 17C7.75 16.5858 7.41421 16.25 7 16.25H5.5ZM9.25 17C9.25 16.5858 9.58579 16.25 10 16.25H18.5C18.9142 16.25 19.25 16.5858 19.25 17C19.25 17.4142 18.9142 17.75 18.5 17.75H10C9.58579 17.75 9.25 17.4142 9.25 17Z" fill="currentColor"></path></svg>',

            'authentication' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14 2.75C14 2.33579 14.3358 2 14.75 2C15.1642 2 15.5 2.33579 15.5 2.75V5.73291L17.75 5.73291H19C19.4142 5.73291 19.75 6.0687 19.75 6.48291C19.75 6.89712 19.4142 7.23291 19 7.23291H18.5L18.5 12.2329C18.5 15.5691 15.9866 18.3183 12.75 18.6901V21.25C12.75 21.6642 12.4142 22 12 22C11.5858 22 11.25 21.6642 11.25 21.25V18.6901C8.01342 18.3183 5.5 15.5691 5.5 12.2329L5.5 7.23291H5C4.58579 7.23291 4.25 6.89712 4.25 6.48291C4.25 6.0687 4.58579 5.73291 5 5.73291L6.25 5.73291L8.5 5.73291L8.5 2.75C8.5 2.33579 8.83579 2 9.25 2C9.66421 2 10 2.33579 10 2.75L10 5.73291L14 5.73291V2.75ZM7 7.23291L7 12.2329C7 14.9943 9.23858 17.2329 12 17.2329C14.7614 17.2329 17 14.9943 17 12.2329L17 7.23291L7 7.23291Z" fill="currentColor"></path></svg>',
        ];

        return $icons[$iconName] ?? '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>';
    }
}
