<?php

/**
 * Plugin Name: Yoast Redirect GraphQL Checker
 * Plugin URI: https://github.com/assef/yoast-graphql-redirect-checker
 * Description: Exposes Yoast SEO redirects to WPGraphQL.
 * Version: 1.0.0
 * Author: Leonardo Assef
 * Author URI: https://github.com/assef
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

add_action('graphql_register_types', function () {
    register_graphql_field('RootQuery', 'yoastRedirectForUrl', [
        'type' => 'YoastRedirect',
        'description' => __('Check if a given URL has a Yoast redirect', 'custom'),
        'args' => [
            'url' => [
                'type' => 'String',
                'description' => 'The origin URL to check for a redirect (relative path)',
            ],
        ],
        'resolve' => function ($source, $args) {
            if (empty($args['url'])) {
                return null;
            }

            $redirectsObj = class_exists('WPSEO_Redirect_Option') ? new WPSEO_Redirect_Option() : null;
            $redirects = $redirectsObj ? $redirectsObj->get_from_option() : [];

            $normalizedUrl = rtrim($args['url'], '/');

            foreach ($redirects as $redirect) {
                if (empty($redirect['origin']) || empty($redirect['url'])) {
                    continue;
                }

                $origin = rtrim($redirect['origin'], '/');

                if ($redirect['format'] === 'regex') {
                    // Sanitize and safely evaluate regex
                    $pattern = '~' . $origin . '~';
                    if (@preg_match($pattern, $normalizedUrl)) {
                        return [
                            'origin' => $redirect['origin'],
                            'target' => $redirect['url'],
                            'type' => $redirect['type'],
                            'format' => $redirect['format'],
                        ];
                    }
                } else {
                    // Exact match
                    if ($origin === $normalizedUrl) {
                        return [
                            'origin' => $redirect['origin'],
                            'target' => $redirect['url'],
                            'type' => $redirect['type'],
                            'format' => $redirect['format'],
                        ];
                    }
                }
            }

            return null;
        }
    ]);

    register_graphql_object_type('YoastRedirect', [
        'description' => 'A single Yoast SEO redirect',
        'fields' => [
            'origin' => ['type' => 'String'],
            'target' => ['type' => 'String'],
            'type' => ['type' => 'String'],
            'format' => ['type' => 'String'],
        ],
    ]);
});
