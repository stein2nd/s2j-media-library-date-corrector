<?php

/**
 * REST API routes for media date correction.
 *
 * @package S2J_MLDC
 */

namespace S2J\MLDC;

/**
 * REST controller.
 */
class RestController
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', array( $this, 'registerRoutes' ));
    }

    /**
     * Register REST routes.
     *
     * @return void
     */
    public function registerRoutes(): void
    {
        register_rest_route(
            S2J_MLDC_REST_NAMESPACE,
            '/attachments/correct',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handleCorrect' ),
                'permission_callback' => array( $this, 'canCorrectMedia' ),
                'args'                => array(
                    'ids' => array(
                        'required' => true,
                        'type'     => 'array',
                        'maxItems' => S2J_MLDC_CHUNK_SIZE,
                        'items'    => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            S2J_MLDC_REST_NAMESPACE,
            '/attachments/correct-query',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handleCorrectQuery' ),
                'permission_callback' => array( $this, 'canCorrectMedia' ),
                'args'                => array(
                    'query'  => array(
                        'required' => true,
                        'type'     => 'object',
                    ),
                    'offset' => array(
                        'type'    => 'integer',
                        'default' => 0,
                    ),
                ),
            )
        );
    }

    /**
     * Route permission: upload_files.
     *
     * @param \WP_REST_Request $request Request.
     * @return bool|\WP_Error
     */
    public function canCorrectMedia(\WP_REST_Request $request)
    {
        unset($request);

        if (! current_user_can('upload_files')) {
            return new \WP_Error(
                'rest_forbidden',
                __('You do not have permission to correct media dates.', 's2j-media-library-date-corrector'),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        return true;
    }

    /**
     * POST /attachments/correct.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handleCorrect(\WP_REST_Request $request)
    {
        $ids = $request->get_param('ids');
        if (! is_array($ids)) {
            return new \WP_Error(
                'rest_invalid_param',
                __('Invalid ids parameter.', 's2j-media-library-date-corrector'),
                array( 'status' => 400 )
            );
        }

        if (count($ids) > S2J_MLDC_CHUNK_SIZE) {
            return new \WP_Error(
                'rest_invalid_param',
                __('Too many ids in one request.', 's2j-media-library-date-corrector'),
                array( 'status' => 400 )
            );
        }

        $payload = MediaDateService::correctIds($ids);

        return rest_ensure_response($payload);
    }

    /**
     * POST /attachments/correct-query.
     *
     * @param \WP_REST_Request $request Request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handleCorrectQuery(\WP_REST_Request $request)
    {
        $query  = $request->get_param('query');
        $offset = (int) $request->get_param('offset');

        if (! is_array($query)) {
            return new \WP_Error(
                'rest_invalid_param',
                __('Invalid query parameter.', 's2j-media-library-date-corrector'),
                array( 'status' => 400 )
            );
        }

        $allowed  = array( 's', 'm', 'post_mime_type', 'author', 'orderby', 'order' );
        $filtered = array();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $query)) {
                $filtered[ $key ] = $query[ $key ];
            }
        }

        $payload = MediaDateService::correctQuery($filtered, $offset);

        return rest_ensure_response($payload);
    }
}
